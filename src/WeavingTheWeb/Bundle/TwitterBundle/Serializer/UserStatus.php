<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Serializer;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Amqp\Exception\SkippableMessageException;
use App\Member\MemberInterface;
use App\Status\LikedStatusCollectionAwareInterface;

use App\Accessor\Exception\NotFoundStatusException;
use App\Aggregate\Exception\LockedAggregateException;
use App\Status\Repository\LikedStatusRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Translation\TranslatorInterface;

use WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate;
use WeavingTheWeb\Bundle\ApiBundle\Entity\Token,
    WeavingTheWeb\Bundle\ApiBundle\Entity\Whisperer;

use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException;

/**
 * @package WeavingTheWeb\Bundle\TwitterBundle\Accessor
 */
class UserStatus implements LikedStatusCollectionAwareInterface
{
    const MAX_AVAILABLE_TWEETS_PER_USER = 3200;

    const MAX_BATCH_SIZE = 200;

    /**
     * @var \Symfony\Component\Translation\Translator $translator
     */
    public $translator;

    /**
     * @param $translator
     * @return $this
     */
    public function setTranslator(TranslatorInterface $translator)
    {
        $this->translator = $translator;

        return $this;
    }

    /**
     * @var \WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor $accessor
     */
    protected $accessor;

    /**
     * @param $accessor
     * @return $this
     */
    public function setAccessor($accessor)
    {
        $this->accessor = $accessor;

        return $this;
    }

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository $statusRepository
     */
    protected $statusRepository;

    /**
     * @var LikedStatusRepository
     */
    public $likedStatusRepository;

    /**
     * @param $statusRepository
     * @return $this
     */
    public function setStatusRepository($statusRepository)
    {
        $this->statusRepository = $statusRepository;

        return $this;
    }

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\WhispererRepository $whispererRepository
     */
    protected $whispererRepository;

    /**
     * @param $whispererRepository
     * @return $this
     */
    public function setWhispererRepository($whispererRepository)
    {
        $this->whispererRepository = $whispererRepository;

        return $this;
    }

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var LoggerInterface
     */
    public $twitterApiLogger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Moderator\ApiLimitModerator $moderator
     */
    protected $moderator;

    /**
     * @param $moderator
     * @return $this
     */
    public function setModerator($moderator)
    {
        $this->moderator = $moderator;

        return $this;
    }

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\TokenRepository $tokenRepository
     */
    protected $tokenRepository;

    /**
     * @param $tokenRepository
     * @return $this
     */
    public function setTokenRepository($tokenRepository)
    {
        $this->tokenRepository = $tokenRepository;

        return $this;
    }

    /**
     * @var array
     */
    protected $serializationOptions = [];

    /**
     * @param $oauthTokens
     * @return $this
     * @throws \Exception
     */
    public function setupAccessor($oauthTokens)
    {
        if (!array_key_exists('authentication_header', $oauthTokens)) {
            $this->accessor->setUserToken($oauthTokens['token']);
            $this->accessor->setUserSecret($oauthTokens['secret']);

            /** @var Token token */
            $token = $this->tokenRepository->findOneBy(['oauthToken' => $oauthTokens['token']]);

            if (! $token instanceof Token) {
                throw new \Exception('Invalid token');
            }

            $this->accessor->setConsumerKey($token->consumerKey);
            $this->accessor->setConsumerSecret($token->consumerSecret);
        } else {
            $this->accessor->setAuthenticationHeader($oauthTokens['authentication_header']);
        }

        return $this;
    }

