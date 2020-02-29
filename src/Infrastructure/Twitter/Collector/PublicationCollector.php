<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Aggregate\Exception\LockedAggregateException;
use App\Api\Entity\Token;
use App\Api\Entity\TokenInterface;
use App\Domain\Collection\CollectionStrategy;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Publication\PublicationListInterface;
use App\Infrastructure\Amqp\Message\FetchPublication;
use App\Infrastructure\DependencyInjection\{Api\ApiAccessorTrait,
    Api\ApiLimitModeratorTrait,
    Api\StatusAccessorTrait,
    Collection\InterruptibleCollectDeciderTrait,
    LoggerTrait,
    Membership\WhispererIdentificationTrait,
    Membership\WhispererRepositoryTrait,
    Publication\PublicationListRepositoryTrait,
    Publication\PublicationPersistenceTrait,
    Status\LikedStatusRepositoryTrait,
    Status\StatusLoggerTrait,
    Status\StatusPersistenceTrait,
    Status\StatusRepositoryTrait,
    TokenRepositoryTrait};
use App\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Infrastructure\Twitter\Collector\Exception\RateLimitedException;
use App\Infrastructure\Twitter\Collector\Exception\SkipCollectException;
use App\Membership\Entity\MemberInterface;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Psr\Log\LoggerInterface;
use ReflectionException;
use stdClass;
use function array_key_exists;
use function count;

class PublicationCollector implements PublicationCollectorInterface
{
    use ApiAccessorTrait;
    use ApiLimitModeratorTrait;
    use LoggerTrait;
    use InterruptibleCollectDeciderTrait;
    use PublicationListRepositoryTrait;
    use PublicationPersistenceTrait;
    use StatusLoggerTrait;
    use StatusPersistenceTrait;
    use LikedStatusRepositoryTrait;
    use StatusAccessorTrait;
    use StatusRepositoryTrait;
    use TokenRepositoryTrait;
    use TranslatorTrait;
    use WhispererIdentificationTrait;
    use WhispererRepositoryTrait;

    private const MESSAGE_OPTION_TOKEN = 'oauth';

    /**
     * @var LoggerInterface
     */
    public LoggerInterface $twitterApiLogger;

    private CollectionStrategyInterface $collectionStrategy;

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
     * @throws Exception
     */
    public function collect(array $options, $greedy = false, $discoverPastTweets = true): bool
    {
        $success = false;

        $this->collectionStrategy = CollectionStrategy::fromArray($options);

        try {
            $this->interruptibleCollectDecider->decideWhetherCollectShouldBeSkipped(
                $this->collectionStrategy,
                $options
            );
        } catch (SkipCollectException $exception) {
            if ($exception instanceof RateLimitedException) {
                unset($exception);

                return false; // unsuccessfully made an attempt to collect statuses
            }

            return true;
        }

        if ($this->collectionStrategy->oneOfTheOptionsIsActive()) {
            $options = $this->removeCollectOptions(
                $options
            );

            try {
                $this->ensureTargetAggregateIsNotLocked();
            } catch (LockedAggregateException $exception) {
                unset($exception);

                return true;
            }
        }

        if (
            !$this->isTwitterApiAvailable()
            && ($remainingItemsToCollect = $this->remainingItemsToCollect($options))
        ) {
            $this->unlockAggregate();

            /**
             * Marks the collect as successful if there are no remaining status
             * or when Twitter API is not available
             */
            return isset($remainingItemsToCollect) ?: false;
        }

        if ($this->shouldLookUpFutureItems($options[FetchPublication::SCREEN_NAME])) {
            $discoverPastTweets = false;
        }

        $options = $this->statusAccessor->updateExtremum(
            $this->collectionStrategy,
            $options,
            $discoverPastTweets
        );

        try {
            $success = $this->tryCollectingFurther($options, $greedy, $discoverPastTweets);
        } catch (BadAuthenticationDataException $exception) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
            if (!($token instanceof Token)) {
                return false;
            }

            $options = $this->setUpAccessorWithFirstAvailableToken($token, $options);
            $success = $this->tryCollectingFurther($options, $greedy, $discoverPastTweets);
        } catch (SuspendedAccountException|NotFoundMemberException|ProtectedAccountException $exception) {
            UnavailableResourceException::handleUnavailableMemberException(
                $exception,
                $this->logger,
                $options
            );

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
     * @param $lastCollectionBatchSize
     * @param $totalCollectedStatuses
     *
     * @return bool
     */
    public function collectedAllAvailableStatuses($lastCollectionBatchSize, $totalCollectedStatuses): bool
    {
        return !$this->justCollectedSomeStatuses($lastCollectionBatchSize)
            && $this->hitCollectionLimit($totalCollectedStatuses);
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function hitCollectionLimit($statuses): bool
    {
        return $statuses >= (CollectionStrategyInterface::MAX_AVAILABLE_TWEETS_PER_USER - 100);
    }

    /**
     * @param $statuses
     *
     * @return bool
     */
    public function justCollectedSomeStatuses($statuses): bool
    {
        return $statuses !== null && $statuses > 0;
    }

    /**
     * @param array $options
     *
     * @return mixed
     */
    public function removeCollectOptions(
        $options
    ) {
        if ($this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            unset($options[FetchPublication::BEFORE]);
        }
        if (array_key_exists(FetchPublication::AGGREGATE_ID, $options)) {
            unset($options[FetchPublication::AGGREGATE_ID]);
        }

        return $options;
    }

    /**
     * @param array $oauthTokens
     *
     * @return $this
     */
    public function setupAccessor(array $oauthTokens): self
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
        $token = $this->tokenRepository->findOneBy(
            ['oauthToken' => $oauthTokens[TokenInterface::FIELD_TOKEN]]
        );

        if (!$token instanceof Token) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
        }

        $this->apiAccessor->setConsumerKey($token->consumerKey);
        $this->apiAccessor->setConsumerSecret($token->consumerSecret);

        return $this;
    }

