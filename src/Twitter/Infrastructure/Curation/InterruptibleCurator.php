<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Curation;

use App\Membership\Domain\Exception\MembershipException;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Search\Infrastructure\DependencyInjection\SearchQueryAwareHttpClientTrait;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Curation\Curator\InterruptibleCuratorInterface;
use App\Twitter\Domain\Publication\Exception\LockedPublishersListException;
use App\Twitter\Domain\Publication\PublishersListInterface;
use App\Twitter\Infrastructure\Amqp\Exception\SkippableMessageException;
use App\Twitter\Infrastructure\Amqp\Message\FetchAuthoredTweetInterface;
use App\Twitter\Infrastructure\Curation\Exception\RateLimited;
use App\Twitter\Infrastructure\Curation\Exception\SkippedCurationException;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\MemberProfileCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\RateLimitComplianceTrait;
use App\Twitter\Infrastructure\DependencyInjection\Http\TweetAwareHttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Membership\WhispererRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\TweetPersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TokenRepositoryTrait;
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
use App\Twitter\Infrastructure\Http\Entity\Whisperer;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use ReflectionException;
use function array_key_exists;
use function count;
use function sprintf;
use function substr;

class InterruptibleCurator implements InterruptibleCuratorInterface
{
    use HttpClientTrait;
    use LoggerTrait;
    use MemberProfileCollectedEventRepositoryTrait;
    use MemberRepositoryTrait;
    use PublishersListRepositoryTrait;
    use RateLimitComplianceTrait;
    use SearchQueryAwareHttpClientTrait;
    use TweetPersistenceLayerTrait;
    use TweetRepositoryTrait;
    use TokenRepositoryTrait;
    use TweetAwareHttpClientTrait;
    use WhispererRepositoryTrait;

    const OPERATION_SKIPPED_AS_EXPECTED = 'Skipped pretty naturally ^_^';
    private CurationSelectorsInterface $selectors;

