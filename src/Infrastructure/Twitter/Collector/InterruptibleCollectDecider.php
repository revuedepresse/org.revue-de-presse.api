<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Accessor\Exception\ApiRateLimitingException;
use App\Accessor\Exception\NotFoundStatusException;
use App\Accessor\Exception\ReadOnlyApplicationException;
use App\Accessor\Exception\UnexpectedApiResponseException;
use App\Amqp\Exception\SkippableMessageException;
use App\Api\Entity\Whisperer;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Publication\PublicationListInterface;
use App\Domain\Status\StatusInterface;
use App\Infrastructure\Amqp\Message\FetchPublication;
use App\Infrastructure\DependencyInjection\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\ApiLimitModeratorTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationListRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\StatusPersistenceTrait;
use App\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Infrastructure\Twitter\Collector\Exception\RateLimitedException;
use App\Infrastructure\Twitter\Collector\Exception\SkipCollectException;
use App\Status\LikedStatusCollectionAwareInterface;
use App\Status\Repository\ExtremumAwareInterface;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\InconsistentTokenRepository;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use ReflectionException;
use stdClass;
use function array_key_exists;
use function count;
use function sprintf;
use function substr;

class InterruptibleCollectDecider implements InterruptibleCollectDeciderInterface
{
    use ApiAccessorTrait;
    use ApiLimitModeratorTrait;
    use LikedStatusRepositoryTrait;
    use PublicationListRepositoryTrait;
    use StatusRepositoryTrait;
    use StatusPersistenceTrait;
    use TokenRepositoryTrait;
    use LoggerTrait;
    use WhispererRepositoryTrait;

    /**
     * @var CollectionStrategyInterface
     */
    private CollectionStrategyInterface $collectionStrategy;

