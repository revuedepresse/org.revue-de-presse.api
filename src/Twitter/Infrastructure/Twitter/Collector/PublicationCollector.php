<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Collector;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ApiRateLimitingException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Api\Entity\Token;
use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Curation\CollectionStrategy;
use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Domain\Curation\Exception\NoRemainingPublicationException;
use App\Twitter\Domain\Publication\Exception\LockedPublishersListException;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Twitter\Infrastructure\DependencyInjection\{Api\ApiAccessorTrait,
    Api\ApiLimitModeratorTrait,
    Api\StatusAccessorTrait,
    Collection\InterruptibleCollectDeciderTrait,
    Collection\MemberProfileCollectedEventRepositoryTrait,
    Collection\PublicationBatchCollectedEventRepositoryTrait,
    LoggerTrait,
    Membership\MemberRepositoryTrait,
    Membership\WhispererIdentificationTrait,
    Membership\WhispererRepositoryTrait,
    Publication\PublishersListRepositoryTrait,
    Publication\PublicationPersistenceTrait,
    Status\LikedStatusRepositoryTrait,
    Status\StatusLoggerTrait,
    Status\StatusPersistenceTrait,
    Status\StatusRepositoryTrait,
    TokenRepositoryTrait};
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Twitter\Collector\Exception\RateLimitedException;
use App\Twitter\Infrastructure\Twitter\Collector\Exception\SkipCollectException;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Curation\LikedStatusCollectionAwareInterface;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\InconsistentTokenRepository;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use DateTimeImmutable;
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
    use MemberProfileCollectedEventRepositoryTrait;
    use MemberRepositoryTrait;
    use PublicationBatchCollectedEventRepositoryTrait;
    use PublishersListRepositoryTrait;
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
     * @param bool  $discoverPublicationsWithMaxId
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
    public function collect(
        array $options,
        $greedy = false,
        $discoverPublicationsWithMaxId = true
    ): bool {
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
        } finally {
            $this->updateLastStatusPublicationDate($options);
        }

        if ($this->collectionStrategy->oneOfTheOptionsIsActive()) {
            $options = $this->removeCollectOptions(
                $options
            );

            try {
                $this->lockPublishersList();
            } catch (LockedPublishersListException $exception) {
                $this->logger->info($exception->getMessage());

                return true;
            }
        }

        if (
            !$this->isTwitterApiAvailable()
            && ($remainingItemsToCollect = $this->remainingItemsToCollect($options))
        ) {
            $this->unlockPublishersList();

            /**
             * Marks the collect as successful when there is no remaining status
             * or when Twitter API is not available
             */
            return isset($remainingItemsToCollect) ?: false;
        }

        if ($this->collectionStrategy->shouldLookUpPublicationsWithMinId(
            $this->likedStatusRepository,
            $this->statusRepository,
            $this->memberRepository
        )) {
            $discoverPublicationsWithMaxId = false;
        }

        $options = $this->statusAccessor->updateExtremum(
            $this->collectionStrategy,
            $options,
            $discoverPublicationsWithMaxId
        );

        try {
            $success = $this->tryCollectingFurther(
                $options,
                $greedy,
                $discoverPublicationsWithMaxId
            );
        } catch (BadAuthenticationDataException $exception) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
            if (!($token instanceof Token)) {
                return false;
            }

            $options = $this->setUpAccessorWithFirstAvailableToken($token, $options);
            $success = $this->tryCollectingFurther($options, $greedy, $discoverPublicationsWithMaxId);
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
            $this->logger->critical(
                $constraintViolationException->getMessage(),
                ['stacktrace' => $constraintViolationException->getTraceAsString()]
            );
            $success = false;
        } catch (\Throwable $exception) {
            $this->logger->error(
                sprintf(
                    '[from %s %s]',
                    __METHOD__,
                    $exception->getMessage()
                ),
                ['stacktrace' => $exception->getTraceAsString()]
            );
            $success = false;
        } finally {
            $this->unlockPublishersList();
        }

        return $success;
    }

    /**
     * @param array $options
     */
    private function updateLastStatusPublicationDate(array $options): void
    {
        if (!$this->collectionStrategy->fetchLikes()) {
            try {
                $this->statusRepository->updateLastStatusPublicationDate(
                    $options[FetchPublicationInterface::SCREEN_NAME]
                );
            } catch (NotFoundStatusException $exception) {
                $this->logger->info($exception->getMessage());
            }
        }
    }

    /**
     * @param $lastCollectionBatchSize
     * @param $totalCollectedStatuses
     *
     * @return bool
     */
    public function collectedAllAvailableStatuses(
        $lastCollectionBatchSize,
        $totalCollectedStatuses
    ): bool {
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
        if ($this->collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()) {
            unset($options[FetchPublicationInterface::BEFORE]);
        }
        if (array_key_exists(FetchPublicationInterface::publishers_list_ID, $options)) {
            unset($options[FetchPublicationInterface::publishers_list_ID]);
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

    protected function guardAgainstNoRemainingPublicationToBeCollected(
        $options,
        bool $betweenPublicationDateOfLastOneSavedAndNow,
        $statuses
    ): void {
        $statusesIds   = $this->getExtremeStatusesIdsFor($options);
        $firstStatusId = $statusesIds['min_id'];
        $lastStatusId  = $statusesIds['max_id'];

        // When we didn't fetch publications between the last one saved and now,
        // both first and last status were declared
        // some publications were retrieved and
        // no boundaries were crosse
        if (
            !$betweenPublicationDateOfLastOneSavedAndNow
            && $firstStatusId !== null
            && $lastStatusId !== null
            && count($statuses) > 0
            && ($statuses[count($statuses) - 1]->id >= (int) $firstStatusId)
            && ($statuses[0]->id <= (int) $lastStatusId)
        ) {
            throw new NoRemainingPublicationException(
                'There is no remaining publication to be collected.'
            );
        }
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
     * @throws OptimisticLockException
     */
    protected function isTwitterApiAvailable(): bool
    {
        $availableApi = false;

        $token = $this->tokenRepository->refreshFreezeCondition(
            $this->apiAccessor->userToken,
            $this->logger
        );

        if ($token->isNotFrozen()) {
            $availableApi = $this->isApiAvailable();
        }

        $token = $this->tokenRepository->findFirstUnfrozenToken();

        if (!$availableApi && $token !== null) {
            $frozenUntil = $token->getFrozenUntil();
            if ($frozenUntil === null) {
                return true;
            }

            $now        = new DateTimeImmutable('now', new \DateTimeZone('UTC'));
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

    protected function remainingItemsToCollect(array $options): bool
    {
        if ($this->collectionStrategy->fetchLikes()) {
            return $this->remainingLikes($options);
        }

        return $this->remainingStatuses($options);
    }

    protected function remainingLikes(array $options): bool
    {
        $serializedLikesCount =
            $this->likedStatusRepository->countHowManyLikesFor($options[FetchPublicationInterface::SCREEN_NAME]);
        $existingStatus       = $this->translator->trans(
            'logs.info.likes_existing',
            [
                'total_likes' => $serializedLikesCount,
                'count'       => $serializedLikesCount,
                'member'      => $options[FetchPublicationInterface::SCREEN_NAME],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $member = $this->collectMemberProfile(
            $options[FetchPublicationInterface::SCREEN_NAME]
        );
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
                'member'      => $options[FetchPublicationInterface::SCREEN_NAME],
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
     */
    protected function remainingStatuses($options): bool
    {
        $serializedStatusCount = $this->statusRepository->countHowManyStatusesFor(
            $options[FetchPublicationInterface::SCREEN_NAME]
        );
        $existingStatus        = $this->translator->trans(
            'logs.info.status_existing',
            [
                'count'        => $serializedStatusCount,
                'total_status' => $serializedStatusCount,
                'member'       => $options[FetchPublicationInterface::SCREEN_NAME],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $memberProfile = $this->collectMemberProfile(
            $options[FetchPublicationInterface::SCREEN_NAME]
        );
        if (!isset($memberProfile->statuses_count)) {
            $memberProfile->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $statusesCount    = max(
            $memberProfile->statuses_count,
            CollectionStrategyInterface::MAX_AVAILABLE_TWEETS_PER_USER
        );
        $discoveredStatus = $this->translator->trans(
            'logs.info.status_discovered',
            [
                'member'       => $options[FetchPublicationInterface::SCREEN_NAME],
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
     * @throws ProtectedAccountException
     */
    protected function saveStatusesMatchingCriteria(
        $options,
        CollectionStrategyInterface $collectionStrategy
    ): ?int {
        $options  = $this->declareOptionsToCollectStatuses($options);
        $statuses = $this->publicationBatchCollectedEventRepository
            ->collectedPublicationBatch($collectionStrategy, $options);

        if ($statuses instanceof stdClass && isset($statuses->error)) {
            throw new ProtectedAccountException(
                $statuses->error,
                $this->apiAccessor::ERROR_PROTECTED_ACCOUNT
            );
        }

        $lookingBetweenLastPublicationAndNow = $this->isLookingBetweenPublicationDateOfLastOneSavedAndNow($options);

        /** @var array $statuses */
        if (count($statuses) > 0) {
            $this->safelyDeclareExtremum(
                $statuses,
                $lookingBetweenLastPublicationAndNow,
                $options[FetchPublicationInterface::SCREEN_NAME]
            );
        }

        try {
            $this->guardAgainstNoRemainingPublicationToBeCollected(
                $options,
                $lookingBetweenLastPublicationAndNow,
                $statuses
            );
        } catch (NoRemainingPublicationException $exception) {
            $this->logger->info($exception->getMessage());

            return 0;
        }

        $lastCollectionBatchSize = $this->statusPersistence->savePublicationsForScreenName(
            $statuses,
            $options[FetchPublicationInterface::SCREEN_NAME],
            $collectionStrategy
        );

        $this->whispererIdentification->identifyWhisperer(
            $collectionStrategy,
            $options,
            $options[FetchPublicationInterface::SCREEN_NAME],
            (int) $lastCollectionBatchSize
        );

        return $lastCollectionBatchSize;
    }

    private function collectMemberProfile(string $screenName)
    {
        $eventRepository = $this->memberProfileCollectedEventRepository;

        return $eventRepository->collectedMemberProfile(
            $this->apiAccessor,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );
    }

    /**
     * @param array  $statuses
     * @param bool   $shouldDeclareMaximumStatusId
     * @param string $memberName
     *
     * @return MemberInterface
     */
    private function declareExtremumIdForMember(
        array $statuses,
        bool $shouldDeclareMaximumStatusId,
        string $memberName
    ): MemberInterface {
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

    private function lockPublishersList(): void
    {
        if (!$this->isCollectingStatusesForAggregate()) {
            return;
        }

        $publishersList = $this->publishersListRepository->findOneBy(
            ['id' => $this->collectionStrategy->publishersListId()]
        );

        if (!$publishersList instanceof PublishersListInterface) {
            return;
        }

        if ($publishersList->isLocked()) {
            throw new LockedPublishersListException(
                'Won\'t process message for already locked aggregate #%d',
                $publishersList
            );
        }

        $this->logger->info(
            sprintf(
                'About to lock processing of publishers list #%d',
                $publishersList->getId()
            )
        );

        $this->publishersListRepository->lockAggregate($publishersList);
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
                $options[FetchPublicationInterface::SCREEN_NAME]
            );
        }

        return $this->statusRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
            $options[FetchPublicationInterface::SCREEN_NAME]
        );
    }

    /**
     * @return bool
     */
    private function isCollectingStatusesForAggregate(): bool
    {
        return $this->collectionStrategy->publishersListId() !== null;
    }

    /**
     * @param $options
     *
     * @return bool
     */
    private function isLookingBetweenPublicationDateOfLastOneSavedAndNow($options): bool
    {
        if (array_key_exists('since_id', $options)) {
            return true;
        }

        return array_key_exists('max_id', $options) && is_infinite($options['max_id']);
    }

    /**
     * @param        $statuses
     * @param        $shouldDeclareMaximumStatusId
     * @param string $memberName
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
     * @param $options
     * @param $greedy
     * @param $discoverPublicationsWithMaxId
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
    private function tryCollectingFurther($options, $greedy, $discoverPublicationsWithMaxId): bool
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
            $discoverPublicationsWithMaxId
            || (
                $lastCollectionBatchSize !== null
                && $lastCollectionBatchSize === CollectionStrategyInterface::MAX_BATCH_SIZE
            )
        ) {
            // When some of the last batch of publications have been collected for the first time,
            // and we were discovering publication in the past,
            // keep retrieving status in the past,
            // otherwise start collecting publication never seen before,
            // which have been more recently published
            $discoverPublicationsWithMaxId = $lastCollectionBatchSize !== null &&
                $discoverPublicationsWithMaxId;

            if ($greedy) {
                $options[FetchPublicationInterface::publishers_list_ID] = $this->collectionStrategy->publishersListId();
                $options[FetchPublicationInterface::BEFORE]              =
                    $this->collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected();

                $success = $this->collect(
                    $options,
                    $greedy,
                    $discoverPublicationsWithMaxId
                );

                $discoverPublicationWithMinId = !$discoverPublicationsWithMaxId;
                if (
                    $discoverPublicationWithMinId
                    && $this->collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected() === null
                ) {
                    unset($options[FetchPublicationInterface::publishers_list_ID]);

                    $options = $this->statusAccessor->updateExtremum(
                        $this->collectionStrategy,
                        $options,
                        $discoverPublicationsWithMaxId = false
                    );
                    $options = $this->apiAccessor->guessMaxId(
                        $options,
                        $this->collectionStrategy->shouldLookUpPublicationsWithMinId(
                            $this->likedStatusRepository,
                            $this->statusRepository,
                            $this->memberRepository
                        )
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

    private function unlockPublishersList(): void
    {
        if ($this->isCollectingStatusesForAggregate()) {
            $publishersList = $this->publishersListRepository->findOneBy(
                ['id' => $this->collectionStrategy->publishersListId()]
            );
            if ($publishersList instanceof PublishersListInterface) {
                $this->publishersListRepository->unlockPublishersList($publishersList);
                $this->logger->info(
                    sprintf(
                        'Unlocked publishers list of id #%d',
                        $publishersList->getId()
                    )
                );
            }
        }
    }
}
