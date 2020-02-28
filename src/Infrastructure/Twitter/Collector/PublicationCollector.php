<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Aggregate\Exception\LockedAggregateException;
use App\Amqp\Exception\SkippableMessageException;
use App\Api\AccessToken\AccessToken;
use App\Api\Entity\Aggregate;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Api\Entity\Whisperer;
use App\Domain\Status\StatusInterface;
use App\Infrastructure\Amqp\Message\FetchMemberStatuses;
use App\Infrastructure\DependencyInjection\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\ApiLimitModeratorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationListRepositoryTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationPersistenceTrait;
use App\Infrastructure\DependencyInjection\Status\StatusLoggerTrait;
use App\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Infrastructure\DependencyInjection\WhispererRepositoryTrait;
use App\Membership\Entity\MemberInterface;
use App\Operation\Collection\CollectionInterface;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Status\Repository\ExtremumAwareInterface;
use App\Status\Repository\LikedStatusRepository;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use App\Infrastructure\Twitter\Collector\Exception\RateLimitedException;
use App\Infrastructure\Twitter\Collector\Exception\SkipSerializationException;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionException;
use function array_key_exists;
use function count;
use function is_array;

class PublicationCollector implements PublicationCollectorInterface
{
    use ApiAccessorTrait;
    use ApiLimitModeratorTrait;
    use LoggerTrait;
    use PublicationListRepositoryTrait;
    use PublicationPersistenceTrait;
    use StatusLoggerTrait;
    use StatusRepositoryTrait;
    use TokenRepositoryTrait;
    use TranslatorTrait;
    use WhispererRepositoryTrait;

    public const MAX_AVAILABLE_TWEETS_PER_USER = 3200;

    private const MAX_BATCH_SIZE = 200;

    private const MESSAGE_OPTION_TOKEN = 'oauth';

    /**
     * @var LikedStatusRepository
     */
    public LikedStatusRepository $likedStatusRepository;

    /**
     * @var LoggerInterface
     */
    public LoggerInterface $twitterApiLogger;

    /**
     * @var array
     */
    protected array $collectingOptions = [];

    /**
     * @param $screenName
     * @param $lastSerializationBatchSize
     * @param $totalSerializedStatuses
     *
     * @return bool
     */
    public function flagWhisperers($screenName, $lastSerializationBatchSize, $totalSerializedStatuses)
    {
        $flaggedWhisperer = false;

        if (!$this->justSerializedSomeStatuses($lastSerializationBatchSize)) {
            $whisperer = new Whisperer($screenName, $totalSerializedStatuses);

            $member = $this->apiAccessor->showUser($screenName);
            $whisperer->setExpectedWhispers($member->statuses_count);

            $this->whispererRepository->declareWhisperer($whisperer);
            $whispererDeclarationMessage = $this->translator->trans(
                'logs.info.whisperer_declared',
                ['screen name' => $screenName],
                'logs'
            );
            $this->logger->info($whispererDeclarationMessage);
            $flaggedWhisperer = true;
        }

        return $flaggedWhisperer;
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function hitCollectionLimit($statuses): bool
    {
        return $statuses >= (self::MAX_AVAILABLE_TWEETS_PER_USER - 100);
    }

    /**
     * @param array $criteria
     *
     * @return bool
     */
    public function isAboutToCollectLikesFromCriteria(array $criteria): bool
    {
        if (!array_key_exists(self::INTENT_TO_FETCH_LIKES, $criteria)) {
            return false;
        }

        return $criteria[self::INTENT_TO_FETCH_LIKES];
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function justSerializedSomeStatuses($statuses): bool
    {
        return $statuses !== null && $statuses > 0;
    }

    /**
     * @param array $options
     * @param bool  $greedy
     * @param bool  $discoverPastTweets
     *
     * @return bool
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    public function collect(array $options, $greedy = false, $discoverPastTweets = true): bool
    {
        $successfulSerializationOptionSetup = $this->setUpSerializationOptions($options);

        try {
            $this->decideWhetherSerializationShouldBeSkipped($options);
        } catch (SkipSerializationException $exception) {
            if ($exception instanceof RateLimitedException) {
                return false; // unsuccessfully made an attempt to serialize statuses
            }

            return true;
        }

        if ($successfulSerializationOptionSetup) {
            // Remove serialization options when found
            $options = $this->removeSerializationOptions($options);

            try {
                $this->ensureTargetAggregateIsNotLocked();
            } catch (LockedAggregateException $exception) {
                return true;
            }
        }

        if (
            !$this->isTwitterApiAvailable()
            && ($remainingItemsToCollect = $this->remainingItemsToCollect($options))
        ) {
            $this->unlockAggregate();

            /**
             * Marks the serialization as successful if there are no remaining status
             */
            return isset($remainingItemsToCollect) ?: false;
        }

        if ($this->shouldLookUpFutureItems($options['screen_name'])) {
            $discoverPastTweets = false;
        }

        $options = $this->updateExtremum($options, $discoverPastTweets);

        try {
            $success = $this->trySerializingFurther($options, $greedy, $discoverPastTweets);
        } catch (BadAuthenticationDataException $exception) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
            if (!($token instanceof Token)) {
                return false;
            }

            $options = $this->setUpAccessorWithFirstAvailableToken($token, $options);
            $success = $this->trySerializingFurther($options, $greedy, $discoverPastTweets);
        } catch (UnavailableResourceException $exception) {
            throw $exception;
        } catch (SuspendedAccountException
        |NotFoundMemberException
        |ProtectedAccountException $exception
        ) {
            $this->handleUnavailableMemberException($exception, $options);

            // Figuring out a member is now protected, suspended or not found is considered to be a "success",
            // provided the workers would not call the API on behalf of them
            $success = true;
        } catch (ConstraintViolationException $constraintViolationException) {
            $this->logger->critical($constraintViolationException->getMessage());
            $success = false;
        } catch (Exception $exception) {
            $this->logger->error(
                sprintf(
                    '[from %s %s]',
                    __METHOD__,
                    $exception->getMessage()
                )
            );
            $success = false;
        } finally {
            $this->unlockAggregate();
        }

