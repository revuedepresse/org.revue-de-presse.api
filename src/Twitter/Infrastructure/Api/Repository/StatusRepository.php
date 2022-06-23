<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Api\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\Repository\ExtremumAwareInterface;
use App\Twitter\Domain\Publication\StatusInterface;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Publication\Dto\TaggedStatus;
use App\Twitter\Infrastructure\Api\Entity\Status;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Api\Accessor\Exception\NotFoundStatusException;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\QueryBuilder;
use Exception;
use function array_key_exists;
use function max;
use function min;
use const JSON_THROW_ON_ERROR;

/**
 * @author revue-de-presse.org <thierrymarianne@users.noreply.github.com>
 *
 * @method Status|null find($id, $lockMode = null, $lockVersion = null)
 * @method Status|null findOneBy(array $criteria, array $orderBy = null)
 * @method Status[]    findAll()
 * @method Status[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StatusRepository extends ArchivedStatusRepository
{
    /**
     * @var ArchivedStatusRepository
     */
    public ArchivedStatusRepository $archivedStatusRepository;

    /**
     * @param $properties
     * @return Status
     */
    public function fromArray($properties)
    {
        $status = new Status();

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

    /**
     * @param Status $status
     *
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function save(Status $status): void
    {
        $this->getEntityManager()->persist($status);
        $this->getEntityManager()->flush();
    }

    public function setOauthTokens($oauthTokens)
    {
        $this->oauthTokens = $oauthTokens;

        return $this;
    }

    public function getAlias(): string
    {
        return 'status';
    }

    /**
     * @throws \App\Twitter\Infrastructure\Api\Accessor\Exception\NotFoundStatusException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function tweetSharedByMemberHavingScreenName(string $screenName, $orderBy = null): StatusInterface
    {
        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder->select('t.id as identifier')
            ->andWhere('LOWER(t.screenName) = :screenName')
            ->setMaxResults(1);

        if (is_array($orderBy)) {
            $column = key($orderBy);
            reset($orderBy);

            $queryBuilder->orderBy($column, $orderBy[$column]);
        }

        $queryBuilder->setParameter('screenName', strtolower($screenName));

        try {
            $statusIdentifier = $queryBuilder->getQuery()->getSingleResult()['identifier'];
            $status = $this->findOneBy(['id' => $statusIdentifier], $orderBy);

            if (!$status instanceof StatusInterface) {
                throw new NoResultException();
            }

        } catch (NoResultException $exception) {
            throw new NotFoundStatusException(sprintf(
                'No status was found for member with screen name "%s"',
                $screenName
            ), $exception->getCode(), $exception);
        }

        return $status;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Api\Accessor\Exception\NotFoundStatusException
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     */
    public function countHowManyStatusesFor(string $screenName): int
    {
        $member = $this->memberRepository->memberHavingScreenName($screenName);

        if ($member->totalTweets() !== 0) {
            $status = $this->tweetSharedByMemberHavingScreenName($screenName, ['createdAt' => 'DESC']);

            $decodedStatusDocument = json_decode(
                $status->getApiDocument(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            if ($decodedStatusDocument['user']['statuses_count'] > CurationSelectorsInterface::MAX_AVAILABLE_TWEETS_PER_USER) {
                return $decodedStatusDocument['user']['statuses_count'];
            }

            return $member->totalTweets();
        }

        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->select('COUNT(DISTINCT s.statusId) as count_')
            ->andWhere('s.screenName = :screenName');

        $queryBuilder->setParameter('screenName', $screenName);

        $totalStatuses = $queryBuilder->getQuery()->getSingleScalarResult();
        $totalStatuses = (int) $totalStatuses + $this->archivedStatusRepository->countHowManyStatusesFor($screenName);

        $this->memberRepository->declareTotalStatusesOfMemberWithName($totalStatuses, $screenName);

        return $totalStatuses;
    }

    /**
     * @param $screenName
     *
     * @return MemberInterface
     * @throws DBALException
     * @throws NotFoundStatusException
     */
    public function updateLastStatusPublicationDate($screenName): MemberInterface
    {
        $member = $this->memberRepository->memberHavingScreenName($screenName);

        $lastStatus = $this->getLastKnownStatusFor($screenName);
        $member->setLastStatusPublicationDate($lastStatus->getCreatedAt());

        return $this->memberRepository->saveMember($member);
    }

    /**
     * @param TaggedStatus $taggedStatus
     *
     * @return StatusInterface
     * @throws Exception
     */
    public function reviseDocument(TaggedStatus $taggedStatus): StatusInterface
    {
        /** @var Status $status */
        $status = $this->findOneBy(
            ['statusId' => $taggedStatus->documentId()]
        );

        if (!$status instanceof Status) {
            $status = $this->archivedStatusRepository->findOneBy(
                ['statusId' => $taggedStatus->documentId()]
            );
        }

        $status->setApiDocument($taggedStatus->document());
        $status->setIdentifier($taggedStatus->token());
        $status->setText($taggedStatus->text());

        return $status->setUpdatedAt(
            new DateTime('now', new \DateTimeZone('UTC'))
        );
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param DateTime    $earliestDate
     * @param DateTime    $latestDate
     */
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
     * @param string    $aggregateName
     * @param DateTime $earliestDate
     * @param DateTime $latestDate
     * @return ArrayCollection
     */
    public function selectAggregateStatusCollection(
        string $aggregateName,
        DateTime $earliestDate,
        DateTime $latestDate
    ) {
        $queryBuilder = $this->createQueryBuilder('s');

        $this->between($queryBuilder, $earliestDate, $latestDate);

        $queryBuilder->join(
            's.aggregates',
            'a'
        );
        $queryBuilder->andWhere('a.name = :aggregate_name');
        $queryBuilder->setParameter('aggregate_name', $aggregateName);

        return new ArrayCollection($queryBuilder->getQuery()->getResult());
    }

    /**
     * For ascending order finding, the min status id can be found,
     * whereas
     * for descending order finding, the max status id can be found
     *
     * Both can be found at a specific date
     *
     * @param string $screenName
     * @param string $direction
     * @param string $before
     *
     * @return array
     * @throws NonUniqueResultException
     */
    public function findNextExtremum(
        string $screenName,
        string $direction = self::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array {
        $nextExtremum = $this->archivedStatusRepository
            ->findNextExtremum($screenName, $direction, $before);

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

        $queryBuilder->setParameter('screenName', $screenName);

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
                $screenName,
                $extremum,
                $nextExtremum,
                $direction
            );
        } catch (NoResultException $exception) {
            return [];
        }
    }

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

            return [self::EXTREMUM_STATUS_ID => $this->memberRepository->declareMinStatusIdForMemberWithScreenName(
                (string) $nextMinimum,
                $screenName
            )->minStatusId];
        }

        $nextMaximum = max(
            (int) $extremum[self::EXTREMUM_STATUS_ID],
            $nextExtremum[self::EXTREMUM_STATUS_ID]
        );

        return [self::EXTREMUM_STATUS_ID => $this->memberRepository->declareMaxStatusIdForMemberWithScreenName(
            (string) $nextMaximum,
            $screenName
        )->maxStatusId];
    }

    /**
     * @param $status
     *
     * @return MemberInterface
     */
    public function declareMaximumStatusId($status): MemberInterface
    {
        $maxStatus = $status->id_str;

        return $this->memberRepository->declareMaxStatusIdForMemberWithScreenName(
            $maxStatus,
            $status->user->screen_name
        );
    }

    /**
     * @param $status
     *
     * @return MemberInterface
     */
    public function declareMinimumStatusId($status): MemberInterface
    {
        $minStatus = $status->id_str;

        return $this->memberRepository->declareMinStatusIdForMemberWithScreenName(
            $minStatus,
            $status->user->screen_name
        );
    }

    /**
     * @param PublishersList $aggregate
     * @return array
     */
    public function findByAggregate(PublishersList $aggregate)
    {
        $queryBuilder = $this->createQueryBuilder('s');
        $queryBuilder->join('s.aggregates', 'a');
        $queryBuilder->andWhere('a.id = :id');
        $queryBuilder->setParameter('id', $aggregate->getId());

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param string $screenName
     * @return array
     * @throws DBALException
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
     * @param string $screenName
     * @return null|Status
     * @throws DBALException
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
     * @param string $screenName
     * @return Status
     * @throws NotFoundStatusException
     * @throws DBALException
     */
    private function getLastKnownStatusFor(string $screenName): StatusInterface {
        $result = $this->howManyStatusesForMemberHavingScreenName($screenName);

        $lastStatus = null;
        if ($result[0]['total_statuses'] > 0) {
            $lastStatus = $this->getLastKnownStatusForMemberHavingScreenName($screenName);
        }

        if (!$lastStatus instanceof StatusInterface) {
            throw new NotFoundStatusException(sprintf(
                'No status has been collected for member with screen name "%s"',
                $screenName
            ));
        }

        return $lastStatus;
    }
}
