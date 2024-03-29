<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Persistence;

use App\Search\Domain\Entity\SavedSearch;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Domain\Persistence\TweetPersistenceLayerInterface;
use App\Twitter\Domain\Publication\Repository\TimelyStatusRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\PersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\PublishersListRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetCurationLoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TaggedTweetRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\TimelyStatusRepositoryTrait;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Infrastructure\Http\Exception\InsertDuplicatesException;
use App\Twitter\Infrastructure\Http\Normalizer\Normalizer;
use App\Twitter\Infrastructure\Publication\Dto\TweetCollection;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use Closure;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\EntityManagerClosed;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use Psr\Log\LoggerInterface;
use function count;
use function is_array;

class TweetPersistenceLayer implements TweetPersistenceLayerInterface
{
    use HttpClientTrait;
    use LoggerTrait;
    use PersistenceLayerTrait;
    use PublishersListRepositoryTrait;
    use TweetCurationLoggerTrait;
    use TweetRepositoryTrait;
    use TaggedTweetRepositoryTrait;
    use TimelyStatusRepositoryTrait;

    public ManagerRegistry $registry;

    private LoggerInterface $appLogger;

    private EntityManagerInterface $entityManager;

    public function __construct(
        TimelyStatusRepositoryInterface $timelyStatusRepository,
        ManagerRegistry $registry,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->registry               = $registry;
        $this->timelyStatusRepository = $timelyStatusRepository;
        $this->entityManager          = $entityManager;
        $this->appLogger              = $logger;
    }

    /**
     * @param EntityManagerInterface $entityManager
     */
    private function flushAndResetManagerOnUniqueConstraintViolation(
        EntityManagerInterface $entityManager
    ): void {
        try {
            $entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            $this->registry->resetManager('default');

            InsertDuplicatesException::throws($exception);
        }
    }

    private function logStatusToBeInserted(TweetInterface $status): void
    {
        if ($status->getId() === null) {
            $this->collectStatusLogger->logStatus($status);
        }
    }

    private function persistStatus(
        CollectionInterface $tweetsCollection,
        TaggedTweet         $taggedTweet,
        ?PublishersList     $twitterList = null
    ): CollectionInterface {
        $extract = $taggedTweet->toLegacyProps();
        $status  = $this->taggedTweetRepository->convertPropsToStatus($extract, $twitterList);

        $this->logStatusToBeInserted($status);

        $status = $this->unarchiveStatus($status, $this->entityManager);
        $this->refreshUpdatedAt($status);

        $this->persistTimelyStatus($twitterList, $status);

        $this->entityManager->persist($status);

        return $tweetsCollection->add($status);
    }

    private function persistTimelyStatus(
        ?PublishersList $twitterList,
        TweetInterface $status
    ): void {
        if ($twitterList instanceof PublishersList) {
            $timelyStatus = $this->timelyStatusRepository->fromTweetInList(
                $status,
                $twitterList
            );
            $this->entityManager->persist($timelyStatus);
        }
    }

    public function persistSearchQueryBasedTweetsCollection(
        AccessToken $identifier,
        SavedSearch $savedSearch,
        array $tweets
    ): array {
        $propertiesCollection = Normalizer::normalizeTweets(
            $tweets,
            $this->tokenSetter($identifier),
            $this->appLogger
        );

        $tweetsCollection = TweetCollection::fromArray([]);

        /** @var TaggedTweet $taggedTweet */
        foreach ($propertiesCollection->toArray() as $_ => $taggedTweet) {
            try {
                $tweetsCollection = $this->persistStatus(
                    $tweetsCollection,
                    $taggedTweet
                );
            } catch (\Throwable $exception) {
                $this->appLogger->error($exception->getMessage());

                if ($exception instanceof EntityManagerClosed) {
                    $this->entityManager = $this->registry->resetManager('default');
                }
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($this->entityManager);

        return [
            self::PROPERTY_NORMALIZED_STATUS => $propertiesCollection,
            self::PROPERTY_SEARCH_QUERY      => $savedSearch,
            self::PROPERTY_TWEET => $tweetsCollection
        ];
    }

    public function persistTweetsCollection(
        array $statuses,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): array {
        $propertiesCollection = Normalizer::normalizeTweets(
            $statuses,
            $this->tokenSetter($identifier),
            $this->appLogger
        );

        $tweetsCollection = TweetCollection::fromArray([]);

        /** @var TaggedTweet $taggedTweet */
        foreach ($propertiesCollection->toArray() as $_ => $taggedTweet) {
            try {
                $tweetsCollection = $this->persistStatus(
                    $tweetsCollection,
                    $taggedTweet,
                    $twitterList
                );
            } catch (\Throwable $exception) {
                $this->appLogger->error($exception->getMessage());

                if ($exception instanceof EntityManagerClosed) {
                    $this->entityManager = $this->registry->resetManager('default');
                }
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($this->entityManager);

        $firstStatus = $tweetsCollection->first();
        $screenName  = $firstStatus instanceof TweetInterface ?
            $firstStatus->getScreenName() :
            null;

        return [
            self::PROPERTY_NORMALIZED_STATUS => $propertiesCollection,
            self::PROPERTY_SCREEN_NAME       => $screenName,
            self::PROPERTY_TWEET             => $tweetsCollection
        ];
    }

    private function refreshUpdatedAt(TweetInterface $status): void
    {
        if ($status->getId()) {
            try {
                $status->setUpdatedAt(
                    new DateTime('now', new DateTimeZone('UTC'))
                );
            } catch (Exception $exception) {
                $this->appLogger->error($exception->getMessage());
            }
        }
    }

    private function tokenSetter(AccessToken $accessToken): Closure
    {
        return function ($extract) use ($accessToken) {
            $extract['identifier'] = $accessToken->accessToken();

            return $extract;
        };
    }

    /**
     * @param TweetInterface         $status
     * @param EntityManagerInterface $entityManager
     *
     * @return Tweet
     */
    public function unarchiveStatus(
        TweetInterface         $status,
        EntityManagerInterface $entityManager
    ): TweetInterface {
        if (!($status instanceof ArchivedTweet)) {
            return $status;
        }

        $archivedStatus = $status;
        $status         = Tweet::fromArchivedStatus($archivedStatus);

        $entityManager->remove($archivedStatus);

        return $status;
    }

    public function saveTweetsAuthoredByMemberHavingScreenName(
        array $statuses,
        string $screenName,
        CurationSelectorsInterface $selectors
    ): ?int
    {
        $success = null;

        if (!is_array($statuses) || count($statuses) === 0) {
            return $success;
        }

        $twitterList = null;
        $membersListId = $selectors->membersListId();
        if ($membersListId !== null) {
            /** @var PublishersList $twitterList */
            $twitterList = $this->publishersListRepository->findOneBy(
                ['id' => $membersListId]
            );
        }

        $this->collectStatusLogger->logHowManyItemsHaveBeenFetched(
            $statuses,
            $screenName
        );

        $savedStatuses = $this->saveStatuses(
            $statuses,
            $selectors,
            $twitterList
        );

        return $this->collectStatusLogger->logHowManyItemsHaveBeenSaved(
            $savedStatuses->count(),
            $screenName
        );
    }

    private function saveStatuses(
        array                      $statuses,
        CurationSelectorsInterface $selectors,
        PublishersList $twitterList = null
    ): CollectionInterface {
        return $this->persistenceLayer->persistTweetsCollection(
            $statuses,
            new AccessToken($this->httpClient->getAccessToken()),
            $twitterList
        );
    }
}