        return $success;
    }

    /**
     * @param $lastSerializationBatchSize
     * @param $totalSerializedStatuses
     *
     * @return bool
     */
    public function serializedAllAvailableStatuses($lastSerializationBatchSize, $totalSerializedStatuses): bool
    {
        return !$this->justSerializedSomeStatuses($lastSerializationBatchSize)
            && $this->hitCollectionLimit($totalSerializedStatuses);
    }

    /**
     * @param array $oauthTokens
     *
     * @return $this
     */
    public function setupAccessor(array $oauthTokens)
    {
        if (array_key_exists('authentication_header', $oauthTokens)) {
            $this->apiAccessor->setAuthenticationHeader($oauthTokens['authentication_header']);

            return $this;
        }

        $token = new Token();
        $token->setOAuthToken($oauthTokens[TokenInterface::FIELD_TOKEN]);
        $token->setOAuthSecret($oauthTokens[TokenInterface::FIELD_SECRET]);

        $this->apiAccessor->setAccessToken($token);

        /** @var Token token */
        $token = $this->tokenRepository
            ->findOneBy(['oauthToken' => $oauthTokens[TokenInterface::FIELD_TOKEN]]);

        if (!$token instanceof Token) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
        }

        $this->apiAccessor->setConsumerKey($token->consumerKey);
        $this->apiAccessor->setConsumerSecret($token->consumerSecret);

        return $this;
    }

    /**
     * @param      $options
     * @param bool $discoverPastTweets
     *
     * @return array
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    protected function fetchLatestStatuses($options, bool $discoverPastTweets = true): array
    {
        $options[self::INTENT_TO_FETCH_LIKES] = $this->isAboutToCollectLikesFromCriteria($this->collectingOptions);
        $options                              = $this->removeSerializationOptions($options);
        $options                              = $this->updateExtremum($options, $discoverPastTweets);

        if (
            array_key_exists('max_id', $options)
            && array_key_exists('before', $options) // Looking into the past
        ) {
            unset($options['max_id']);
        }

        $statuses = $this->apiAccessor->fetchStatuses($options);

        $discoverMoreRecentStatuses = false;
        if (
            count($statuses) > 0
            && $this->statusRepository->findOneBy(
                ['statusId' => $statuses[0]->id]
            ) instanceof StatusInterface
        ) {
            $discoverMoreRecentStatuses = true;
        }

        if (
            $discoverPastTweets
            && (
                $discoverMoreRecentStatuses
                || (count($statuses) === 0))
        ) {
            if (array_key_exists('max_id', $options)) {
                unset($options['max_id']);
            }

            $statuses = $this->fetchLatestStatuses(
                $options,
                $discoverPastTweets = false
            );
        }

        return $statuses;
    }

    /**
     * @return bool
     * @throws OptimisticLockException
     */
    protected function isApiAvailable()
    {
        $availableApi = false;

        if (!$this->apiAccessor->isApiLimitReached()) {
            return true;
        }

        try {
            if (!$this->apiAccessor->isApiRateLimitReached('/statuses/user_timeline')) {
                $availableApi = true;
            }
        } catch (Exception $exception) {
            $this->twitterApiLogger->info('[error message] Testing for API availability: ' . $exception->getMessage());
            $this->twitterApiLogger->info('[error code] ' . (int) $exception->getCode());

            if ($exception->getCode() === $this->apiAccessor->getEmptyReplyErrorCode()) {
                $availableApi = true;
            } else {
                $this->tokenRepository->freezeToken($this->apiAccessor->userToken);
            }
        }

        return $availableApi;
    }

    /**
     * @param Token $token
     *
     * @return bool
     * @throws OptimisticLockException
     * @throws Exception
     */
    protected function isApiAvailableForToken(Token $token)
    {
        $this->setupAccessor(
            [
                TokenInterface::FIELD_TOKEN  => $token->getOauthToken(),
                TokenInterface::FIELD_SECRET => $token->getOauthTokenSecret()
            ]
        );

        return $this->isApiAvailable();
    }

    /**
     * @return bool
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     */
    protected function isTwitterApiAvailable()
    {
        $availableApi = false;

        /**
         * @var \App\Api\Entity\Token $token
         */
        $token = $this->tokenRepository->refreshFreezeCondition($this->apiAccessor->userToken, $this->logger);

        if ($token->isNotFrozen()) {
            $availableApi = $this->isApiAvailable();
        }

        $token = $this->tokenRepository->findFirstUnfrozenToken();

        if (!$availableApi && !is_null($token)) {
            $frozenUntil = $token->getFrozenUntil();
            if (is_null($frozenUntil)) {
                return true;
            }

            $now        = new \DateTime('now', new \DateTimeZone('UTC'));
            $timeout    = $frozenUntil->getTimestamp() - $now->getTimestamp();
            $oauthToken = $token->getOauthToken();

            $availableApi = $this->isApiAvailableForToken($token);
            while (!$availableApi && ($token = $this->tokenRepository->findFirstUnfrozenToken()) !== null) {
                $availableApi = $this->isApiAvailableForToken($token);
                if (!$availableApi) {
                    $timeout = min(abs($timeout), abs($token->getFrozenUntil()->getTimestamp() - $now->getTimestamp()));
                }

                $oauthToken = $token->getOauthToken();
            }

            if (!$availableApi) {
                $this->logger->info('The API is not available right now.');
                $this->moderator->waitFor(
                    $timeout,
                    [
                        '{{ token }}' => substr($oauthToken, 0, '8'),
                    ]
                );
            }
        }

        if (!$availableApi) {
            return $this->delayingConsumption();
        }

        return $availableApi;
    }

    /**
     * @param $options
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    protected function logHowManyItemsHaveBeenCollected($options)
    {
        $subjectInSingularForm = 'status';
        $subjectInPluralForm   = 'statuses';
        $countCollectedItems   = function ($memberName, $maxId) {
            return $this->statusRepository->countCollectedStatuses($memberName, $maxId);
        };
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            $subjectInSingularForm = 'like';
            $subjectInPluralForm   = 'likes';
            $countCollectedItems   = function ($memberName, $maxId) {
                return $this->likedStatusRepository->countCollectedLikes($memberName, $maxId);
            };
        }

        if (!array_key_exists('max_id', $options)) {
            $options['max_id'] = INF;
        }

        $totalStatuses = $countCollectedItems(
            $options['screen_name'],
            $options['max_id']
        );

        if (is_infinite($options['max_id'])) {
            $maxId = "+infinity";
        } else {
            $maxId = $options['max_id'];
        }

        $this->logger->info(
            sprintf(
                '%d %s older than %s of id #%d have been found for "%s"',
                $totalStatuses,
                $subjectInPluralForm,
                $subjectInSingularForm,
                $maxId,
                $options['screen_name']
            )
        );

        return $totalStatuses;
    }

    /**
     * @param $options
     * @param $lastSerializationBatchSize
     * @param $totalSerializedStatuses
     */
    protected function logSerializationProgress(
        $options,
        $lastSerializationBatchSize,
        $totalSerializedStatuses
    ): void {
        $subject = 'statuses';
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            $subject = 'likes';
        }

        if ($this->serializedAllAvailableStatuses($lastSerializationBatchSize, $totalSerializedStatuses)) {
            $this->logger->info(
                sprintf(
                    'All available %s have most likely been fetched for "%s" or few %s are available (%d)',
                    $subject,
                    $options['screen_name'],
                    $subject,
                    $totalSerializedStatuses
                )
            );

            return;
        }

        $this->logger->info(
            sprintf(
                '%d more %s in the past have been saved for "%s" in aggregate #%d',
                $lastSerializationBatchSize,
                $subject,
                $options['screen_name'],
                $this->collectingOptions['aggregate_id']
            )
        );
    }

    /**
     * @param $options
     *
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     */
    protected function remainingItemsToCollect($options)
    {
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return $this->remainingLikes($options);
        }

        return $this->remainingStatuses($options);
    }

    /**
     * @param $options
     *
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws Exception
     */
    protected function remainingLikes($options)
    {
        $serializedLikesCount = $this->likedStatusRepository->countHowManyLikesFor($options['screen_name']);
        $existingStatus       = $this->translator->trans(
            'logs.info.likes_existing',
            [
                'total_likes' => $serializedLikesCount,
                'count'       => $serializedLikesCount,
                'member'      => $options['screen_name'],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $member = $this->apiAccessor->showUser($options['screen_name']);
        if (!isset($member->statuses_count)) {
            $member->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $likesCount      = max($member->statuses_count, self::MAX_AVAILABLE_TWEETS_PER_USER);
        $discoveredLikes = $this->translator->trans(
            'logs.info.likes_discovered',
            [
                'total_likes' => $likesCount,
                'member'      => $options['screen_name'],
                'count'       => $likesCount,
            ],
            'logs'
        );
        $this->logger->info($discoveredLikes);

        return $serializedLikesCount < $likesCount;
    }

    /**
     * @param $options
     *
     * @return bool
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws NoResultException
     */
    protected function remainingStatuses($options)
    {
        $serializedStatusCount = $this->statusRepository->countHowManyStatusesFor($options['screen_name']);
        $existingStatus        = $this->translator->trans(
            'logs.info.status_existing',
            [
                'count'        => $serializedStatusCount,
                'total_status' => $serializedStatusCount,
                'member'       => $options['screen_name'],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $user = $this->apiAccessor->showUser($options['screen_name']);
        if (!isset($user->statuses_count)) {
            $user->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $statusesCount    = max($user->statuses_count, self::MAX_AVAILABLE_TWEETS_PER_USER);
        $discoveredStatus = $this->translator->trans(
            'logs.info.status_discovered',
            [
                'member'       => $options['screen_name'],
                'count'        => $statusesCount,
                'total_status' => $statusesCount,
            ],
            'logs'
        );
        $this->logger->info($discoveredStatus);

        return $serializedStatusCount < $statusesCount;
    }

    /**
     * @param $options
     *
     * @return mixed
     */
    protected function removeSerializationOptions($options)
    {
        if (array_key_exists('before', $options)) {
            unset($options['before']);
        }
        if (array_key_exists('aggregate_id', $options)) {
            unset($options['aggregate_id']);
        }

        return $options;
    }

    /**
     * @param          $options
     * @param int|null $aggregateId
     *
     * @return int|null
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    protected function saveStatusesMatchingCriteria($options, int $aggregateId = null)
    {
        $options  = $this->declareOptionsToCollectStatuses($options);
        $statuses = $this->apiAccessor->fetchStatuses($options);

        if ($statuses instanceof \stdClass && isset($statuses->error)) {
            throw new ProtectedAccountException(
                $statuses->error,
                $this->apiAccessor::ERROR_PROTECTED_ACCOUNT
            );
        }

        $lookingForStatusesBetweenPublicationTimeOfLastOneSavedAndNow =
            $this->isLookingForStatusesBetweenPublicationTimeOfLastOneSavedAndNow($options);

        if (count($statuses) > 0) {
            $this->safelyDeclareExtremum(
                $statuses,
                $lookingForStatusesBetweenPublicationTimeOfLastOneSavedAndNow,
                $options['screen_name']
            );
        }

        $statusesIds   = $this->getExtremeStatusesIdsFor($options);
        $firstStatusId = $statusesIds['min_id'];
        $lastStatusId  = $statusesIds['max_id'];
        if (
            !$lookingForStatusesBetweenPublicationTimeOfLastOneSavedAndNow
            && $firstStatusId !== null
            && $lastStatusId !== null
            && count($statuses) > 0
            && ($statuses[count($statuses) - 1]->id >= (int) $firstStatusId)
            && ($statuses[0]->id <= (int) $lastStatusId)
        ) {
            return 0;
        }

        return $this->saveStatusesForScreenName(
            $statuses,
            $options['screen_name'],
            $aggregateId
        );
    }

    /**
     * @param $options
     *
     * @return mixed
     */
    protected function setUpSerializationOptions($options)
    {
        $foundOption = false;

        if (array_key_exists('aggregate_id', $options)) {
            $this->collectingOptions['aggregate_id'] = $options['aggregate_id'];
            $foundOption                             = true;
        } else {
            $this->collectingOptions['aggregate_id'] = null;
        }

        if (array_key_exists('before', $options)) {
            $this->collectingOptions['before'] = $options['before'];
            $foundOption                       = true;
        } else {
            $this->collectingOptions['before'] = null;
        }

        if (array_key_exists(self::INTENT_TO_FETCH_LIKES, $options)) {
            $this->collectingOptions[self::INTENT_TO_FETCH_LIKES] = $options[self::INTENT_TO_FETCH_LIKES];
        } else {
            $this->collectingOptions[self::INTENT_TO_FETCH_LIKES] = false;
        }

        return $foundOption;
    }

    /**
     * @param $options
     *
     * @return bool
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws DBALException
     * @throws InconsistentTokenRepository
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    protected function shouldSkipSerialization($options): bool
    {
        if (
            !array_key_exists(FetchMemberStatuses::SCREEN_NAME, $options)
            || $options[FetchMemberStatuses::SCREEN_NAME] === null
            || $this->apiAccessor->shouldSkipSerializationForMemberWithScreenName(
                $options[FetchMemberStatuses::SCREEN_NAME]
            )
        ) {
            return true;
        }

        $aggregate = null;
        if (array_key_exists('aggregate_id', $this->collectingOptions)) {
            $aggregate = $this->publicationListRepository->findOneBy(
                ['id' => $this->collectingOptions['aggregate_id']]
            );
        }

        if (
            ($aggregate instanceof Aggregate)
            && $aggregate->isLocked()
            && !array_key_exists('before', $options)
        ) {
            $message = sprintf(
                'Will skip message consumption for locked aggregate #%d',
                $aggregate->getId()
            );
            $this->logger->info($message);

            return true;
        }

        try {
            $whisperer = $this->beforeFetchingStatuses($options);
        } catch (SkippableMessageException $exception) {
            return $exception->shouldSkipMessageConsumption;
        }

        $statuses = $this->fetchLatestStatuses($options);
        if (count($statuses) > 0 && $whisperer instanceof Whisperer) {
            try {
                $this->afterCountingCollectedStatuses(
                    $options,
                    $statuses,
                    $whisperer
                );
            } catch (SkippableMessageException $exception) {
                return $exception->shouldSkipMessageConsumption;
            }
        }

        if ($this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            $atLeastOneStatusFetched = count($statuses) > 0;

            $hasLikedStatusBeenSavedBefore = false;
            if ($atLeastOneStatusFetched && $aggregate instanceof Aggregate) {
                $hasLikedStatusBeenSavedBefore = $this->likedStatusRepository->hasBeenSavedBefore(
                    $statuses[0],
                    $aggregate->getName(),
                    $options['screen_name'],
                    $statuses[0]->user->screen_name
                );
            }

            if ($atLeastOneStatusFetched && !$hasLikedStatusBeenSavedBefore) {
                // At this point, it should not skip further consumption
                // for matching liked statuses
                $this->saveStatusesForScreenName(
                    $statuses,
                    $options['screen_name'],
                    $options['aggregate_id']
                );

                $this->statusRepository->declareMinimumLikedStatusId(
                    $statuses[count($statuses) - 1],
                    $options['screen_name']
                );
            }

            if (!$atLeastOneStatusFetched || $hasLikedStatusBeenSavedBefore) {
                $statuses = $this->fetchLatestStatuses($options, $discoverPastTweets = false);
                if (count($statuses) > 0) {
                    if (
                    $this->statusRepository->hasBeenSavedBefore(
                        [$statuses[0]]
                    )
                    ) {
                        return true;
                    }

                    // At this point, it should not skip further consumption
                    // for matching liked statuses
                    $this->saveStatusesForScreenName(
                        $statuses,
                        $options['screen_name'],
                        $options['aggregate_id']
                    );

                    $this->statusRepository->declareMaximumLikedStatusId(
                        $statuses[0],
                        $options['screen_name']
                    );
                }

                return true;
            }

            return false;
        }

        if (!$this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            try {
                $this->statusRepository->updateLastStatusPublicationDate($options['screen_name']);
            } catch (NotFoundStatusException $exception) {
                $this->logger->info($exception->getMessage());
            }
        }

        if ($whisperer instanceof Whisperer) {
            $this->afterUpdatingLastPublicationDate($options, $whisperer);
        }

        return true;
    }

    /**
     * @param array $options
     * @param bool  $discoverPastTweets
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    protected function updateExtremum(array $options, bool $discoverPastTweets = true)
    {
        if (
            array_key_exists(
                FetchMemberStatuses::BEFORE,
                $this->collectingOptions
            )
            && $this->collectingOptions[FetchMemberStatuses::BEFORE]
        ) {
            $discoverPastTweets = true;
        }

        $options = $this->getExtremumOptions($options, $discoverPastTweets);

        $findingDirection = $this->getExtremumUpdateMethod($discoverPastTweets);
        $status           = $this->findExtremum($options, $findingDirection);

        $logPrefix = $this->getLogPrefix();

        if ((count($status) === 1) && array_key_exists('statusId', $status)) {
            $option = $this->getExtremumOption($discoverPastTweets);
            $shift  = $this->getShiftFromExtremum($discoverPastTweets);

            if ($status['statusId'] === '-INF' && $option === 'max_id') {
                $status['statusId'] = 0;
            }

            $options[$option] = (int) $status['statusId'] + $shift;

            $this->logger->info(
                sprintf(
                    'Extremum (%s%s) retrieved for "%s": #%s',
                    $logPrefix,
                    $option,
                    $options['screen_name'],
                    $options[$option]
                )
            );

            if ($options[$option] < 0 && $option === 'max_id') {
                unset($options[$option]);
            }

            return $options;
        }

        $this->logger->info(
            sprintf(
                '[No %s retrieved for "%s"] ',
                $logPrefix . 'extremum',
                $options['screen_name']
            )
        );

        return $options;
    }

    /**
     * @param array     $options
     * @param array     $statuses
     * @param Whisperer $whisperer
     *
     * @throws SkippableMessageException
     */
    private function afterCountingCollectedStatuses(
        array $options,
        array $statuses,
        Whisperer $whisperer
    ) {
        $aggregateId = $this->extractAggregateIdFromOptions($options);

        if ($statuses === 0) {
            SkippableMessageException::stopMessageConsumption();
        }

        if ($this->statusRepository->hasBeenSavedBefore($statuses)) {
            $this->logger->info(
                sprintf(
                    'The item with id "%d" has already been saved in the past (skipping the whole batch from "%s")',
                    $statuses[0]->id_str,
                    $options['screen_name']
                )
            );
            SkippableMessageException::stopMessageConsumption();
        }

        $savedItems = $this->saveStatusesForScreenName(
            $statuses,
            $options['screen_name'],
            $aggregateId
        );

        if (count($statuses) < self::MAX_BATCH_SIZE || is_null($savedItems)) {
            SkippableMessageException::stopMessageConsumption();
        }

        $isNotAboutCollectingLikes = !$this->isAboutToCollectLikesFromCriteria($options);
        if ($isNotAboutCollectingLikes) {
            $this->whispererRepository->forgetAboutWhisperer($whisperer);
        }

        SkippableMessageException::continueMessageConsumption();
    }

    /**
     * @param $options
     * @param $whisperer
     *
     * @throws OptimisticLockException
     */
    private function afterUpdatingLastPublicationDate($options, Whisperer $whisperer): void
    {
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return;
        }

        if ($whisperer->getExpectedWhispers() === 0) {
            $this->whispererRepository->declareWhisperer(
                $whisperer->setExpectedWhispers(
                    $whisperer->member->statuses_count
                )
            );
        }

        $whisperer->setExpectedWhispers($whisperer->member->statuses_count);
        $this->whispererRepository->saveWhisperer($whisperer);

        $this->logger->info(sprintf('Skipping whisperer "%s"', $options['screen_name']));
    }

    private function beforeFetchingStatuses($options)
    {
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return null;
        }

        $whisperer = $this->whispererRepository->findOneBy(
            ['name' => $options['screen_name']]
        );
        if (!$whisperer instanceof Whisperer) {
            SkippableMessageException::continueMessageConsumption();
        }

        $whisperer->member = $this->apiAccessor->showUser($options['screen_name']);
        $whispers          = (int) $whisperer->member->statuses_count;

        $storedWhispers = $this->statusRepository->countHowManyStatusesFor($options['screen_name']);

        if ($storedWhispers === $whispers) {
            SkippableMessageException::stopMessageConsumption();
        }

        if (
            $whispers >= self::MAX_AVAILABLE_TWEETS_PER_USER
            && $storedWhispers < self::MAX_AVAILABLE_TWEETS_PER_USER
        ) {
            SkippableMessageException::continueMessageConsumption();
        }

        return $whisperer;
    }

    /**
     * @param array $options
     *
     * @throws ProtectedAccountException
     * @throws RateLimitedException
     * @throws SkipSerializationException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws NonUniqueResultException
     */
    private function decideWhetherSerializationShouldBeSkipped(array $options)
    {
        try {
            if ($this->shouldSkipSerialization($options)) {
                throw new SkipSerializationException('Skipped pretty naturally ^_^');
            }
        } catch (SuspendedAccountException
        |NotFoundMemberException
        |ProtectedAccountException $exception
        ) {
            $this->handleUnavailableMemberException($exception, $options);
        } catch (SkipSerializationException $exception) {
            throw $exception;
        } catch (BadAuthenticationDataException $exception) {
            $this->logger->error(
                sprintf(
                    'The provided tokens have come to expire (%s).',
                    $exception->getMessage()
                )
            );

            throw new SkipSerializationException('Skipped because of bad authentication credentials');
        } catch (ApiRateLimitingException $exception) {
            $this->delayingConsumption();

            throw new RateLimitedException('No more call to the API can be made.');
        } catch (UnavailableResourceException|Exception $exception) {
            $this->logger->error(
                sprintf(
                    'An error occurred when checking if a serialization could be skipped ("%s")',
                    $exception->getMessage()
                )
            );

            throw new SkipSerializationException(
                'Skipped because Twitter sent error message and codes never dealt with so far'
            );
        }
    }

    /**
     * @param array  $statuses
     * @param bool   $shouldDeclareMaximumStatusId
     * @param string $memberName
     *
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     */
    private function declareExtremumIdForMember(
        array $statuses,
        bool $shouldDeclareMaximumStatusId,
        string $memberName
    ) {
        if (count($statuses) === 0) {
            throw new \LogicException('There should be at least one status');
        }

        if ($this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            if ($shouldDeclareMaximumStatusId) {
                $lastStatusFetched = $statuses[0];

                return $this->statusRepository->declareMaximumLikedStatusId(
                    $lastStatusFetched,
                    $memberName
                );
            }

            $firstStatusFetched = $statuses[count($statuses) - 1];

            return $this->statusRepository->declareMinimumLikedStatusId(
                $firstStatusFetched,
                $memberName
            );
        }

        if ($shouldDeclareMaximumStatusId) {
            $lastStatusFetched = $statuses[0];

            return $this->statusRepository->declareMaximumStatusId($lastStatusFetched);
        }

        $firstStatusFetched = $statuses[count($statuses) - 1];

        return $this->statusRepository->declareMinimumStatusId($firstStatusFetched);
    }

    /**
     * @param $options
     *
     * @return mixed
     */
    private function declareOptionsToCollectStatuses($options)
    {
        if (array_key_exists('max_id', $options) && is_infinite($options['max_id'])) {
            unset($options['max_id']);
        }

        $options[self::INTENT_TO_FETCH_LIKES] = $this->isAboutToCollectLikesFromCriteria($this->collectingOptions);

        return $options;
    }

    /**
     * @return bool
     * @throws Exception
     */
    private function delayingConsumption(): bool
    {
        $token = $this->tokenRepository->findFirstFrozenToken();

        /** @var \DateTime $frozenUntil */
        $frozenUntil = $token->getFrozenUntil();
        $now         = new \DateTime('now', $frozenUntil->getTimezone());

        $timeout = $frozenUntil->getTimestamp() - $now->getTimestamp();

        $this->logger->info('The API is not available right now.');
        $this->moderator->waitFor(
            $timeout,
            [
                '{{ token }}' => substr($token->getOauthToken(), 0, '8'),
            ]
        );

        return true;
    }

    private function ensureTargetAggregateIsNotLocked(): void
    {
        if ($this->isCollectingStatusesForAggregate()) {
            $aggregate = $this->publicationListRepository->findOneBy(
                ['id' => $this->collectingOptions['aggregate_id']]
            );

            if (!$aggregate instanceof Aggregate) {
                return;
            }

            if ($aggregate->isLocked()) {
                $message = sprintf(
                    'Won\'t process message for locked aggregate #%d',
                    $aggregate->getId()
                );
                $this->logger->info($message);

                throw new LockedAggregateException($message);
            }

            $this->logger->info(
                sprintf(
                    'About to lock processing of aggregate #%d',
                    $aggregate->getId()
                )
            );

            $this->publicationListRepository->lockAggregate($aggregate);
        }
    }

    /**
     * @param $options
     *
     * @return int|null
     */
    private function extractAggregateIdFromOptions($options): ?int
    {
        if (!array_key_exists('aggregate_id', $options)) {
            return null;
        }

        return $options['aggregate_id'];
    }

    /**
     * @param $options
     * @param $findingDirection
     *
     * @return array|mixed
     * @throws NonUniqueResultException
     */
    private function findExtremum($options, $findingDirection)
    {
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return $this->findLikeExtremum($options, $findingDirection);
        }

        if (
            !array_key_exists('before', $this->collectingOptions)
            || !$this->collectingOptions['before']
        ) {
            return $this->statusRepository->findLocalMaximum(
                $options['screen_name'],
                $this->collectingOptions['before']
            );
        }

        return $this->statusRepository->findNextExtremum(
            $options['screen_name'],
            $findingDirection
        );
    }

    /**
     * @param $options
     * @param $findingDirection
     *
     * @return array|mixed
     * @throws NonUniqueResultException
     */
    private function findLikeExtremum($options, $findingDirection)
    {
        if (
            !array_key_exists('before', $this->collectingOptions)
            || !$this->collectingOptions['before']
        ) {
            return $this->likedStatusRepository->findLocalMaximum(
                $options['screen_name'],
                $this->collectingOptions['before']
            );
        }

        return $this->likedStatusRepository->findNextExtremum
        (
            $options['screen_name'],
            $findingDirection
        );
    }

    /**
     * @param $options
     *
     * @return array
     */
    private function getExtremeStatusesIdsFor($options): array
    {
        if ($this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            return $this->likedStatusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
                $options['screen_name']
            );
        }

        return $this->statusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
            $options['screen_name']
        );
    }

    /**
     * @param $options
     * @param $discoverPastTweets
     *
     * @return string
     */
    private function getExtremumOption($discoverPastTweets): string
    {
        if ($discoverPastTweets) {
            return 'max_id';
        }

        return 'since_id';
    }

    /**
     * @param $options
     * @param $discoverPastTweets
     *
     * @return array
     */
    private function getExtremumOptions($options, $discoverPastTweets): array
    {
        if (!$discoverPastTweets && array_key_exists('max_id', $options)) {
            unset($options['max_id']);
        }

        return $options;
    }

    /**
     * @param $discoverPastTweets
     *
     * @return string
     */
    private function getExtremumUpdateMethod($discoverPastTweets): string
    {
        if ($discoverPastTweets) {
            // next maximum
            return ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER;
        }

        // next minimum
        return ExtremumAwareInterface::FINDING_IN_DESCENDING_ORDER;
    }

    /**
     * @return string
     */
    private function getLogPrefix(): string
    {
        if (
            !array_key_exists('before', $this->collectingOptions)
            || !$this->collectingOptions['before']
        ) {
            return '';
        }

        return 'local ';
    }

    /**
     * @param $discoverPastTweets
     *
     * @return int
     */
    private function getShiftFromExtremum($discoverPastTweets): int
    {
        if ($discoverPastTweets) {
            return -1;
        }

        return 1;
    }

    /**
     * @param Exception  $exception
     * @param            $options
     *
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    private function handleUnavailableMemberException(Exception $exception, array $options): void
    {
        $message = 'Skipping member with screen name "%s", which has not been found';

        if ($exception instanceof SuspendedAccountException) {
            $message = 'Skipping member with screen name "%s", which has been suspended';
        }

        if ($exception instanceof ProtectedAccountException) {
            $message = 'Skipping member with screen name "%s", which is protected';
            $this->logger->error(sprintf($message, $options['screen_name']));

            throw $exception;
        }

        $message = sprintf($message, $options['screen_name']);
        $this->logger->error($message);

        throw new UnavailableResourceException($message, $exception->getCode(), $exception);
    }

    /**
     * @param $options
     *
     * @return bool
     */
    private function isLookingForStatusesBetweenPublicationTimeOfLastOneSavedAndNow($options): bool
    {
        if (array_key_exists('max_id', $options) && is_infinite($options['max_id'])) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function isCollectingStatusesForAggregate(): bool
    {
        return array_key_exists('aggregate_id', $this->collectingOptions);
    }

    /**
     * @param        $statuses
     * @param        $shouldDeclareMaximumStatusId
     * @param string $memberName
     *
     * @throws NotFoundMemberException
     * @throws UnavailableResourceException
     * @throws OptimisticLockException
     */
    private function safelyDeclareExtremum(
        $statuses,
        $shouldDeclareMaximumStatusId,
        string $memberName
    ): void {
        try {
            $this->declareExtremumIdForMember(
                $statuses,
                $shouldDeclareMaximumStatusId,
                $memberName
            );
        } catch (NotFoundMemberException $exception) {
            $this->apiAccessor->ensureMemberHavingNameExists($exception->screenName);

            try {
                $this->declareExtremumIdForMember(
                    $statuses,
                    $shouldDeclareMaximumStatusId,
                    $memberName
                );
            } catch (NotFoundMemberException $exception) {
                $this->apiAccessor->ensureMemberHavingNameExists($exception->screenName);
                $this->declareExtremumIdForMember(
                    $statuses,
                    $shouldDeclareMaximumStatusId,
                    $memberName
                );
            }
        }
    }

    /**
     * @param array                $statuses
     * @param Aggregate|null       $aggregate
     * @param MemberInterface|null $likedBy
     *
     * @return CollectionInterface
     */
    private function saveStatuses(
        array $statuses,
        Aggregate $aggregate = null,
        MemberInterface $likedBy = null
    ): CollectionInterface {
        if ($this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            return $this->statusRepository->saveLikes(
                $statuses,
                $this->apiAccessor->getOAuthToken(),
                $aggregate,
                $this->logger,
                $likedBy,
                function ($memberName) {
                    return $this->apiAccessor->ensureMemberHavingNameExists($memberName);
                }
            );
        }

        return $this->publicationPersistence->persistStatusPublications(
            $statuses,
            new AccessToken($this->apiAccessor->getOAuthToken()),
            $aggregate
        );
    }

    /**
     * @param array    $statuses
     * @param string   $screenName
     * @param int|null $aggregateId
     *
     * @return int|null
     */
    private function saveStatusesForScreenName(
        array $statuses,
        string $screenName,
        int $aggregateId = null
    ): ?int {
        $success = null;

        if (!is_array($statuses) || count($statuses) === 0) {
            return $success;
        }

        $aggregate = null;
        if ($aggregateId !== null) {
            /** @var Aggregate $aggregate */
            $aggregate = $this->publicationListRepository->findOneBy(['id' => $aggregateId]);
        }

        $this->collectStatusLogger->logHowManyItemsHaveBeenFetched(
            $statuses,
            $screenName
        );

        $likedBy = null;
        if ($this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            $likedBy = $this->apiAccessor->ensureMemberHavingNameExists($screenName);
        }

        $savedStatuses = $this->saveStatuses($statuses, $aggregate, $likedBy);

        return $this->collectStatusLogger->logHowManyItemsHaveBeenSaved(
            $savedStatuses->count(),
            $screenName,
            $this->isAboutToCollectLikesFromCriteria($this->collectingOptions)
        );
    }

    /**
     * @param Token $token
     * @param       $options
     *
     * @return array
     * @throws Exception
     */
    private function setUpAccessorWithFirstAvailableToken(Token $token, $options): array
    {
        $options[self::MESSAGE_OPTION_TOKEN] = $token->getOauthToken();
        $this->setupAccessor(
            [
                TokenInterface::FIELD_TOKEN  => $options[self::MESSAGE_OPTION_TOKEN],
                TokenInterface::FIELD_SECRET => $token->getOauthTokenSecret()
            ]
        );

        return $options;
    }

    /**
     * @param string $memberName
     *
     * @return bool
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    private function shouldLookUpFutureItems(string $memberName): bool
    {
        if ($this->isAboutToCollectLikesFromCriteria($this->collectingOptions)) {
            return $this->likedStatusRepository->countHowManyLikesFor($memberName)
                > self::MAX_AVAILABLE_TWEETS_PER_USER;
        }

        return $this->statusRepository->countHowManyStatusesFor($memberName)
            > self::MAX_AVAILABLE_TWEETS_PER_USER;
    }

    /**
     * @param $options
     * @param $greedy
     * @param $discoverPastTweets
     *
     * @return bool
     * @throws ApiRateLimitingException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws NotFoundStatusException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     */
    private function trySerializingFurther($options, $greedy, $discoverPastTweets): bool
    {
        $success = true;

        $this->collectStatusLogger->logIntentionWithRegardsToAggregate(
            $options,
            $this->collectingOptions['aggregate_id']
        );

        $lastSerializationBatchSize = $this->saveStatusesMatchingCriteria(
            $options,
            $this->collectingOptions['aggregate_id']
        );

        if (
            $discoverPastTweets
            || (
                $lastSerializationBatchSize !== null &&
                $lastSerializationBatchSize === self::MAX_BATCH_SIZE
            )
        ) {
            // When some of the last batch of statuses have been serialized for the first time,
            // and we should discover status in the past,
            // keep retrieving status in the past
            // otherwise start serializing status never seen before,
            // which have been more recently published
            $discoverPastTweets = $lastSerializationBatchSize !== null && $discoverPastTweets;
            if ($greedy) {
                $options['aggregate_id'] = $this->collectingOptions['aggregate_id'];
                $options['before']       = $this->collectingOptions['before'];

                $success = $this->collect($options, $greedy, $discoverPastTweets);

                $justDiscoveredFutureTweets = !$discoverPastTweets;
                if ($justDiscoveredFutureTweets && is_null($this->collectingOptions['before'])) {
                    unset($options['aggregate_id']);

                    $options = $this->updateExtremum($options, $discoverPastTweets = false);
                    $options = $this->apiAccessor->guessMaxId(
                        $options,
                        $this->shouldLookUpFutureItems($options['screen_name'])
                    );

                    $lastSerializationBatchSize = $this->saveStatusesMatchingCriteria(
                        $options,
                        $this->collectingOptions['aggregate_id']
                    );

                    $totalSerializedStatuses = $this->logHowManyItemsHaveBeenCollected($options);
                    $this->logSerializationProgress(
                        $options,
                        $lastSerializationBatchSize,
                        $totalSerializedStatuses
                    );

                    $this->flagWhisperers(
                        $options['screen_name'],
                        $lastSerializationBatchSize,
                        $totalSerializedStatuses
                    );
                }
            }
        }

        return $success;
    }

    private function unlockAggregate(): void
    {
        if ($this->isCollectingStatusesForAggregate()) {
            $aggregate = $this->publicationListRepository->findOneBy(
                ['id' => $this->collectingOptions['aggregate_id']]
            );
            if ($aggregate instanceof Aggregate) {
                $this->publicationListRepository->unlockAggregate($aggregate);
                $this->logger->info(sprintf('Unlocked aggregate #%d', $aggregate->getId()));
            }
        }
    }
}
