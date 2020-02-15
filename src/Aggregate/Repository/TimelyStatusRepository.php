<?php

namespace App\Aggregate\Repository;

use App\Aggregate\Controller\SearchParams;
use App\Aggregate\Entity\TimelyStatus;
use App\Conversation\ConversationAwareTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use App\Api\Entity\Aggregate;
use App\Api\Entity\StatusInterface;
use App\Api\Repository\AggregateRepository;
use Laminas\Service;
use WeavingTheWeb\Bundle\ApiBundle\Repository\StatusRepository;

class TimelyStatusRepository extends ServiceEntityRepository
{
    const TABLE_ALIAS = 't';

    use PaginationAwareTrait;
    use ConversationAwareTrait;

    /**
     * @var StatusRepository
     */
    public $statusRepository;

    /**
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @param array $properties
     * @return TimelyStatus|\App\TimeRange\TimeRangeAwareInterface
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function fromArray(array $properties)
    {
        $status = $this->statusRepository->findOneBy(['id' => $properties['status_id']]);
        $timelyStatus = $this->findOneBy([
            'status' => $status
        ]);

        if ($timelyStatus instanceof TimelyStatus) {
            return $timelyStatus->updateTimeRange();
        }

        $aggregate = $this->aggregateRepository->findOneBy([
            'id' => $properties['aggregate_id'],
            'screenName' => $properties['member_name']
        ]);

        if (!($aggregate instanceof Aggregate)) {
            $aggregate = $this->aggregateRepository->findOneBy([
                'name' => $properties['aggregate_name'],
                'screenName' => $properties['member_name']
            ]);

            if (!($aggregate instanceof Aggregate)) {
                $aggregate = $this->aggregateRepository->make(
                    $properties['member_name'],
                    $properties['aggregate_name']
                );
                $this->aggregateRepository->save($aggregate);
            }
        }

        return new TimelyStatus(
            $status,
            $aggregate,
            $status->getCreatedAt()
        );
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function selectStatuses()
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $queryBuilder->select(
            [
                's.userAvatar as author_avatar',
                's.text',
                's.screenName as screen_name',
                's.id',
                's.statusId as status_id',
                's.starred',
                's.apiDocument original_document'
            ]
        )
            ->leftJoin('t.status', 's')
            ->orderBy('t.timeRange', 'asc')
            ->orderBy('t.publicationDateTime', 'desc')
            ->setMaxResults(50)
        ;

        return $queryBuilder;
    }

    /**
     * @param StatusInterface $status
     * @param Aggregate|null  $aggregate
     * @return TimelyStatus
     * @throws \Exception
     */
    public function fromAggregatedStatus(
        StatusInterface $status,
        Aggregate $aggregate = null
    ): TimelyStatus {
        $timelyStatus = $this->findOneBy([
            'status' => $status
        ]);

        if ($timelyStatus instanceof TimelyStatus) {
            return $timelyStatus->updateTimeRange();
        }

        return new TimelyStatus(
            $status,
            $aggregate,
            $status->getCreatedAt()
        );
    }

    /**
     * @param TimelyStatus $timelyStatus
     * @param bool         $doNotFlush
     * @return TimelyStatus
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveTimelyStatus(TimelyStatus $timelyStatus, $doNotFlush = false)
    {
        $this->getEntityManager()->persist($timelyStatus);
        $this->getEntityManager()->flush();

        return $timelyStatus;
    }

    /**
     * @param $searchParams
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTotalPages($searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param SearchParams $searchParams
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findStatuses(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);
        $this->applyCriteria($queryBuilder, $searchParams);

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults($searchParams->getPageSize());

        $queryBuilder->orderBy(self::TABLE_ALIAS.'.publicationDateTime', 'DESC');

        $results = $queryBuilder->getQuery()->getArrayResult();
        $statuses = array_map(
            function ($status) {
                $extractedProperties = [
                    'status' => $this->extractStatusProperties(
                        [$status],
                        false)[0]
                ];

                unset($status['original_document']);
                unset($status['screen_name']);
                unset($status['author_avatar']);
                unset($status['status_id']);

                return array_merge($status, $extractedProperties);
            },
            $results
        );

        return $statuses;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @throws \Doctrine\DBAL\DBALException
     */
    private function applyCriteria(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ) {
        $queryBuilder->select('s.apiDocument as original_document');
        $queryBuilder->addSelect('t.memberName as screen_name');
        $queryBuilder->addSelect('t.memberName as screenName');
        $queryBuilder->addSelect('t.aggregateName as aggregateName');
        $queryBuilder->addSelect('a.id as aggregateId');
        $queryBuilder->addSelect('s.id as id');
        $queryBuilder->addSelect('s.statusId as twitterId');
        $queryBuilder->addSelect('s.userAvatar as author_avatar');
        $queryBuilder->addSelect('s.text');
        $queryBuilder->addSelect('s.statusId as status_id');

        if ($searchParams->hasKeyword()) {
            $queryBuilder->andWhere('s.text like :keyword');
            $queryBuilder->setParameter(
                'keyword',
                sprintf('%%%s%%', $searchParams->getKeyword())
            );
        }

        $queryBuilder->innerJoin('t.status', 's');
        $queryBuilder->innerJoin('t.aggregate', 'a');

        $params = $searchParams->getParams();
        if (array_key_exists('memberName', $params)) {
            $queryBuilder->andWhere('t.memberName = :member_name');
            $queryBuilder->setParameter('member_name', $params['memberName']);
        }
    }
}