    /**
     * @return bool
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
                TokenInterface::FIELD_TOKEN  => $token->getOAuthToken(),
                TokenInterface::FIELD_SECRET => $token->getOAuthSecret()
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
            return $this->interruptibleCollectDecider->delayingConsumption();
        }

        return $availableApi;
    }

    /**
     * @param $options
     * @param $lastCollectionBatchSize
     * @param $totalCollectedStatuses
     */
    protected function logCollectionProgress(
        $options,
        $lastCollectionBatchSize,
        $totalCollectedStatuses
    ): void {
        $subject = 'statuses';
        if ($this->collectionStrategy->fetchLikes()) {
            $subject = 'likes';
        }

        if ($this->collectedAllAvailableStatuses($lastCollectionBatchSize, $totalCollectedStatuses)) {
            $this->logger->info(
                sprintf(
                    'All available %s have most likely been fetched for "%s" or few %s are available (%d)',
                    $subject,
                    $options[FetchPublication::SCREEN_NAME],
                    $subject,
                    $totalCollectedStatuses
                )
            );

            return;
        }

        $this->logger->info(
            sprintf(
                '%d more %s in the past have been saved for "%s" in aggregate #%d',
                $lastCollectionBatchSize,
                $subject,
                $options[FetchPublication::SCREEN_NAME],
                $this->collectionStrategy->publicationListId()
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
        if ($this->collectionStrategy->fetchLikes()) {
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
        $serializedLikesCount =
            $this->likedStatusRepository->countHowManyLikesFor($options[FetchPublication::SCREEN_NAME]);
        $existingStatus       = $this->translator->trans(
            'logs.info.likes_existing',
            [
                'total_likes' => $serializedLikesCount,
                'count'       => $serializedLikesCount,
                'member'      => $options[FetchPublication::SCREEN_NAME],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $member = $this->apiAccessor->showUser($options[FetchPublication::SCREEN_NAME]);
        if (!isset($member->statuses_count)) {
            $member->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $likesCount      = max($member->statuses_count, CollectionStrategyInterface::MAX_AVAILABLE_TWEETS_PER_USER);
        $discoveredLikes = $this->translator->trans(
            'logs.info.likes_discovered',
            [
                'total_likes' => $likesCount,
                'member'      => $options[FetchPublication::SCREEN_NAME],
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
        $serializedStatusCount =
            $this->statusRepository->countHowManyStatusesFor($options[FetchPublication::SCREEN_NAME]);
        $existingStatus        = $this->translator->trans(
            'logs.info.status_existing',
            [
                'count'        => $serializedStatusCount,
                'total_status' => $serializedStatusCount,
                'member'       => $options[FetchPublication::SCREEN_NAME],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $user = $this->apiAccessor->showUser($options[FetchPublication::SCREEN_NAME]);
        if (!isset($user->statuses_count)) {
            $user->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $statusesCount    = max($user->statuses_count, CollectionStrategyInterface::MAX_AVAILABLE_TWEETS_PER_USER);
        $discoveredStatus = $this->translator->trans(
            'logs.info.status_discovered',
            [
                'member'       => $options[FetchPublication::SCREEN_NAME],
                'count'        => $statusesCount,
                'total_status' => $statusesCount,
            ],
            'logs'
        );
        $this->logger->info($discoveredStatus);

        return $serializedStatusCount < $statusesCount;
    }

    /**
     * @param                             $options
     * @param CollectionStrategyInterface $collectionStrategy
     *
     * @return int|null
     * @throws NotFoundMemberException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws UnavailableResourceException
     */
    protected function saveStatusesMatchingCriteria(
        $options,
        CollectionStrategyInterface $collectionStrategy
    ): ?int {
        $options  = $this->declareOptionsToCollectStatuses($options);
        $statuses = $this->apiAccessor->fetchStatuses($options);

        if ($statuses instanceof stdClass && isset($statuses->error)) {
            throw new ProtectedAccountException(
                $statuses->error,
                $this->apiAccessor::ERROR_PROTECTED_ACCOUNT
            );
        }

        $betweenPublicationDateOfLastOneSavedAndNow = $this->isLookingBetweenPublicationDateOfLastOneSavedAndNow($options);

        if (count($statuses) > 0) {
            $this->safelyDeclareExtremum(
                $statuses,
                $betweenPublicationDateOfLastOneSavedAndNow,
                $options[FetchPublication::SCREEN_NAME]
            );
        }

        $statusesIds   = $this->getExtremeStatusesIdsFor($options);
        $firstStatusId = $statusesIds['min_id'];
        $lastStatusId  = $statusesIds['max_id'];

        if (
            !$betweenPublicationDateOfLastOneSavedAndNow
            && $firstStatusId !== null
            && $lastStatusId !== null
            && count($statuses) > 0
            && ($statuses[count($statuses) - 1]->id >= (int) $firstStatusId)
            && ($statuses[0]->id <= (int) $lastStatusId)
        ) {
            return 0;
        }

        $lastCollectionBatchSize = $this->statusPersistence->saveStatusForScreenName(
            $statuses,
            $options[FetchPublication::SCREEN_NAME],
            $collectionStrategy
        );

        $totalCollectedStatuses = $this->logHowManyItemsHaveBeenCollected(
            $options,
            $lastCollectionBatchSize
        );

        $this->whispererIdentification->identifyWhisperer(
            $options[FetchPublication::SCREEN_NAME],
            $totalCollectedStatuses,
            $lastCollectionBatchSize
        );
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
            throw new \LogicException(
                'There should be at least one status'
            );
        }

        if ($this->collectionStrategy->fetchLikes()) {
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

        $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES] = $this->collectionStrategy->fetchLikes();

        return $options;
    }

    private function ensureTargetAggregateIsNotLocked(): void
    {
        if (!$this->isCollectingStatusesForAggregate()) {
            return;
        }

        $publicationList = $this->publicationListRepository->findOneBy(
            ['id' => $this->collectionStrategy->publicationListId()]
        );

        if (!$publicationList instanceof PublicationListInterface) {
            return;
        }

        if ($publicationList->isLocked()) {
            $message = sprintf(
                'Won\'t process message for locked aggregate #%d',
                $publicationList->getId()
            );
            $this->logger->info($message);

            throw new LockedAggregateException($message);
        }

        $this->logger->info(
            sprintf(
                'About to lock processing of aggregate #%d',
                $publicationList->getId()
            )
        );

        $this->publicationListRepository->lockAggregate($publicationList);
    }

    /**
     * @param $options
     *
     * @return array
     */
    private function getExtremeStatusesIdsFor($options): array
    {
        if ($this->collectionStrategy->fetchLikes()) {
            return $this->likedStatusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
                $options[FetchPublication::SCREEN_NAME]
            );
        }

        return $this->statusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
            $options[FetchPublication::SCREEN_NAME]
        );
    }

    /**
     * @return bool
     */
    private function isCollectingStatusesForAggregate(): bool
    {
        return $this->collectionStrategy->publicationListId() !== null;
    }

    /**
     * @param $options
     *
     * @return bool
     */
    private function isLookingBetweenPublicationDateOfLastOneSavedAndNow($options): bool
    {
        return array_key_exists('max_id', $options) && is_infinite($options['max_id']);
    }

    /**
     * @param array $options
     *
     * @param int   $lastCollectionBatchSize
     *
     * @return mixed
     */
    private function logHowManyItemsHaveBeenCollected(
        array $options,
        ?int $lastCollectionBatchSize
    ) {
        $this->collectionStrategy->optInToCollectStatusFor($options[FetchPublication::SCREEN_NAME]);
        $this->collectionStrategy->optInToCollectStatusWhichIdIsLessThan($options['max_id']);

        $subjectInSingularForm = 'status';
        $subjectInPluralForm   = 'statuses';
        $countCollectedItems   = function ($memberName, $maxId) {
            return $this->statusRepository->countCollectedStatuses($memberName, $maxId);
        };
        if ($this->collectionStrategy->fetchLikes()) {
            $subjectInSingularForm = 'like';
            $subjectInPluralForm   = 'likes';
            $countCollectedItems   = function ($memberName, $maxId) {
                return $this->likedStatusRepository->countCollectedLikes($memberName, $maxId);
            };
        }

        $totalStatuses = $countCollectedItems(
            $this->collectionStrategy->screenName(),
            $options['max_id']
        );

        $this->collectStatusLogger->logHowManyItemsHaveBeenCollected(
            $this->collectionStrategy,
            (int) $totalStatuses,
            [
                'plural'   => $subjectInPluralForm,
                'singular' => $subjectInSingularForm
            ],
            (int) $lastCollectionBatchSize
        );

        return $totalStatuses;
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
     * @param Token $token
     * @param array $options
     *
     * @return array
     * @throws Exception
     */
    private function setUpAccessorWithFirstAvailableToken(
        Token $token,
        array $options
    ): array {
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
        if ($this->collectionStrategy->fetchLikes()) {
            return $this->likedStatusRepository->countHowManyLikesFor($memberName)
                > CollectionStrategyInterface::MAX_AVAILABLE_TWEETS_PER_USER;
        }

        return $this->statusRepository->countHowManyStatusesFor($memberName)
            > CollectionStrategyInterface::MAX_AVAILABLE_TWEETS_PER_USER;
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
    private function tryCollectingFurther($options, $greedy, $discoverPastTweets): bool
    {
        $success = true;

        $this->collectStatusLogger->logIntentionWithRegardsToAggregate(
            $options,
            $this->collectionStrategy
        );

        $lastCollectionBatchSize = $this->saveStatusesMatchingCriteria(
            $options,
            $this->collectionStrategy
        );

        if (
            $discoverPastTweets
            || (
                $lastCollectionBatchSize !== null
                && $lastCollectionBatchSize === CollectionStrategyInterface::MAX_BATCH_SIZE
            )
        ) {
            // When some of the last batch of statuses have been serialized for the first time,
            // and we should discover status in the past,
            // keep retrieving status in the past
            // otherwise start serializing status never seen before,
            // which have been more recently published
            $discoverPastTweets = $lastCollectionBatchSize !== null && $discoverPastTweets;
            if ($greedy) {
                $options[FetchPublication::AGGREGATE_ID] = $this->collectionStrategy->publicationListId();
                $options[FetchPublication::BEFORE]       =
                    $this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected();

                $success = $this->collect($options, $greedy, $discoverPastTweets);

                $justDiscoveredFutureTweets = !$discoverPastTweets;
                if (
                    $justDiscoveredFutureTweets
                    && $this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected() === null
                ) {
                    unset($options[FetchPublication::AGGREGATE_ID]);

                    $options = $this->statusAccessor->updateExtremum(
                        $this->collectionStrategy,
                        $options,
                        $discoverPastTweets = false
                    );
                    $options = $this->apiAccessor->guessMaxId(
                        $options,
                        $this->shouldLookUpFutureItems($options[FetchPublication::SCREEN_NAME])
                    );

                    $this->saveStatusesMatchingCriteria(
                        $options,
                        $this->collectionStrategy
                    );
                }
            }
        }

        return $success;
    }

    private function unlockAggregate(): void
    {
        if ($this->isCollectingStatusesForAggregate()) {
            $publicationList = $this->publicationListRepository->findOneBy(
                ['id' => $this->collectionStrategy->publicationListId()]
            );
            if ($publicationList instanceof PublicationListInterface) {
                $this->publicationListRepository->unlockAggregate($publicationList);
                $this->logger->info(
                    sprintf(
                        'Unlocked publication list of id #%d',
                        $publicationList->getId()
                    )
                );
            }
        }
    }
}