    /**
     * @throws ProtectedAccountException
     * @throws RateLimited
     * @throws SkippedCurationException
     * @throws UnavailableResourceException
     * @throws Exception
     */
    public function curateTweets(
        CurationSelectorsInterface $selectors,
        array                      $options
    ): void {
        $this->selectors = $selectors;

        if ($this->selectors->isSearchQuery()) {
            $this->searchQueryAwareHttpClient->searchTweets($this->selectors->searchQuery());

            throw new SkippedCurationException(self::OPERATION_SKIPPED_AS_EXPECTED);
        }

        try {
            if ($this->shouldSkipCuration($options)) {
                throw new SkippedCurationException(self::OPERATION_SKIPPED_AS_EXPECTED);
            }
        } catch (SuspendedAccountException|NotFoundMemberException|ProtectedAccountException $exception) {
            UnavailableResourceException::handleUnavailableMemberException(
                $exception,
                $this->logger,
                $options
            );
        } catch (SkippedCurationException $exception) {
            $this->logger->info(
                sprintf(
                    'Skipping Tweets curation for member "%s".',
                    $selectors->screenName()
                )
            );

            throw $exception;
        } catch (BadAuthenticationDataException $exception) {
            $this->logger->error(
                sprintf(
                    'The provided tokens have come to expire (%s).',
                    $exception->getMessage()
                )
            );

            throw new SkippedCurationException('Skipped because of bad authentication credentials');
        } /** @noinspection BadExceptionsProcessingInspection */
        catch (ApiAccessRateLimitException $exception) {
            $this->delayingConsumption();

            throw new RateLimited('No more call to the API can be made.');
        } catch (UnavailableResourceException|Exception $exception) {
            $this->logger->error(
                sprintf(
                    'An error occurred when checking if a collect could be skipped ("%s")',
                    $exception->getMessage()
                ),
                ['trace' => json_encode($exception->getTrace())],
            );

            throw new SkippedCurationException(
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
        $this->rateLimitCompliance->waitFor(
            $timeout,
            [
                '{{ token }}' => substr(
                    $token->getAccessToken(),
                    0,
                    8
                ),
            ]
        );

        return true;
    }

    /**
     * @throws \App\Membership\Domain\Exception\MembershipException
     */
    private function guardAgainstExceptionalMember(array $options): void
    {
        if (
            !array_key_exists(FetchAuthoredTweetInterface::SCREEN_NAME, $options)
            || $options[FetchAuthoredTweetInterface::SCREEN_NAME] === null
            || $this->httpClient->skipCuratingTweetsForMemberHavingScreenName(
                $options[FetchAuthoredTweetInterface::SCREEN_NAME]
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
        if ($this->selectors->membersListId() !== null) {
            $publishersList = $this->publishersListRepository->findOneBy(
                ['id' => $this->selectors->membersListId()]
            );
        }

        if (
            ($publishersList instanceof PublishersListInterface)
            && $publishersList->isLocked()
            && !$this->selectors->dateBeforeWhichPublicationsAreToBeCollected()
        ) {
            LockedPublishersListException::throws(
                'Will skip message consumption for locked aggregate #%d',
                $publishersList
            );
        }

        return $publishersList;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    private function shouldSkipCuration(
        array $options
    ): bool {
        try {
            $this->guardAgainstExceptionalMember($options);
            $this->guardAgainstLockedPublishersList();

            $whisperer = $this->beforeFetchingTweets($options);
        } catch (MembershipException|LockedPublishersListException $exception) {
            $this->logger->info($exception->getMessage());

            return true;
        } catch (SkippableMessageException $exception) {
            return $exception->shouldSkipMessageConsumption;
        }

        if ($this->memberRepository->hasBeenUpdatedBetweenHalfAnHourAgoAndNow($this->selectors->screenName())) {
            $this->logger->info(
                sprintf(
                    'Tweets have been curated for "%s".',
                    $this->selectors->screenName()
                )
            );

            return true;
        }

        $tweets = $this->tweetAwareHttpClient->fetchTweets(
            $this->selectors,
            $options
        );

        if ($whisperer instanceof Whisperer && count($tweets) > 0) {
            try {
                $this->afterCountingCollectedStatuses(
                    $options,
                    $tweets,
                    $whisperer
                );
            } catch (SkippableMessageException $exception) {
                return $exception->shouldSkipMessageConsumption;
            }
        }

        $this->afterUpdatingLastPublicationDate(
            $options,
            $whisperer
        );

        return true;
    }

    /**
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
            $this->tweetRepository->hasBeenSavedBefore($statuses)
        ) {
            $this->logger->info(
                sprintf(
                    'The item with id "%d" has already been saved in the past (skipping the whole batch from "%s")',
                    $statuses[0]->id_str,
                    $options[FetchAuthoredTweetInterface::SCREEN_NAME]
                )
            );
            SkippableMessageException::stopMessageConsumption();
        }

        $savedItems = $this->tweetPersistenceLayer->saveTweetsAuthoredByMemberHavingScreenName(
            $statuses,
            $options[FetchAuthoredTweetInterface::SCREEN_NAME],
            $this->selectors
        );

        if (
            $savedItems === null ||
            count($statuses) < CurationSelectorsInterface::MAX_BATCH_SIZE
        ) {
            SkippableMessageException::stopMessageConsumption();
        }

        $this->whispererRepository->forgetAboutWhisperer($whisperer);

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
        if (!array_key_exists(FetchAuthoredTweetInterface::TWITTER_LIST_ID, $options)) {
            return null;
        }

        $this->selectors->optInToCollectStatusForPublishersListOfId($options[FetchAuthoredTweetInterface::TWITTER_LIST_ID]);

        return $options[FetchAuthoredTweetInterface::TWITTER_LIST_ID];
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
            'Skipping whisperer "%s"', $options[FetchAuthoredTweetInterface::SCREEN_NAME]
        ));
    }

    /**
     * @throws \App\Twitter\Infrastructure\Amqp\Exception\SkippableMessageException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function beforeFetchingTweets($options): ?Whisperer {
        $whisperer = $this->whispererRepository->findOneBy(
            ['name' => $options[FetchAuthoredTweetInterface::SCREEN_NAME]]
        );
        if (!$whisperer instanceof Whisperer) {
            SkippableMessageException::continueMessageConsumption();
        }

        $eventRepository = $this->memberProfileCollectedEventRepository;
        $whisperer->member = $eventRepository->collectedMemberProfile(
            $this->httpClient,
            [$eventRepository::OPTION_SCREEN_NAME => $options[FetchAuthoredTweetInterface::SCREEN_NAME]]
        );
        $whispers          = (int) $whisperer->member->statuses_count;

        $storedWhispers = $this->tweetRepository->countHowManyStatusesFor($options[FetchAuthoredTweetInterface::SCREEN_NAME]);

        if ($storedWhispers === $whispers) {
            SkippableMessageException::stopMessageConsumption();
        }

        if (
            $whispers >= $this->selectors::MAX_AVAILABLE_TWEETS_PER_USER
            && $storedWhispers < $this->selectors::MAX_AVAILABLE_TWEETS_PER_USER
        ) {
            SkippableMessageException::continueMessageConsumption();
        }

        return $whisperer;
    }
}
