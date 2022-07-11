<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Persistence;

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
use App\Twitter\Infrastructure\Publication\Dto\StatusCollection;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use Closure;
use DateTime;
use DateTimeZone;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
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

    public const PROPERTY_NORMALIZED_STATUS = 'normalized_status';
    public const PROPERTY_SCREEN_NAME       = 'screen_name';
    public const PROPERTY_STATUS            = 'status';

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

    public function persistTweetsCollection(
        array $statuses,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): array {
        $propertiesCollection = Normalizer::normalizeAll(
            $statuses,
            $this->tokenSetter($identifier),
            $this->appLogger
        );

        $statusCollection = StatusCollection::fromArray([]);

        /** @var TaggedTweet $taggedTweet */
        foreach ($propertiesCollection->toArray() as $key => $taggedTweet) {
            try {
                $statusCollection = $this->persistStatus(
                    $statusCollection,
                    $taggedTweet,
                    $twitterList
                );
            } catch (ORMException $exception) {
                if ($exception->getMessage() === ORMException::entityManagerClosed()->getMessage()) {
                    $this->entityManager = $this->registry->resetManager('default');
                }
            } catch (Exception $exception) {
                $this->appLogger->info($exception->getMessage());
            }
        }

        $this->flushAndResetManagerOnUniqueConstraintViolation($this->entityManager);

        $firstStatus = $statusCollection->first();
        $screenName  = $firstStatus instanceof TweetInterface ?
            $firstStatus->getScreenName() :
            null;

        return [
            self::PROPERTY_NORMALIZED_STATUS => $propertiesCollection,
            self::PROPERTY_SCREEN_NAME       => $screenName,
            self::PROPERTY_STATUS            => $statusCollection
        ];
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
        CollectionInterface $statuses,
        TaggedTweet $taggedTweet,
        ?PublishersList $twitterList
    ): CollectionInterface {
        $extract = $taggedTweet->toLegacyProps();
        $status  = $this->taggedTweetRepository->convertPropsToStatus($extract, $twitterList);

        $this->logStatusToBeInserted($status);

        $status = $this->unarchiveStatus($status, $this->entityManager);
        $this->refreshUpdatedAt($status);

        $this->persistTimelyStatus($twitterList, $status);

        $this->entityManager->persist($status);

        return $statuses->add($status);
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

    public function savePublicationsForScreenName(
        array $statuses,
        string $screenName,
        CurationSelectorsInterface $selectors
    ) {
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