    /**
     * @param      $options
     * @param bool $greedy
     * @param bool $discoverPastTweets
     * @return bool
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function serialize($options, $greedy = false, $discoverPastTweets = true)
    {
        $successfulSerializationOptionSetup = $this->setUpSerializationOptions($options);

        try {
            if ($this->shouldSkipSerialization($options)) {
                return true;
            }
        } catch (UnavailableResourceException
            |SuspendedAccountException
            |NotFoundMemberException
            |ProtectedAccountException $exception
        ) {
            $this->handleUnavailableMemberException($exception, $options);
        } catch (ApiRateLimitingException $exception) {
            $this->delayingConsumption();

            return false;
        } catch (\Exception $exception) {
            $this->logger->error(
                sprintf(
                    'An error occurred when checking if a serialization could be skipped ("%s")',
                    $exception->getMessage()
                )
            );

            return false;
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

        if (!$this->isTwitterApiAvailable() &&
            ($remainingItemsToCollect = $this->remainingItemsToCollect($options))
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
            $this->logIntentionWithRegardsToAggregate($options);

            $lastSerializationBatchSize = $this->saveStatusesMatchingCriteria(
                $options,
                $this->serializationOptions['aggregate_id']
            );
            $success = true;

            if ($discoverPastTweets || (
                !is_null($lastSerializationBatchSize) && $lastSerializationBatchSize == self::MAX_BATCH_SIZE
            )) {
                // When some of the last batch of statuses have been serialized for the first time,
                // and we should discover statuses in the past,
                // keep retrieving statuses in the past
                // otherwise start serializing statuses never seen before,
                // which have been more recently published
                $discoverPastTweets = !is_null($lastSerializationBatchSize) && $discoverPastTweets;
                if ($greedy) {
                    $options['aggregate_id'] = $this->serializationOptions['aggregate_id'];
                    $options['before'] = $this->serializationOptions['before'];

                    $success = $this->serialize($options, $greedy, $discoverPastTweets);

                    $justDiscoveredFutureTweets = !$discoverPastTweets;
                    if ($justDiscoveredFutureTweets && is_null($this->serializationOptions['before'])) {
                        unset($options['aggregate_id']);

                        $options = $this->updateExtremum($options, $discoverPastTweets = false);
                        $options = $this->accessor->guessMaxId(
                            $options,
                            $this->shouldLookUpFutureItems($options['screen_name'])
                        );

                        $lastSerializationBatchSize = $this->saveStatusesMatchingCriteria(
                            $options,
                            $this->serializationOptions['aggregate_id']
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
        } catch (UnavailableResourceException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            $this->logger->error('[' . $exception->getMessage() . ']');
            $success = false;
        } finally {
            $this->unlockAggregate();
        }

        return $success;
    }

    /**
     * @param $options
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function shouldSkipSerialization($options)
    {
        if ($this->accessor->shouldSkipSerializationForMemberWithScreenName($options['screen_name'])) {
            return true;
        }

        $aggregate = null;
        if (array_key_exists('aggregate_id', $this->serializationOptions)) {
            $aggregate = $this->aggregateRepository->findOneBy(
                ['id' => $this->serializationOptions['aggregate_id']]
            );
        }

        if (($aggregate instanceof Aggregate) &&
            $aggregate->isLocked() &&
            !array_key_exists('before', $options)
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

        if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
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
                if (count($statuses) > 0 ) {
                    if ($this->statusRepository->hasBeenSavedBefore(
                        [$statuses[0]]
                    )) {
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

        if (!$this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
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
     * @param $screenName
     * @param $lastSerializationBatchSize
     * @param $totalSerializedStatuses
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    public function flagWhisperers($screenName, $lastSerializationBatchSize, $totalSerializedStatuses)
    {
        $flaggedWhisperer = false;

        if (! $this->justSerializedSomeStatuses($lastSerializationBatchSize)) {
            $whisperer = new Whisperer($screenName, $totalSerializedStatuses);

            $member = $this->accessor->showUser($screenName);
            $whisperer->setExpectedWhispers($member->statuses_count);

            $this->whispererRepository->declareWhisperer($whisperer);
            $whispererDeclarationMessage = $this->translator->trans(
                'logs.info.whisperer_declared',
                ['{{ screen name }}' => $screenName],
                'logs'
            );
            $this->logger->info($whispererDeclarationMessage);
            $flaggedWhisperer = true;
        }

        return $flaggedWhisperer;
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function isTwitterApiAvailable()
    {
        $availableApi = false;

        /**
         * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Token $token
         */
        $token = $this->tokenRepository->refreshFreezeCondition($this->accessor->userToken, $this->logger);

        if ($token->isNotFrozen()) {
            $availableApi = $this->isApiAvailable();
        }

