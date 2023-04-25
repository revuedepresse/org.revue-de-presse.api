<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Curation\Curator\TweetCuratorInterface;
use App\Twitter\Domain\Curation\Exception\NoRemainingPublicationException;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Publication\Exception\LockedPublishersListException;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchSearchQueryMatchingTweetInterface;
use App\Twitter\Infrastructure\Curation\Exception\RateLimited;
use App\Twitter\Infrastructure\Curation\Exception\SkippedCurationException;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Curator\InterruptibleCuratorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\TweetBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\RateLimitComplianceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\TweetAwareHttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\WhispererIdentificationTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\PersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\TweetPersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetCurationLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TranslatorTrait;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\InconsistentTokenRepository;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException;
use App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Http\Entity\FreezableToken;
use App\Twitter\Infrastructure\Http\Entity\Token;
use App\Twitter\Infrastructure\Http\Repository\Exception\TweetNotFoundException as NotFoundException;
use DateTimeImmutable;
use Doctrine\DBAL\Exception\ConstraintViolationException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use LogicException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use stdClass;
use function array_key_exists;
use function count;

class TweetCurator implements TweetCuratorInterface
{
    use HttpClientTrait;
    use RateLimitComplianceTrait;
    use LoggerTrait;
    use InterruptibleCuratorTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use MemberRepositoryTrait;
    use TweetBatchCollectedEventRepositoryTrait;
    use PersistenceLayerTrait;
    use PublishersListRepositoryTrait;
    use TweetCurationLoggerTrait;
    use TweetPersistenceLayerTrait;
    use TweetAwareHttpClientTrait;
    use TweetRepositoryTrait;
    use TokenRepositoryTrait;
    use TranslatorTrait;
    use WhispererIdentificationTrait;
    use WhispererRepositoryTrait;

    private const MESSAGE_OPTION_TOKEN = 'oauth';

    public LoggerInterface $twitterApiLogger;

    private CurationSelectorsInterface $selectors;

