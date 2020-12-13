<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Collector;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ApiRateLimitingException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableMessageException;
use App\Twitter\Infrastructure\Api\Entity\Whisperer;
use App\Twitter\Domain\Curation\CollectionStrategyInterface;
use App\Twitter\Domain\Membership\Exception\MembershipException;
use App\Twitter\Domain\Publication\Exception\LockedPublishersListException;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Api\ApiLimitModeratorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Api\StatusAccessorTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\LikedStatusCollectDecider;
use App\Twitter\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusPersistenceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Twitter\Infrastructure\Twitter\Collector\Exception\RateLimitedException;
use App\Twitter\Infrastructure\Twitter\Collector\Exception\SkipCollectException;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use DateTime;
use Exception;
use function array_key_exists;
use function count;
use function sprintf;
use function substr;

class InterruptibleCollectDecider implements InterruptibleCollectDeciderInterface
{
    use ApiAccessorTrait;
    use ApiLimitModeratorTrait;
    use LikedStatusRepositoryTrait;
    use LikedStatusCollectDecider;
    use MemberRepositoryTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use PublishersListRepositoryTrait;
    use StatusAccessorTrait;
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
                'Skipped because Twitter sent error message and code never dealt with so far'
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
                '{{ token }}' => substr(
                    $token->getOAuthToken(),
                    0,
                    8
                ),
            ]
        );

        return true;
    }

    /**
     * @param array $options
     *
     * @throws MembershipException
     */
    private function guardAgainstExceptionalMember(array $options): void
    {
        if (
            !array_key_exists(FetchPublicationInterface::SCREEN_NAME, $options)
            || $options[FetchPublicationInterface::SCREEN_NAME] === null
            || $this->apiAccessor->shouldSkipCollectForMemberWithScreenName(
                $options[FetchPublicationInterface::SCREEN_NAME]
            )
        ) {
            throw new MembershipException(
                'Skipping collect when encountering exceptional member',
            );
        }
    }

    private function guardAgainstLockedPublishersList(): ?PublishersListInterface
    {
        $publishersList = null;
        if ($this->collectionStrategy->publishersListId() !== null) {
            $publishersList = $this->publishersListRepository->findOneBy(
                ['id' => $this->collectionStrategy->publishersListId()]
            );
        }

        if (
            ($publishersList instanceof PublishersListInterface)
            && $publishersList->isLocked()
            && !$this->collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()
        ) {
            LockedPublishersListException::throws(
                'Will skip message consumption for locked aggregate #%d',
                $publishersList
            );
        }

        return $publishersList;
    }

    /**
     * @param array $options
     *
     * @return bool
     */
    private function shouldSkipCollect(
        array $options
    ): bool {
        try {
            $this->guardAgainstExceptionalMember($options);
            $publishersList = $this->guardAgainstLockedPublishersList();
            $whisperer = $this->beforeFetchingStatuses($options);
        } catch (MembershipException|LockedPublishersListException $exception) {
            $this->logger->info($exception->getMessage());

            return true;
        } catch (SkippableMessageException $exception) {
            return $exception->shouldSkipMessageConsumption;
        }

        if ($this->memberRepository->hasBeenUpdatedBetween7HoursAgoAndNow(
            $this->collectionStrategy->screenName()
        )) {
            $this->logger->info(sprintf(
                'Publications have been recently collected for %s',
                $this->collectionStrategy->screenName()
            ));

            return true;
        }

        $statuses = $this->statusAccessor->fetchPublications(
            $this->collectionStrategy,
            $options
        );

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
            return $this->likedStatusCollectDecider
                ->shouldSkipLikedStatusCollect(
                    $options,
                    $statuses,
                    $this->collectionStrategy,
                    $publishersList
                );
        }

        $this->afterUpdatingLastPublicationDate(
            $options,
            $whisperer
        );

        return true;
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
                    $options[FetchPublicationInterface::SCREEN_NAME]
                )
            );
            SkippableMessageException::stopMessageConsumption();
        }

        $savedItems = $this->statusPersistence->savePublicationsForScreenName(
            $statuses,
            $options[FetchPublicationInterface::SCREEN_NAME],
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
        if (!array_key_exists(FetchPublicationInterface::publishers_list_ID, $options)) {
            return null;
        }

        $this->collectionStrategy->optInToCollectStatusForPublishersListOfId($options[FetchPublicationInterface::publishers_list_ID]);

        return $options[FetchPublicationInterface::publishers_list_ID];
    }

    /**
     * @param array          $options
     * @param Whisperer|null $whisperer
     */
    private function afterUpdatingLastPublicationDate(
        $options,
        ?Whisperer $whisperer
    ): void {
        if (!($whisperer instanceof Whisperer)) {
            return;
        }

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
            'Skipping whisperer "%s"', $options[FetchPublicationInterface::SCREEN_NAME]
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
            ['name' => $options[FetchPublicationInterface::SCREEN_NAME]]
        );
        if (!$whisperer instanceof Whisperer) {
            SkippableMessageException::continueMessageConsumption();
        }

        $eventRepository = $this->memberProfileCollectedEventRepository;
        $whisperer->member = $eventRepository->collectedMemberProfile(
            $this->apiAccessor,
            [$eventRepository::OPTION_SCREEN_NAME => $options[FetchPublicationInterface::SCREEN_NAME]]
        );
        $whispers          = (int) $whisperer->member->statuses_count;

        $storedWhispers = $this->statusRepository->countHowManyStatusesFor($options[FetchPublicationInterface::SCREEN_NAME]);

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