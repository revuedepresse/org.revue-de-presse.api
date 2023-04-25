<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Http\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use DateTime;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use function array_key_exists;
use function max;
use function min;
use const JSON_THROW_ON_ERROR;

/**
 * @method Tweet|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tweet|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tweet[]    findAll()
 * @method Tweet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TweetRepository extends ArchivedTweetRepository
{
    public ArchivedTweetRepository $archivedTweetRepository;

    public function fromArray($properties): TweetInterface
    {
        $status = new Tweet();

        $status->setScreenName($properties['screen_name']);
        $status->setName($properties['name']);
        $status->setText($properties['text']);
        $status->setUserAvatar($properties['user_avatar']);
        $status->setIdentifier($properties['identifier']);
        $status->setCreatedAt($properties['created_at']);
        $status->setIndexed(false);

        if (array_key_exists('aggregate', $properties)) {
            $status->addToAggregates($properties['aggregate']);
        }

        return $status;
    }

    public function save(Tweet $status): void
    {
        $this->getEntityManager()->persist($status);
        $this->getEntityManager()->flush();
    }

    public function getAlias(): string
    {
        return 'status';
    }

    public function tweetSharedByMemberHavingScreenName(string $screenName, $orderBy = null): TweetInterface
    {
        $tableAlias = 't';
        $queryBuilder = $this->createQueryBuilder($tableAlias);
        $queryBuilder
            ->select("{$tableAlias}.id as identifier")
            ->andWhere("LOWER({$tableAlias}.screenName) = :screenName")
            ->setMaxResults(1);

        if (is_array($orderBy)) {
            $column = key($orderBy);
            reset($orderBy);

            $queryBuilder->addSelect("{$tableAlias}.{$column}");
            $queryBuilder->orderBy("{$tableAlias}.{$column}", $orderBy[$column]);
        }

        $queryBuilder->setParameter('screenName', strtolower($screenName));

        try {
            $statusIdentifier = $queryBuilder->getQuery()->getSingleResult()['identifier'];
            $status = $this->findOneBy(['id' => $statusIdentifier], $orderBy);

            if (!($status instanceof TweetInterface)) {
                throw new NoResultException();
            }
        } catch (NoResultException $exception) {
            TweetNotFoundException::throws(
                $screenName,
                $exception,
                $this->appLogger
            );
        }

        return $status;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \JsonException
     */
    public function howManyTweetsHaveBeenCollectedForMemberHavingUserName(string $screenName): int
    {
        $member = $this->memberRepository->memberHavingScreenName($screenName);
        $totalStatuses = $this->refreshTotalTweetsPublishedByMemberHavingScreenName($screenName);

        if ($member->totalTweets() !== 0) {
            try {
                $status = $this->tweetSharedByMemberHavingScreenName($screenName, ['createdAt' => 'DESC']);
            } catch (TweetNotFoundException) {
                return $member->totalTweets();
            }

            $decodedStatusDocument = json_decode(
                $status->getApiDocument(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if ($decodedStatusDocument['user']['statuses_count'] > CurationSelectorsInterface::MAX_AVAILABLE_TWEETS_PER_USER) {
                return min($totalStatuses, $decodedStatusDocument['user']['statuses_count']);
            }

            return $member->totalTweets();
        }

        return $totalStatuses;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    public function updateLastStatusPublicationDate(string $screenName): MemberInterface
    {
        $member = $this->memberRepository->memberHavingScreenName($screenName);

        $lastStatus = $this->getLastKnownStatusFor($screenName);

        $member->setMaxTweetId((int) $lastStatus->getStatusId());
        $member->setLastStatusPublicationDate($lastStatus->getCreatedAt());

        return $this->memberRepository->saveMember($member);
    }

    /**
     * @throws \Exception
     */
    public function reviseDocument(TaggedTweet $taggedTweet): TweetInterface
    {
        /** @var Tweet $status */
        $status = $this->findOneBy(
            ['statusId' => $taggedTweet->documentId()]
        );

        if (!($status instanceof Tweet)) {
            $status = $this->archivedTweetRepository->findOneBy(
                ['statusId' => $taggedTweet->documentId()]
            );
        }

        $status->setScreenName($taggedTweet->screenName());
        $status->setApiDocument($taggedTweet->document());
        $status->setIdentifier($taggedTweet->token());
        $status->setText($taggedTweet->text());

        return $status->setUpdatedAt(
            new DateTime('now', new \DateTimeZone('UTC'))
        );
    }

    private function between(
        QueryBuilder $queryBuilder,
        DateTime $earliestDate,
        DateTime $latestDate
    ): void {
        $queryBuilder->andWhere('s.createdAt >= :after');
        $queryBuilder->setParameter('after', $earliestDate);

        $queryBuilder->andWhere('s.createdAt <= :before');
        $queryBuilder->setParameter('before', $latestDate);
    }

    /**
     * For ascending order finding, the min tweet id can be found,
     * whereas
     * for descending order finding, the max tweet id can be found
     *
     * Both can be found at a specific date
     *
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string  $memberUsername,
        string  $direction = self::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array {
        $nextExtremum = $this->archivedTweetRepository
            ->findNextExtremum($memberUsername, $direction, $before);

        if (array_key_exists(self::EXTREMUM_FROM_MEMBER, $nextExtremum)) {
            return [
                self::EXTREMUM_STATUS_ID => $nextExtremum[self::EXTREMUM_STATUS_ID]
            ];
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('s.statusId')
            ->andWhere('s.screenName = :screenName')
            ->andWhere('s.apiDocument is not null')
            ->orderBy('CAST(s.statusId AS bigint)', $direction)
            ->setMaxResults(1);

        $queryBuilder->setParameter('screenName', $memberUsername);

        if ($before) {
            $queryBuilder->andWhere('DATE(s.createdAt) = :date');
            $queryBuilder->setParameter(
                'date',
                (new DateTime($before, new \DateTimeZone('UTC')))
                    ->format('Y-m-d')
            );
        }

        try {
            $extremum = $queryBuilder->getQuery()->getSingleResult();

            return $this->declareMemberExtremum(
                $memberUsername,
                $extremum,
                $nextExtremum,
                $direction
            );
        } catch (NoResultException $exception) {
            return [];
        }
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     */
    public function declareMemberExtremum(
        string $screenName,
        array $extremum,
        array $nextExtremum,
        $direction = ExtremumAwareInterface::FINDING_IN_ASCENDING_ORDER
    ): array {
        if ($direction === 'asc') {
            $nextMinimum = min(
                (int) $extremum[self::EXTREMUM_STATUS_ID],
                $nextExtremum[self::EXTREMUM_STATUS_ID]
            );

            return [self::EXTREMUM_STATUS_ID => $this->memberRepository->declareMinTweetIdForMemberHavingScreenName(
                (string) $nextMinimum,
                $screenName
            )->minTweetId()];
        }

        $nextMaximum = max(
            (int) $extremum[self::EXTREMUM_STATUS_ID],
            $nextExtremum[self::EXTREMUM_STATUS_ID]
        );

        return [self::EXTREMUM_STATUS_ID => $this->memberRepository->declareMaxTweetIdForMemberHavingScreenName(
            (string) $nextMaximum,
            $screenName
        )->maxTweetId()];
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     */
    public function declareMaximumStatusId($status): MemberInterface
    {
        if ($status instanceof TweetInterface) {
            $maxStatus = $status->statusId();

            return $this->memberRepository->declareMaxTweetIdForMemberHavingScreenName(
                $maxStatus,
                $status->getScreenName()
            );
        }

        $maxStatus = $status->id_str;

        return $this->memberRepository->declareMaxTweetIdForMemberHavingScreenName(
            $maxStatus,
            $status->user->screen_name
        );
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     */
    public function declareMinimumStatusId($status): MemberInterface
    {
        if ($status instanceof TweetInterface) {
            $minStatus = $status->statusId();

            return $this->memberRepository->declareMinTweetIdForMemberHavingScreenName(
                $minStatus,
                $status->screenName()
            );
        }

        $minStatus = $status->id_str;

        return $this->memberRepository->declareMinTweetIdForMemberHavingScreenName(
            $minStatus,
            $status->user->screen_name
        );
    }

    public function findByAggregate(PublishersList $aggregate)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.aggregates', 'a');
        $queryBuilder->andWhere('a.id = :id');
        $queryBuilder->setParameter('id', $aggregate->getId());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function howManyStatusesForMemberHavingScreenName($screenName): array
    {
        $connection = $this->getEntityManager()->getConnection();
        $query = <<<QUERY
            SELECT count(*) total_statuses
            FROM weaving_status s
            WHERE s.ust_full_name = ?
QUERY;

        $statement = $connection->executeQuery($query, [$screenName], [\PDO::PARAM_STR]);

        return $statement->fetchAllAssociative();
    }

    /**
     * @throws \Doctrine\DBAL\Exception
     */
    private function getLastKnownStatusForMemberHavingScreenName(string $screenName)
    {
        $connection = $this->getEntityManager()->getConnection();
        $query = <<<QUERY
              SELECT id, publication_date_time
              FROM (
                SELECT s.ust_id AS id,
                s.ust_created_at publication_date_time
                FROM weaving_status s
                WHERE s.ust_full_name = ?
              ) select_
              ORDER BY select_.publication_date_time DESC
              LIMIT 1
QUERY;

        $statement = $connection->executeQuery($query, [$screenName], [\PDO::PARAM_STR]);
        $result = $statement->fetchAllAssociative();

        $criteria = ['id' => $result[0]['id']];

        return $this->findOneBy($criteria);
    }

    /**
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \Doctrine\DBAL\Exception
     */
    private function getLastKnownStatusFor(string $screenName): TweetInterface {
        $result = $this->howManyStatusesForMemberHavingScreenName($screenName);

        $lastStatus = null;
        if ($result[0]['total_statuses'] > 0) {
            $lastStatus = $this->getLastKnownStatusForMemberHavingScreenName($screenName);
        }

        if (!($lastStatus instanceof TweetInterface)) {
            TweetNotFoundException::throws($screenName, logger: $this->appLogger);
        }

        return $lastStatus;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    private function refreshTotalTweetsPublishedByMemberHavingScreenName(string $screenName): int
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();
        $totalStatuses = (int)$totalStatuses + $this->archivedTweetRepository->howManyTweetsHaveBeenCollectedForMemberHavingUserName($screenName);

        $this->memberRepository->declareTotalStatusesOfMemberWithName($totalStatuses, $screenName);

        return $totalStatuses;
    }

    public function persistTweetsCollection(
        array $tweets,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): CollectionInterface {
        return $this->archivedTweetRepository->persistTweetsCollection(
            $tweets,
            $identifier,
            $twitterList
        );
    }
}