    /**
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws ReadOnlyApplicationException
     * @throws ReflectionException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws UnexpectedApiResponseException
     * @throws Exception
     */
    public function curateTweets(
        array $options,
              $greedy = false,
              $discoverPublicationsWithMaxId = true
    ): bool {
        $success = false;

        $this->selectors = CurationSelectors::fromArray($options);

        try {
            $this->interruptibleCurator->curateTweets(
                $this->selectors,
                $options
            );
        } catch (SkippedCurationException $exception) {
            if ($exception instanceof RateLimited) {
                unset($exception);

                return false; // unsuccessfully made an attempt to collect statuses
            }

            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        } finally {
            $this->updateLastStatusPublicationDate($options);
        }

        if ($this->selectors->oneOfTheOptionsIsActive()) {
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
            !$this->isTwitterApiAvailable() &&
            $this->remainingItemsToCollect($options)
        ) {
            $this->unlockPublishersList();

            /**
             * There is no stone left unturned
             * as soon as there is no more operation to run,
             * that is to say as soon as there are no more tweets to be curated
             *
             * Marks the curation as undefined as there are remaining operations left
             * or when Twitter API is not available
             */
            return false;
        }

        if ($this->selectors->shouldLookUpPublicationsWithMinId(
            $this->tweetRepository,
            $this->memberRepository
        )) {
            $discoverPublicationsWithMaxId = false;
        }

        $options = $this->tweetAwareHttpClient->updateExtremum(
            $this->selectors,
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

            // Figuring out a member is now protected,
            // suspended or not found is considered to be a successful operation,
            // provided the workers would not call the API on behalf of them

            UnavailableResourceException::handleUnavailableMemberException(
                $exception,
                $this->logger,
                $options
            );

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

    private function updateLastStatusPublicationDate(array $options): void
    {
        if (array_key_exists(FetchSearchQueryMatchingTweetInterface::SEARCH_QUERY, $options)) {
            return;
        }

        try {
            $this->tweetRepository->updateLastStatusPublicationDate(
                $options[FetchAuthoredTweetInterface::SCREEN_NAME]
            );
        } catch (TweetNotFoundException $exception) {
            $this->logger->info($exception->getMessage(), ['trace' => $exception->getTrace()]);
        }
    }

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
        return $statuses >= (CurationSelectorsInterface::MAX_AVAILABLE_TWEETS_PER_USER - 100);
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
        if ($this->selectors->dateBeforeWhichPublicationsAreToBeCollected()) {
            unset($options[FetchAuthoredTweetInterface::BEFORE]);
        }
        if (array_key_exists(FetchAuthoredTweetInterface::TWITTER_LIST_ID, $options)) {
            unset($options[FetchAuthoredTweetInterface::TWITTER_LIST_ID]);
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
        $token = new Token();
        $token->setAccessToken($oauthTokens[TokenInterface::FIELD_TOKEN]);
        $token->setAccessTokenSecret($oauthTokens[TokenInterface::FIELD_SECRET]);

        $this->httpClient->fromToken($token);

        /** @var Token token */
        $token = $this->tokenRepository->findOneBy(
            ['oauthToken' => $oauthTokens[TokenInterface::FIELD_TOKEN]]
        );

        if (!($token instanceof Token)) {
            $token = $this->tokenRepository->findFirstUnfrozenToken();
        }

        $this->httpClient->setConsumerKey($token->consumerKey);
        $this->httpClient->setConsumerSecret($token->consumerSecret);

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

        try {
            if ($firstStatusId > 0) {
                $this->tweetRepository->byId($firstStatusId);
            } else {
                return;
            }

            if ($lastStatusId > 0) {
                $this->tweetRepository->byId((int) $lastStatusId);
            }
        }
        catch (NotFoundException $e) {
            return;
        }

        // When we didn't fetch publications between the last one saved and now,
        // both first and last status were declared
        // some publications were retrieved and
        // no boundaries were crossed
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

    protected function isApiAvailable(): bool
    {
        $availableApi = false;

        if (!$this->httpClient->isApiLimitReached()) {
            return true;
        }

        try {
            if (!$this->httpClient->isApiRateLimitReached('/statuses/user_timeline')) {
                $availableApi = true;
            }
        } catch (Exception $exception) {
            $this->twitterApiLogger->info('[error message] Testing for API availability: ' . $exception->getMessage());
            $this->twitterApiLogger->info('[error code] ' . (int) $exception->getCode());

            if ($exception->getCode() === $this->httpClient->getEmptyReplyErrorCode()) {
                $availableApi = true;
            } else {
                $this->tokenRepository->freezeToken(
                    FreezableToken::fromAccessToken(
                        $this->httpClient->accessToken(),
                        $this->httpClient->consumerKey()
                    )
                );
            }
        }

        return $availableApi;
    }

    protected function isApiAvailableForToken(TokenInterface $token): bool
    {
        $this->setupAccessor(
            [
                TokenInterface::FIELD_TOKEN  => $token->getAccessToken(),
                TokenInterface::FIELD_SECRET => $token->getAccessTokenSecret()
            ]
        );

        return $this->isApiAvailable();
    }

    /**
     * @throws Exception
     */
    protected function isTwitterApiAvailable(): bool
    {
        $availableApi = false;

        $token = $this->tokenRepository->findByUserToken($this->httpClient->userToken);

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
            $oauthToken = $token->getAccessToken();

            $availableApi = $this->isApiAvailableForToken($token);
            while (
                !$availableApi &&
                ($token = $this->tokenRepository->findFirstUnfrozenToken()) instanceof TokenInterface
            ) {
                $availableApi = $this->isApiAvailableForToken($token);
                if (!$availableApi) {
                    $timeout = min(abs($timeout), abs($token->getFrozenUntil()->getTimestamp() - $now->getTimestamp()));
                }

                $oauthToken = $token->getAccessToken();
            }

            if (!$availableApi) {
                $this->logger->info('The API is not available right now.');
                $this->moderator->waitFor(
                    $timeout,
                    [
                        '{{ token }}' => substr($oauthToken, 0, 8),
                    ]
                );
            }
        }

        if (!$availableApi) {
            return $this->interruptibleCurator->delayingConsumption();
        }

        return true;
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    protected function remainingItemsToCollect(array $options): bool
    {
        return $this->remainingStatuses($options);
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    protected function remainingStatuses($options): bool
    {
        $serializedStatusCount = $this->tweetRepository->howManyTweetsHaveBeenCollectedForMemberHavingUserName(
            $options[FetchAuthoredTweetInterface::SCREEN_NAME]
        );
        $existingStatus        = $this->translator->trans(
            'logs.info.status_existing',
            [
                'count'        => $serializedStatusCount,
                'total_status' => $serializedStatusCount,
                'member'       => $options[FetchAuthoredTweetInterface::SCREEN_NAME],
            ],
            'logs'
        );
        $this->logger->info($existingStatus);

        $memberProfile = $this->collectMemberProfile(
            $options[FetchAuthoredTweetInterface::SCREEN_NAME]
        );
        if (!isset($memberProfile->statuses_count)) {
            $memberProfile->statuses_count = 0;
        }

        /**
         * Twitter allows 3200 past tweets at most to be retrieved for any given user
         */
        $statusesCount    = max(
            $memberProfile->statuses_count,
            CurationSelectorsInterface::MAX_AVAILABLE_TWEETS_PER_USER
        );
        $discoveredStatus = $this->translator->trans(
            'logs.info.status_discovered',
            [
                'member'       => $options[FetchAuthoredTweetInterface::SCREEN_NAME],
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
     * @param CurationSelectorsInterface  $selectors
     *
     * @return int|null
     * @throws ProtectedAccountException
     */
    protected function saveStatusesMatchingCriteria(
        $options,
        CurationSelectorsInterface $selectors
    ): ?int {
        $options  = $this->declareOptionsToCollectStatuses($options);

        try {
            $tweetsCollection = $this->tweetsBatchCollectedEventRepository
                ->collectedPublicationBatch($selectors, $options);

            if ($tweetsCollection instanceof stdClass && isset($tweetsCollection->error)) {
                throw new ProtectedAccountException(
                    $tweetsCollection->error,
                    $this->httpClient::ERROR_PROTECTED_ACCOUNT
                );
            }
        } catch (ApiAccessRateLimitException $e) {
            if ($this->isTwitterApiAvailable()) {
                return $this->saveStatusesMatchingCriteria($options, $selectors);
            }
        }

        $lookingBetweenLastPublicationAndNow = $this->isLookingBetweenPublicationDateOfLastOneSavedAndNow($options);

        /** @var array $tweetsCollection */
        if (count($tweetsCollection) > 0) {
            $this->safelyDeclareExtremum(
                $tweetsCollection,
                $lookingBetweenLastPublicationAndNow,
                $options[FetchAuthoredTweetInterface::SCREEN_NAME]
            );
        }

        try {
            $this->guardAgainstNoRemainingPublicationToBeCollected(
                $options,
                $lookingBetweenLastPublicationAndNow,
                $tweetsCollection
            );
        } catch (NoRemainingPublicationException $exception) {
            $this->logger->info($exception->getMessage());

            return 0;
        }

        $lastCollectionBatchSize = $this->tweetPersistenceLayer->saveTweetsAuthoredByMemberHavingScreenName(
            $tweetsCollection,
            $options[FetchAuthoredTweetInterface::SCREEN_NAME],
            $selectors
        );

        $this->whispererIdentification->identifyWhisperer(
            $selectors,
            $options,
            $options[FetchAuthoredTweetInterface::SCREEN_NAME],
            (int) $lastCollectionBatchSize
        );

        return $lastCollectionBatchSize;
    }

    private function collectMemberProfile(string $screenName)
    {
        $eventRepository = $this->memberProfileCollectedEventRepository;

        return $eventRepository->collectedMemberProfile(
            $this->httpClient,
            [$eventRepository::OPTION_SCREEN_NAME => $screenName]
        );
    }

    /**
     * @param array  $statuses
     * @param bool   $shouldDeclareMaximumStatusId
     *
     * @return MemberInterface
     */
    private function declareExtremumIdForMember(
        array $statuses,
        bool $shouldDeclareMaximumStatusId
    ): MemberInterface {
        if (empty($statuses)) {
            throw new LogicException(
                'There should be at least one status'
            );
        }

        if ($shouldDeclareMaximumStatusId) {
            $lastStatusFetched = $statuses[0];

            return $this->tweetRepository->declareMaximumStatusId($lastStatusFetched);
        }

        $firstStatusFetched = $statuses[count($statuses) - 1];

        return $this->tweetRepository->declareMinimumStatusId($firstStatusFetched);
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

        return $options;
    }

    private function lockPublishersList(): void
    {
        if (!$this->isCollectingStatusesForAggregate()) {
            return;
        }

        $publishersList = $this->publishersListRepository->findOneBy(
            ['id' => $this->selectors->membersListId()]
        );

        if (!$publishersList instanceof PublishersListInterface) {
            return;
        }

        if ($publishersList->isLocked()) {
            throw new LockedPublishersListException(
                sprintf(
                    'Won\'t process message for already locked aggregate "%s"',
                    $publishersList->publicId()
                )
            );
        }

        $this->logger->info(
            sprintf(
                'About to lock processing of Twitter list #%d',
                $publishersList->getId()
            )
        );

        $this->publishersListRepository->lockAggregate($publishersList);
    }

    private function getExtremeStatusesIdsFor($options): array
    {
        return $this->tweetRepository->getIdsOfExtremeStatusesSavedForMemberHavingScreenName(
            $options[FetchAuthoredTweetInterface::SCREEN_NAME]
        );
    }

    private function isCollectingStatusesForAggregate(): bool
    {
        return $this->selectors->membersListId() !== null;
    }

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
                $shouldDeclareMaximumStatusId
            );
        } catch (NotFoundMemberException $exception) {
            $this->httpClient->ensureMemberHavingNameExists($exception->screenName);

            try {
                $this->declareExtremumIdForMember(
                    $statuses,
                    $shouldDeclareMaximumStatusId
                );
            } catch (NotFoundMemberException $exception) {
                $this->httpClient->ensureMemberHavingNameExists($exception->screenName);
                $this->declareExtremumIdForMember(
                    $statuses,
                    $shouldDeclareMaximumStatusId
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
        $options[self::MESSAGE_OPTION_TOKEN] = $token->getAccessToken();
        $this->setupAccessor(
            [
                TokenInterface::FIELD_TOKEN  => $options[self::MESSAGE_OPTION_TOKEN],
                TokenInterface::FIELD_SECRET => $token->getAccessTokenSecret()
            ]
        );

        return $options;
    }

    public function collectSingleTweet(string $identifier): mixed
    {
        return $this->httpClient->showStatus($identifier);
    }

    /**
     * @param $options
     * @param $greedy
     * @param $discoverPublicationsWithMaxId
     *
     * @return bool
     * @throws ApiAccessRateLimitException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NoResultException
     * @throws NonUniqueResultException
     * @throws NotFoundMemberException
     * @throws TweetNotFoundException
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

        $this->collectStatusLogger->logIntentionWithRegardToList(
            $options,
            $this->selectors
        );

        $lastCollectionBatchSize = $this->saveStatusesMatchingCriteria(
            $options,
            $this->selectors
        );

        if (
            $discoverPublicationsWithMaxId ||
            $lastCollectionBatchSize === CurationSelectorsInterface::MAX_BATCH_SIZE
        ) {
            // When some of the last batch of publications have been collected for the first time,
            // and we were discovering publication in the past,
            // keep retrieving status in the past,
            // otherwise start collecting publication never seen before,
            // which have been more recently published
            $discoverPublicationsWithMaxId = $lastCollectionBatchSize !== null &&
                $discoverPublicationsWithMaxId;

            if ($greedy) {
                $options[FetchAuthoredTweetInterface::TWITTER_LIST_ID] = $this->selectors->membersListId();
                $options[FetchAuthoredTweetInterface::BEFORE] = $this->selectors->dateBeforeWhichPublicationsAreToBeCollected();

                $success = $this->curateTweets(
                    $options,
                    $greedy,
                    $discoverPublicationsWithMaxId
                );

                $discoverPublicationWithMinId = !$discoverPublicationsWithMaxId;
                if (
                    $discoverPublicationWithMinId
                    && $this->selectors->dateBeforeWhichPublicationsAreToBeCollected() === null
                ) {
                    unset($options[FetchAuthoredTweetInterface::TWITTER_LIST_ID]);

                    $options = $this->tweetAwareHttpClient->updateExtremum(
                        $this->selectors,
                        $options,
                        discoverPublicationsWithMaxId: false
                    );
                    $options = $this->httpClient->guessMaxId(
                        $options,
                        $this->selectors->shouldLookUpPublicationsWithMinId(
                            $this->tweetRepository,
                            $this->memberRepository
                        )
                    );

                    $this->saveStatusesMatchingCriteria(
                        $options,
                        $this->selectors
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
                ['id' => $this->selectors->membersListId()]
            );
            if ($publishersList instanceof PublishersListInterface) {
                $this->publishersListRepository->unlockPublishersList($publishersList);
                $this->logger->info(
                    sprintf(
                        'Unlocked Twitter list of id #%d',
                        $publishersList->getId()
                    )
                );
            }
        }
    }
}
