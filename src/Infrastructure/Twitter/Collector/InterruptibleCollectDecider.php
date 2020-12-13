<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Collector;

use App\Infrastructure\Twitter\Api\Accessor\Exception\ApiRateLimitingException;
use App\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Infrastructure\Amqp\Exception\SkippableMessageException;
use App\Infrastructure\Api\Entity\Whisperer;
use App\Domain\Collection\CollectionStrategyInterface;
use App\Domain\Membership\Exception\MembershipException;
use App\Domain\Publication\Exception\LockedPublicationListException;
use App\Domain\Publication\PublicationListInterface;
use App\Infrastructure\Amqp\Message\FetchPublicationInterface;
use App\Infrastructure\DependencyInjection\Api\ApiAccessorTrait;
use App\Infrastructure\DependencyInjection\Api\ApiLimitModeratorTrait;
use App\Infrastructure\DependencyInjection\Api\StatusAccessorTrait;
use App\Infrastructure\DependencyInjection\Collection\LikedStatusCollectDecider;
use App\Infrastructure\DependencyInjection\Collection\MemberProfileCollectedEventRepositoryTrait;
use App\Infrastructure\DependencyInjection\LoggerTrait;
use App\Infrastructure\DependencyInjection\Membership\MemberRepositoryTrait;
use App\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Infrastructure\DependencyInjection\Publication\PublicationListRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\LikedStatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\Status\StatusPersistenceTrait;
use App\Infrastructure\DependencyInjection\Status\StatusRepositoryTrait;
use App\Infrastructure\DependencyInjection\TokenRepositoryTrait;
use App\Infrastructure\Twitter\Collector\Exception\RateLimitedException;
use App\Infrastructure\Twitter\Collector\Exception\SkipCollectException;
use App\Twitter\Exception\BadAuthenticationDataException;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use App\Twitter\Exception\UnavailableResourceException;
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
    use PublicationListRepositoryTrait;
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

    private function guardAgainstLockedPublicationList(): ?PublicationListInterface
    {
        $publicationList = null;
        if ($this->collectionStrategy->publicationListId() !== null) {
            $publicationList = $this->publicationListRepository->findOneBy(
                ['id' => $this->collectionStrategy->publicationListId()]
            );
        }

        if (
            ($publicationList instanceof PublicationListInterface)
            && $publicationList->isLocked()
            && !$this->collectionStrategy->dateBeforeWhichPublicationsAreToBeCollected()
        ) {
            LockedPublicationListException::throws(
                'Will skip message consumption for locked aggregate #%d',
                $publicationList
            );
        }

        return $publicationList;
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
            $publicationList = $this->guardAgainstLockedPublicationList();
            $whisperer = $this->beforeFetchingStatuses($options);
        } catch (MembershipException|LockedPublicationListException $exception) {
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
                    $publicationList
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
        if (!array_key_exists(FetchPublicationInterface::PUBLICATION_LIST_ID, $options)) {
            return null;
        }

        $this->collectionStrategy->optInToCollectStatusForPublicationListOfId($options[FetchPublicationInterface::PUBLICATION_LIST_ID]);

        return $options[FetchPublicationInterface::PUBLICATION_LIST_ID];
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