        $token = $this->tokenRepository->findFirstUnfrozenToken();

        if (!$availableApi && !is_null($token)) {
            $frozenUntil = $token->getFrozenUntil();
            if (is_null($frozenUntil)) {
                return true;
            }

            $now = new \DateTime();
            $timeout = $frozenUntil->getTimestamp() - $now->getTimestamp();
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
                $this->moderator->waitFor($timeout, [
                    '{{ token }}' => substr($oauthToken, 0, '8'),
                ]);
            }
        }

        if (!$availableApi) {
            return $this->delayingConsumption();
        }

        return $availableApi;
    }

    /**
     * @param Token $token
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function isApiAvailableForToken(Token $token) {
        $this->setupAccessor(['token' => $token->getOauthToken(), 'secret' => $token->getOauthTokenSecret()]);

        return $this->isApiAvailable();
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function isApiAvailable()
    {
        $availableApi = false;

        if (!$this->accessor->isApiLimitReached()) {
            return true;
        }

        try {
            if (!$this->accessor->isApiRateLimitReached('/statuses/user_timeline')) {
                $availableApi = true;
            }
        } catch (\Exception $exception) {
            $this->twitterApiLogger->info('[error message] Testing for API availability: '.$exception->getMessage());
            $this->twitterApiLogger->info('[error code] '.(int)$exception->getCode());

            if ($exception->getCode() === $this->accessor->getEmptyReplyErrorCode()) {
                $availableApi = true;
            } else {
                $this->tokenRepository->freezeToken($this->accessor->userToken);
            }
        }

        return $availableApi;
    }

    /**
     * @param      $options
     * @param bool $discoverPastTweets
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function updateExtremum($options, $discoverPastTweets = true)
    {
        if (array_key_exists('before', $this->serializationOptions) &&
            $this->serializationOptions['before']
        ) {
            $discoverPastTweets = true;
        }

        $options = $this->getExtremumOptions($options, $discoverPastTweets);
        $updateMethod = $this->getExtremumUpdateMethod($discoverPastTweets);

        $status = $this->findExtremum($options, $updateMethod);

        $logPrefix = $this->getLogPrefix();

        if ((count($status) === 1) && array_key_exists('statusId', $status)) {
            $option = $this->getExtremumOption($discoverPastTweets);
            $shift = $this->getShiftFromExtremum($discoverPastTweets);
            $options[$option] = $status['statusId'] + $shift;

            $this->logger->info(sprintf(
                'Extremum (%s%s) retrieved for "%s": #%s',
                $logPrefix, $option, $options['screen_name'], $options[$option]
            ));

            if ($options[$option] < 0 && $option === 'max_id') {
                unset($options[$option]);
            }

            return $options;
        }

        $this->logger->info(sprintf(
            '[No %s retrieved for "%s"] ',
            $logPrefix . 'extremum', $options['screen_name']
        ));

        return $options;
    }

    /**
     * @param $options
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
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
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function remainingLikes($options)
    {
        $serializedLikesCount = $this->likedStatusRepository->countHowManyLikesFor($options['screen_name']);
        $existingStatus = $this->translator->transChoice(
            'logs.info.likes_existing',
            $serializedLikesCount,
            [
                '{{ count }}' => $serializedLikesCount,
                '{{ user }}' => $options['screen_name'],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $member = $this->accessor->showUser($options['screen_name']);
        if (!isset($member->statuses_count)) {
            $member->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $likesCount = max($member->statuses_count, self::MAX_AVAILABLE_TWEETS_PER_USER);
        $discoveredLikes = $this->translator->transChoice(
            'logs.info.likes_discovered',
            $member->statuses_count, [
                '{{ user }}' => $options['screen_name'],
                '{{ count }}' => $likesCount,
            ],
            'logs'
        );
        $this->logger->info($discoveredLikes);

        return $serializedLikesCount < $likesCount;
    }

    /**
     * @param $options
     * @return bool
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Exception
     */
    protected function remainingStatuses($options)
    {
        $serializedStatusCount = $this->statusRepository->countHowManyStatusesFor($options['screen_name']);
        $existingStatus = $this->translator->transChoice(
            'logs.info.status_existing',
            $serializedStatusCount,
            [
                '{{ count }}' => $serializedStatusCount,
                '{{ user }}' => $options['screen_name'],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $user = $this->accessor->showUser($options['screen_name']);
        if (!isset($user->statuses_count)) {
            $user->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $statusesCount = max($user->statuses_count, self::MAX_AVAILABLE_TWEETS_PER_USER);
        $discoveredStatus = $this->translator->transChoice(
            'logs.info.status_discovered',
            $user->statuses_count, [
                '{{ user }}' => $options['screen_name'],
                '{{ count }}' => $statusesCount,
            ],
            'logs'
        );
        $this->logger->info($discoveredStatus);

        return $serializedStatusCount < $statusesCount;
    }

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository $aggregateRepository
     */
    protected $aggregateRepository;

    /**
     * @param $aggregateRepository
     */
    public function setAggregateRepository($aggregateRepository)
    {
        $this->aggregateRepository = $aggregateRepository;
    }

    /**
     * @param      $options
     * @param null $aggregateId
     * @return int|null
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function saveStatusesMatchingCriteria($options, $aggregateId = null)
    {
        $options = $this->declareOptionsToCollectStatuses($options);
        $statuses = $this->accessor->fetchStatuses($options);

        if ($statuses instanceof \stdClass && isset($statuses->error)) {
            throw new ProtectedAccountException(
                $statuses->error,
                $this->accessor::ERROR_PROTECTED_ACCOUNT
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

        $statusesIds = $this->getExtremeStatusesIdsFor($options);
        $firstStatusId = $statusesIds['min_id'];
        $lastStatusId = $statusesIds['max_id'];
        if (!$lookingForStatusesBetweenPublicationTimeOfLastOneSavedAndNow &&
            !is_null($firstStatusId) &&
            !is_null($lastStatusId) &&
            count($statuses) > 0 &&
            ($statuses[count($statuses) - 1]->id >= intval($firstStatusId)) &&
            ($statuses[count($statuses) - 1]->id <= intval($lastStatusId))
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
     * @return mixed
     */
    private function declareOptionsToCollectStatuses($options)
    {
        if (array_key_exists('max_id', $options) && is_infinite($options['max_id'])) {
            unset($options['max_id']);
        }

        $options[self::INTENT_TO_FETCH_LIKES] = $this->isAboutToCollectLikesFromCriteria($this->serializationOptions);

        return $options;
    }

    /**
     * @param array $criteria
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
     * @param $options
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
     * @param array  $statuses
     * @param bool   $shouldDeclareMaximumStatusId
     * @param string $memberName
     * @return MemberInterface
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function declareExtremumIdForMember(
        array $statuses,
        bool $shouldDeclareMaximumStatusId,
        string $memberName
    ) {
        if (count($statuses) === 0) {
            throw new \LogicException('There should be at least one status');
        }

        if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
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
     * @return mixed
     */
    protected function setUpSerializationOptions($options)
    {
        $foundOption = false;

        if (array_key_exists('aggregate_id', $options)) {
            $this->serializationOptions['aggregate_id'] = $options['aggregate_id'];
            $foundOption = true;
        } else {
            $this->serializationOptions['aggregate_id'] = null;
        }

        if (array_key_exists('before', $options)) {
            $this->serializationOptions['before'] = $options['before'];
            $foundOption = true;
        } else {
            $this->serializationOptions['before'] = null;
        }

        if (array_key_exists(self::INTENT_TO_FETCH_LIKES, $options)) {
            $this->serializationOptions[self::INTENT_TO_FETCH_LIKES] = $options[self::INTENT_TO_FETCH_LIKES];
        } else {
            $this->serializationOptions[self::INTENT_TO_FETCH_LIKES] = false;
        }

        return $foundOption;
    }

    /**
     * @param $options
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
     * @param $options
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function logHowManyItemsHaveBeenCollected($options)
    {
        $subjectInSingularForm = 'status';
        $subjectInPluralForm = 'statuses';
        $countCollectedItems = function ($memberName, $maxId) {
            return $this->statusRepository->countCollectedStatuses($memberName, $maxId);
        };
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            $subjectInSingularForm = 'like';
            $subjectInPluralForm = 'likes';
            $countCollectedItems = function ($memberName, $maxId) {
                return $this->likedStatusRepository->countCollectedLikes($memberName, $maxId);
            };
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
    protected function logSerializationProgress($options, $lastSerializationBatchSize, $totalSerializedStatuses)
    {
        $subject = 'statuses';
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            $subject = 'likes';
        }

        if ($this->serializedAllAvailableStatuses($lastSerializationBatchSize, $totalSerializedStatuses)) {
            $this->logger->info(
                sprintf(
                    'All available %s have most likely not been fetched for "%s" or few %s are available (%d)',
                    $subject,
                    $options['screen_name'],
                    $subject,
                    $totalSerializedStatuses
                )
            );
        } else {
            $this->logger->info(
                sprintf(
                    '%d more %s in the past have been saved for "%s" in aggregate #%d',
                    $lastSerializationBatchSize,
                    $subject,
                    $options['screen_name'],
                    $this->serializationOptions['aggregate_id']
                )
            );
        }
    }

    /**
     * @param $statuses
     * @return bool
     */
    public function hitSerializationLimit($statuses)
    {
        return $statuses >= (self::MAX_AVAILABLE_TWEETS_PER_USER - 100);
    }

    /**
     * @param $statuses
     * @return bool
     */
    public function justSerializedSomeStatuses($statuses)
    {
        return ! is_null($statuses) && $statuses > 0;
    }

    /**
     * @param $lastSerializationBatchSize
     * @param $totalSerializedStatuses
     * @return bool
     */
    public function serializedAllAvailableStatuses($lastSerializationBatchSize, $totalSerializedStatuses)
    {
        return ! $this->justSerializedSomeStatuses($lastSerializationBatchSize) &&
            $this->hitSerializationLimit($totalSerializedStatuses);
    }

    /**
     * @return bool
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function delayingConsumption(): bool
    {
        $token = $this->tokenRepository->findFirstFrozenToken();

        /** @var \DateTime $frozenUntil */
        $frozenUntil = $token->getFrozenUntil();
        $now = new \DateTime('now', $frozenUntil->getTimezone());

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

    /**
     * @param array  $statuses
     * @param string $screenName
     * @param int    $aggregateId
     * @return int|null
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function saveStatusesForScreenName(
        array $statuses,
        string $screenName,
        int $aggregateId
    ) {
        $success = null;

        if (is_array($statuses) && count($statuses) > 0) {
            if (is_null($aggregateId)) {
                $aggregate = null;
            } else {
                /** @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Aggregate $aggregate */
                $aggregate = $this->aggregateRepository->find($aggregateId);
            }

            $this->logger->info(sprintf(
                'Fetched "%d" statuses for "%s"',
                count($statuses),
                $screenName)
            );

            $likedBy = null;
            if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
                $likedBy = $this->accessor->ensureMemberHavingNameExists($screenName);
            }
            $statuses = $this->saveStatuses($statuses, $aggregate, $likedBy);
            $success = $this->logHowManyItemsHaveBeenSaved(
                count($statuses),
                $screenName
            );
        }

        return $success;
    }

    /**
     * @param array          $statuses
     * @param Aggregate|null $aggregate
     * @return array
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function saveStatuses(
        array $statuses,
        Aggregate $aggregate = null,
        MemberInterface $likedBy = null
    ) {
        if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
            return $this->statusRepository->saveLikes(
                $statuses,
                $this->accessor->getUserToken(),
                $aggregate,
                $this->logger,
                $likedBy,
                function ($memberName) {
                    return $this->accessor->ensureMemberHavingNameExists($memberName);
                }
            );
        }

        return $this->statusRepository->saveStatuses(
            $statuses,
            $this->accessor->getUserToken(),
            $aggregate,
            $this->logger
        );
    }

    /**
     * @param      $options
     * @param bool $discoverPastTweets
     * @return array
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    protected function fetchLatestStatuses($options, $discoverPastTweets = true): array
    {
        $options[self::INTENT_TO_FETCH_LIKES] = $this->isAboutToCollectLikesFromCriteria($this->serializationOptions);
        $options = $this->removeSerializationOptions($options);
        $options = $this->updateExtremum($options, $discoverPastTweets);

        if (array_key_exists('max_id', $options) &&
            array_key_exists('before', $options) // Looking into the past
        ) {
            unset($options['max_id']);
        }

        return $this->accessor->fetchStatuses($options);
    }

    /**
     * @param $options
     * @return null
     */
    private function extractAggregateIdFromOptions($options)
    {
        if (!array_key_exists('aggregate_id', $options)) {
            return null;
        }

        return $options['aggregate_id'];
    }

    private function ensureTargetAggregateIsNotLocked(): void
    {
        if ($this->isSerializingStatusesForAggregate()) {
            $aggregate = $this->aggregateRepository->findOneBy(
                ['id' => $this->serializationOptions['aggregate_id']]
            );

            if (!$aggregate instanceof Aggregate ) {
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

            $this->logger->info(sprintf(
                'About to lock processing of aggregate #%d',
                $aggregate->getId()
            ));

            $this->aggregateRepository->lockAggregate($aggregate);
        }
    }

    /**
     * @return bool
     */
    private function isSerializingStatusesForAggregate(): bool
    {
        return array_key_exists('aggregate_id', $this->serializationOptions);
    }

    private function unlockAggregate(): void
    {
        if ($this->isSerializingStatusesForAggregate()) {
            $aggregate = $this->aggregateRepository->findOneBy(
                ['id' => $this->serializationOptions['aggregate_id']]
            );
            if ($aggregate instanceof Aggregate) {
                $this->aggregateRepository->unlockAggregate($aggregate);
                $this->logger->info(sprintf('Unlocked aggregate #%d', $aggregate->getId()));
            }
        }
    }

    /**
     * @param \Exception $exception
     * @param            $options
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     */
    private function handleUnavailableMemberException(\Exception $exception, array $options): void
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
     */
    private function logIntentionWithRegardsToAggregate($options): void
    {
        if (is_null($this->serializationOptions['aggregate_id'])) {
            $this->logger->info(sprintf('No aggregate id for "%s"', $options['screen_name']));
        } else {
            $this->logger->info(sprintf(
                'About to save status for "%s" in aggregate #%d',
                $options['screen_name'],
                $this->serializationOptions['aggregate_id']
            ));
        }
    }

    /**
     * @param string $memberName
     * @return bool
     * @throws NotFoundMemberException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function shouldLookUpFutureItems(string $memberName): bool
    {
        if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
            return $this->likedStatusRepository->countHowManyLikesFor($memberName)
                > self::MAX_AVAILABLE_TWEETS_PER_USER;
        }

        return $this->statusRepository->countHowManyStatusesFor($memberName)
            > self::MAX_AVAILABLE_TWEETS_PER_USER;
    }

    /**
     * @return string
     */
    private function getLogPrefix(): string
    {
        if (!array_key_exists('before', $this->serializationOptions)
            || ! $this->serializationOptions['before']
        ) {
            return '';
        }

        return 'local ';
    }

    /**
     * @param $options
     * @param $updateMethod
     * @return array|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function findExtremum($options, $updateMethod)
    {
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return $this->findLikeExtremum($options, $updateMethod);
        }

        if (!array_key_exists('before', $this->serializationOptions) ||
            !$this->serializationOptions['before']
        ) {
            return $this->statusRepository->findLocalMaximum(
                $options['screen_name'],
                $this->serializationOptions['before']
            );
        }


        return $this->statusRepository->$updateMethod($options['screen_name']);
    }

    /**
     * @param $options
     * @param $updateMethod
     * @return array|mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function findLikeExtremum($options, $updateMethod)
    {
        if (!array_key_exists('before', $this->serializationOptions) ||
            !$this->serializationOptions['before']
        ) {
            return $this->likedStatusRepository->findLocalMaximum(
                $options['screen_name'],
                $this->serializationOptions['before']
            );
        }


        return $this->likedStatusRepository->$updateMethod($options['screen_name']);
    }

    /**
     * @param $options
     * @param $discoverPastTweets
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
     * @return array
     */
    private function getExtremumOptions($options, $discoverPastTweets): array
    {
        if (!$discoverPastTweets) {
            unset($options['max_id']);
        }

        return $options;
    }

    /**
     * @param $discoverPastTweets
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
     * @param $discoverPastTweets
     * @return string
     */
    private function getExtremumUpdateMethod($discoverPastTweets): string
    {
        if ($discoverPastTweets) {
            return 'findNextMaximum';
        }

        return'findNextMininum';
    }

    /**
     * @param $options
     * @return null|Whisperer
     * @throws NotFoundMemberException
     * @throws SkippableMessageException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function beforeFetchingStatuses($options)
    {
        if ($this->isAboutToCollectLikesFromCriteria($options)) {
            return null;
        }

        $whisperer = $this->whispererRepository->findOneBy(['name' => $options['screen_name']]);
        if (!$whisperer instanceof Whisperer) {
            SkippableMessageException::continueMessageConsumption();
        }

        $whisperer->member = $this->accessor->showUser($options['screen_name']);
        $whispers = intval($whisperer->member->statuses_count);

        $storedWhispers = $this->statusRepository->countHowManyStatusesFor($options['screen_name']);

        if ($storedWhispers === $whispers) {
            SkippableMessageException::stopMessageConsumption();
        }

        if ($whispers >= self::MAX_AVAILABLE_TWEETS_PER_USER &&
            $storedWhispers < self::MAX_AVAILABLE_TWEETS_PER_USER
        ) {
            SkippableMessageException::continueMessageConsumption();
        }

        return $whisperer;
    }

    /**
     * @param array     $options
     * @param array     $statuses
     * @param Whisperer $whisperer
     * @throws SkippableMessageException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function afterCountingCollectedStatuses(
        array $options,
        array $statuses,
        Whisperer $whisperer
    ) {
        $aggregateId = $this->extractAggregateIdFromOptions($options);

        if ($statuses === 0)  {
            SkippableMessageException::stopMessageConsumption();
        }

        if ($this->statusRepository->hasBeenSavedBefore($statuses)) {
            $this->logger->info(sprintf(
                'The item with id "%d" has already been saved in the past (skipping the whole batch from "%s")',
                $statuses[0]->id_str,
                $options['screen_name']
            ));
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
     * @throws \Doctrine\ORM\OptimisticLockException
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

    /**
     * @param int    $statusesCount
     * @param string $memberName
     * @return int|null
     */
    private function logHowManyItemsHaveBeenSaved(int $statusesCount, string $memberName)
    {
        if ($statusesCount > 0) {
            $success = $statusesCount;

            $messageKey = 'logs.info.status_saved';
            if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
                $messageKey = 'logs.info.likes_saved';
            }
            $savedTweets = $this->translator->transChoice(
                $messageKey,
                $statusesCount, [
                '{{ user }}' => $memberName,
                '{{ count }}' => $statusesCount,
            ],
                'logs'
            );
            $this->logger->info($savedTweets);

            return $success;
        }

        $this->logger->info(sprintf('Nothing new for "%s"', $memberName));

        return null;

    }

    /**
     * @param $options
     * @return array
     */
    private function getExtremeStatusesIdsFor($options): array
    {
        if ($this->isAboutToCollectLikesFromCriteria($this->serializationOptions)) {
            return $this->likedStatusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
                $options['screen_name']
            );
        }

        return $this->statusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
            $options['screen_name']
        );
    }

    /**
     * @param        $statuses
     * @param        $shouldDeclareMaximumStatusId
     * @param string $memberName
     * @throws NotFoundMemberException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
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
            $this->accessor->ensureMemberHavingNameExists($exception->screenName);

            try {
                $this->declareExtremumIdForMember(
                    $statuses,
                    $shouldDeclareMaximumStatusId,
                    $memberName
                );
            } catch (NotFoundMemberException $exception) {
                $this->accessor->ensureMemberHavingNameExists($exception->screenName);
                $this->declareExtremumIdForMember(
                    $statuses,
                    $shouldDeclareMaximumStatusId,
                    $memberName
                );
            }
        }
    }
}