    /**
     * @param CollectionStrategyInterface $collectionStrategy
     * @param array                       $options
     *
     * @throws ProtectedAccountException
     * @throws RateLimitedException
     * @throws SkipCollectException
     * @throws UnavailableResourceException
     * @throws Exception
     */
    public function decideWhetherCollectShouldBeSkipped(
        CollectionStrategyInterface $collectionStrategy,
        array $options
    ): void {
        $this->collectionStrategy = $collectionStrategy;

        try {
            if ($this->shouldSkipCollect(
                $options
            )) {
                throw new SkipCollectException('Skipped pretty naturally ^_^');
            }
        } catch (SuspendedAccountException|NotFoundMemberException|ProtectedAccountException $exception) {
            UnavailableResourceException::handleUnavailableMemberException(
                $exception,
                $this->logger,
                $options
            );
        } catch (SkipCollectException $exception) {
            throw $exception;
        } catch (BadAuthenticationDataException $exception) {
            $this->logger->error(
                sprintf(
                    'The provided tokens have come to expire (%s).',
                    $exception->getMessage()
                )
            );

            throw new SkipCollectException('Skipped because of bad authentication credentials');
        } /** @noinspection BadExceptionsProcessingInspection */
        catch (ApiRateLimitingException $exception) {
            $this->delayingConsumption();

            throw new RateLimitedException('No more call to the API can be made.');
        } catch (UnavailableResourceException|Exception $exception) {
            $this->logger->error(
                sprintf(
                    'An error occurred when checking if a collect could be skipped ("%s")',
                    $exception->getMessage()
                )
            );

            throw new SkipCollectException(
                'Skipped because Twitter sent error message and codes never dealt with so far'
            );
        }
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function delayingConsumption(): bool
    {
        $token = $this->tokenRepository->findFirstFrozenToken();

        if ($token === null) {
            return false;
        }

        /** @var DateTime $frozenUntil */
        $frozenUntil = $token->getFrozenUntil();
        $now         = new DateTime('now', $frozenUntil->getTimezone());

        $timeout = $frozenUntil->getTimestamp() - $now->getTimestamp();

        $this->logger->info('The API is not available right now.');
        $this->moderator->waitFor(
            $timeout,
            [
                '{{ token }}' => substr($token->getOAuthToken(), 0, '8'),
            ]
        );

        return true;
    }

    /**
     * @param array $options
     * @param bool  $discoverPastTweets
     *
     * @return mixed
     * @throws NonUniqueResultException
     */
    public function updateExtremum(
        array $options,
        bool $discoverPastTweets = true
    ) {
        if ($this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            $discoverPastTweets = true;
        }

        $options = $this->getExtremumOptions($options, $discoverPastTweets);

        $findingDirection = $this->getExtremumUpdateMethod($discoverPastTweets);
        $status           = $this->findExtremum($options, $findingDirection);

        $logPrefix = $this->getLogPrefix();

        if (array_key_exists('statusId', $status) && (count($status) === 1)) {
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
                    $options[FetchPublication::SCREEN_NAME],
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
                $options[FetchPublication::SCREEN_NAME]
            )
        );

        return $options;
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
     * @param $options
     * @param $findingDirection
     *
     * @return array|mixed
     * @throws NonUniqueResultException
     */
    private function findExtremum(
        array $options,
        $findingDirection
    )
    {
        if ($this->collectionStrategy->fetchLikes()) {
            return $this->findLikeExtremum($options, $findingDirection);
        }

        if (!$this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            return $this->statusRepository->findLocalMaximum(
                $options[FetchPublication::SCREEN_NAME],
                $this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
            );
        }

        return $this->statusRepository->findNextExtremum(
            $options[FetchPublication::SCREEN_NAME],
            $findingDirection
        );
    }

    /**
     * @param $options
     * @param $findingDirection
     *
     * @return array|mixed
     */
    private function findLikeExtremum($options, $findingDirection)
    {
        if (!$this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            return $this->likedStatusRepository->findLocalMaximum(
                $options[FetchPublication::SCREEN_NAME],
                $this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
            );
        }

        return $this->likedStatusRepository->findNextExtremum
        (
            $options[FetchPublication::SCREEN_NAME],
            $findingDirection
        );
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
     * @return string
     */
    private function getLogPrefix(): string
    {
        if (!$this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()) {
            return '';
        }

        return 'local ';
    }

    /**
     * @param array $options
     *
     * @return bool
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
    private function shouldSkipCollect(
        array $options
    ): bool
    {
        if (
            !array_key_exists(FetchPublication::SCREEN_NAME, $options)
            || $options[FetchPublication::SCREEN_NAME] === null
            || $this->apiAccessor->shouldSkipCollectForMemberWithScreenName(
                $options[FetchPublication::SCREEN_NAME]
            )
        ) {
            return true;
        }

        $publicationList = null;
        if ($this->collectionStrategy->publicationListId() !== null) {
            $publicationList = $this->publicationListRepository->findOneBy(
                ['id' => $this->collectionStrategy->publicationListId()]
            );
        }

        if (
            ($publicationList instanceof PublicationListInterface)
            && $publicationList->isLocked()
            && !$this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected()
        ) {
            $message = sprintf(
                'Will skip message consumption for locked aggregate #%d',
                $publicationList->getId()
            );
            $this->logger->info($message);

            return true;
        }

        try {
            $whisperer = $this->beforeFetchingStatuses(
                $options
            );
        } catch (SkippableMessageException $exception) {
            return $exception->shouldSkipMessageConsumption;
        }

        $statuses = $this->fetchLatestStatuses($options);
        if ($whisperer instanceof Whisperer && count($statuses) > 0) {
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

        if ($this->collectionStrategy->fetchLikes()) {
            $atLeastOneStatusFetched = count($statuses) > 0;

            $hasLikedStatusBeenSavedBefore = $this->hasOneLikedStatusAtLeastBeenSavedBefore(
                $options[FetchPublication::SCREEN_NAME],
                $atLeastOneStatusFetched,
                $publicationList,
                $statuses[0]
            );

            if ($atLeastOneStatusFetched && !$hasLikedStatusBeenSavedBefore) {
                // At this point, it should not skip further consumption
                // for matching liked statuses
                $this->statusPersistence->saveStatusForScreenName(
                    $statuses,
                    $options[FetchPublication::SCREEN_NAME],
                    $this->collectionStrategy
                );

                $this->statusRepository->declareMinimumLikedStatusId(
                    $statuses[count($statuses) - 1],
                    $options[FetchPublication::SCREEN_NAME]
                );
            }

            if (!$atLeastOneStatusFetched || $hasLikedStatusBeenSavedBefore) {
                $statuses = $this->fetchLatestStatuses(
                    $options,
                    $discoverPastTweets = false
                );
                if (count($statuses) > 0) {
                    if (
                    $this->statusRepository->hasBeenSavedBefore(
                        [$statuses[0]]
                    )
                    ) {
                        return true;
                    }

                    $this->collectionStrategy->optInToCollectStatusForPublicationListOfId(
                        $options[FetchPublication::AGGREGATE_ID]
                    );

                    // At this point, it should not skip further consumption
                    // for matching liked statuses
                    $this->statusPersistence->saveStatusForScreenName(
                        $statuses,
                        $options[FetchPublication::SCREEN_NAME],
                        $this->collectionStrategy
                    );

                    $this->statusRepository->declareMaximumLikedStatusId(
                        $statuses[0],
                        $options[FetchPublication::SCREEN_NAME]
                    );
                }

                return true;
            }

            return false;
        }

        if (!$this->collectionStrategy->fetchLikes()) {
            try {
                $this->statusRepository->updateLastStatusPublicationDate(
                    $options[FetchPublication::SCREEN_NAME]
                );
            } catch (NotFoundStatusException $exception) {
                $this->logger->info($exception->getMessage());
            }
        }

        if ($whisperer instanceof Whisperer) {
            $this->afterUpdatingLastPublicationDate(
                $options,
                $whisperer
            );
        }

        return true;
    }

    /**
     * @param array $options
     * @param bool                        $discoverPastTweets
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
    protected function fetchLatestStatuses(
        $options,
        bool $discoverPastTweets = true
    ): array {
        $options[LikedStatusCollectionAwareInterface::INTENT_TO_FETCH_LIKES] = $this->collectionStrategy->fetchLikes();
        $options = $this->removeCollectOptions($options);
        $options = $this->updateExtremum($options, $discoverPastTweets);

        if (
            array_key_exists('max_id', $options)
            && $this->collectionStrategy->dateBeforeWhichStatusAreToBeCollected() // Looking into the past
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
     * @param array $options
     *
     * @return mixed
     */
    private function removeCollectOptions(
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
     * @param array                       $options
     * @param array                       $statuses
     * @param Whisperer                   $whisperer
     *
     * @throws SkippableMessageException
     */
    private function afterCountingCollectedStatuses(
        array $options,
        array $statuses,
        Whisperer $whisperer
    ): void {
        $this->extractAggregateIdFromOptions($options);

        if (count($statuses) === 0) {
            SkippableMessageException::stopMessageConsumption();
        }

        if (
            array_key_exists(0, $statuses) &&
            $this->statusRepository->hasBeenSavedBefore($statuses)
        ) {
            $this->logger->info(
                sprintf(
                    'The item with id "%d" has already been saved in the past (skipping the whole batch from "%s")',
                    $statuses[0]->id_str,
                    $options[FetchPublication::SCREEN_NAME]
                )
            );
            SkippableMessageException::stopMessageConsumption();
        }

        $savedItems = $this->statusPersistence->saveStatusForScreenName(
            $statuses,
            $options[FetchPublication::SCREEN_NAME],
            $this->collectionStrategy
        );

        if ($savedItems === null ||
            count($statuses) < CollectionStrategyInterface::MAX_BATCH_SIZE
        ) {
            SkippableMessageException::stopMessageConsumption();
        }

        $isNotAboutCollectingLikes = !$this->collectionStrategy->fetchLikes();
        if ($isNotAboutCollectingLikes) {
            $this->whispererRepository->forgetAboutWhisperer($whisperer);
        }

        SkippableMessageException::continueMessageConsumption();
    }

    /**
     * @param array $options
     *
     * @return int|null
     */
    private function extractAggregateIdFromOptions(
        $options
    ): ?int {
        if (!array_key_exists(FetchPublication::AGGREGATE_ID, $options)) {
            return null;
        }

        $this->collectionStrategy->optInToCollectStatusForPublicationListOfId($options[FetchPublication::AGGREGATE_ID]);

        return $options[FetchPublication::AGGREGATE_ID];
    }

    /**
     * @param string                        $screenNameOfMemberWhoLikedStatus
     * @param bool                          $atLeastOneStatusFetched
     * @param PublicationListInterface|null $publicationList
     * @param stdClass                     $firstStatus
     *
     * @return bool
     */
    private function hasOneLikedStatusAtLeastBeenSavedBefore(
        string $screenNameOfMemberWhoLikedStatus,
        bool $atLeastOneStatusFetched,
        ?PublicationListInterface $publicationList,
        stdClass $firstStatus
    ): bool {
        if (!$atLeastOneStatusFetched) {
            return false;
        }

        if (!($publicationList instanceof PublicationListInterface)) {
            return false;
        }

        return $this->likedStatusRepository->hasBeenSavedBefore(
            $firstStatus,
            $publicationList->getName(),
            $screenNameOfMemberWhoLikedStatus,
            $firstStatus->user->screen_name
        );
    }

    /**
     * @param array $options
     * @param Whisperer                   $whisperer
     */
    private function afterUpdatingLastPublicationDate(
        $options,
        Whisperer $whisperer
    ): void {
        if ($this->collectionStrategy->fetchLikes()) {
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

        $this->logger->info(sprintf(
            'Skipping whisperer "%s"', $options[FetchPublication::SCREEN_NAME]
        ));
    }

    /**
     * @param array $options
     *
     * @return null|Whisperer
     * @throws SkippableMessageException
     */
    private function beforeFetchingStatuses(
        $options
    ): ?Whisperer {
        if ($this->collectionStrategy->fetchLikes()) {
            return null;
        }

        $whisperer = $this->whispererRepository->findOneBy(
            ['name' => $options[FetchPublication::SCREEN_NAME]]
        );
        if (!$whisperer instanceof Whisperer) {
            SkippableMessageException::continueMessageConsumption();
        }

        $whisperer->member = $this->apiAccessor->showUser(
            $options[FetchPublication::SCREEN_NAME]
        );
        $whispers          = (int) $whisperer->member->statuses_count;

        $storedWhispers = $this->statusRepository->countHowManyStatusesFor($options[FetchPublication::SCREEN_NAME]);

        if ($storedWhispers === $whispers) {
            SkippableMessageException::stopMessageConsumption();
        }

        if (
            $whispers >= $this->collectionStrategy::MAX_AVAILABLE_TWEETS_PER_USER
            && $storedWhispers < $this->collectionStrategy::MAX_AVAILABLE_TWEETS_PER_USER
        ) {
            SkippableMessageException::continueMessageConsumption();
        }

        return $whisperer;
    }
}