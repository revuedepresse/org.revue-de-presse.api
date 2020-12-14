<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\NewsReview\Domain\Repository\SearchParamsInterface;
use App\PublishersList\Repository\PaginationAwareTrait;
use App\Conversation\ConversationAwareTrait;
use App\Twitter\Domain\Publication\Repository\PaginationAwareRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\SearchParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class HighlightRepository extends ServiceEntityRepository implements PaginationAwareRepositoryInterface
{
    use PaginationAwareTrait;
    use ConversationAwareTrait;
    use LoggerTrait;

    public string $aggregate;

    public string $adminRouteName;

    private const TABLE_ALIAS = 'h';

    /**
     * @param SearchParams $searchParams
     * @return int
     * @throws NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param SearchParams $searchParams
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findHighlights(SearchParams $searchParams): array
    {
        $queryBuilder = $this->createQueryBuilder(self::TABLE_ALIAS);

        $queryBuilder->select('s.statusId as status_id');
        $queryBuilder->addSelect('s.id as id');
        $queryBuilder->addSelect('s.apiDocument as original_document');
        $queryBuilder->addSelect('s.text');
        $queryBuilder->addSelect('s.createdAt as publicationDateTime');
        $queryBuilder->addSelect('s.screenName as screen_name');
        $queryBuilder->addSelect("s.createdAt as last_update");
        $queryBuilder->addSelect('MAX(COALESCE(p.totalRetweets, h.totalRetweets)) as total_retweets');
        $queryBuilder->addSelect('MAX(COALESCE(p.totalFavorites, h.totalFavorites)) as total_favorites');

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());

        $maxResults = min($searchParams->getPageSize(), 10);
        if ($this->accessingAdministrativeRoute($searchParams)) {
            $maxResults = 100;
        }

        $queryBuilder->setMaxResults($maxResults);

        $this->applyCriteria($queryBuilder, $searchParams);

        $queryBuilder->groupBy('h.status');
        $queryBuilder->addOrderBy('total_retweets', 'DESC');

        $results = $queryBuilder->getQuery()->getArrayResult();

        return [
            'aggregates' => $this->selectDistinctAggregates($searchParams),
            'statuses' => $this->mapStatuses($searchParams, $results),
        ];
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     */
    public function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $queryBuilder->innerJoin(self::TABLE_ALIAS.'.status', 's');
        $queryBuilder->innerJoin(self::TABLE_ALIAS.'.member', 'm');

        if ($searchParams->hasParam('term')) {
            $queryBuilder->innerJoin(
                'Status:Keyword',
                'k',
                Join::WITH,
                's.id = k.status'
            );
        }

        $this->applyConstraintAboutPopularity($queryBuilder, $searchParams);
        $this->applyConstraintAboutPublicationDateTime($queryBuilder, $searchParams)
        ->applyConstraintAboutPublicationDateOfRetweetedStatus($queryBuilder, $searchParams)
        ->applyConstraintAboutRetweetedStatus($queryBuilder, $searchParams)
        ->applyConstraintAboutRelatedAggregate($queryBuilder, $searchParams)
        ->applyConstraintAboutSelectedAggregates($queryBuilder, $searchParams);

        if ($searchParams->hasParam('term')) {
            $this->applyConstraintAboutTerm($queryBuilder, $searchParams);
        }

        $queryBuilder->setParameter('startDate', $searchParams->getParams()['startDate']);
        if ($this->overMoreThanADay($searchParams)) {
            $queryBuilder->setParameter('endDate', $searchParams->getParams()['endDate']);
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return HighlightRepository
     */
    private function applyConstraintAboutPublicationDateOfRetweetedStatus(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ): self {
        $retweetedStatusPublicationDate = "COALESCE(
                DATE(
                    DATEADD(" .
            self::TABLE_ALIAS . ".retweetedStatusPublicationDate, 1, 'HOUR'
                    )
                ),
                DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR'))
            )";

        if ($this->overOneDay($searchParams) && !$searchParams->hasParam('term')) {
            $queryBuilder->andWhere($retweetedStatusPublicationDate . " = :startDate");
        }

        if ($this->overMoreThanADay($searchParams)) {
            $queryBuilder->andWhere($retweetedStatusPublicationDate . " >= :startDate");
            $queryBuilder->andWhere($retweetedStatusPublicationDate . " <= :endDate");
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return HighlightRepository
     */
    private function applyConstraintAboutTerm(QueryBuilder $queryBuilder, SearchParams $searchParams): self
    {
        $queryBuilder->andWhere('k.keyword LIKE :term');
        $queryBuilder->setParameter('term', $searchParams->getParams()['term'].'%');

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return HighlightRepository
     */
    private function applyConstraintAboutRetweetedStatus(QueryBuilder $queryBuilder, SearchParams $searchParams): self
    {
        $excludeRetweets = !$searchParams->getParams()['includeRetweets'];
        if ($excludeRetweets) {
            $queryBuilder->andWhere(self::TABLE_ALIAS . ".isRetweet = 0");
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return HighlightRepository
     */
    private function applyConstraintAboutPublicationDateTime(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ): self {
         if ($this->overMoreThanADay($searchParams)) {
            $queryBuilder->andWhere("DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR')) >= :startDate");
            $queryBuilder->andWhere("DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR')) <= :endDate");

            return $this;
        }

        $queryBuilder->andWhere("DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR')) = :startDate");

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return HighlightRepository
     */
    private function applyConstraintAboutRelatedAggregate(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ): self {
        if ($this->accessingAdministrativeRoute($searchParams)
            || $searchParams->hasParam('term')
        ) {
            $queryBuilder->andWhere(self::TABLE_ALIAS . '.aggregateName != :aggregate');
            $queryBuilder->setParameter('aggregate', $this->aggregate);

            return $this;
        }

        $aggregates = [$this->aggregate];
        if ($searchParams->hasParam('aggregate')) {
            $aggregates = explode(',', $searchParams->getParams()['aggregate']);
        }

        $queryBuilder->andWhere(self::TABLE_ALIAS . '.aggregateName in (:aggregates)');
        $queryBuilder->setParameter('aggregates', $aggregates);

        return $this;
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function accessingAdministrativeRoute(SearchParams $searchParams): bool
    {
        return $searchParams->paramIs('routeName', $this->adminRouteName);
    }

    /**
     * @param SearchParams $searchParams
     * @return array
     * @throws \Doctrine\DBAL\DBALException
     */
    public function selectDistinctAggregates(SearchParams $searchParams): array
    {
        $aggregateRestriction = 'AND a.name = ? ';
        $groupBy = 'GROUP BY h.member_id';

        if ($this->accessingAdministrativeRoute($searchParams)) {
            $aggregateRestriction = 'AND a.name != ? ';
            $groupBy = 'GROUP BY h.aggregate_id';
        }

        $excludeRetweets = !$searchParams->getParams()['includeRetweets'];
        $clauseAboutRetweets = '';
        if ($excludeRetweets) {
            $clauseAboutRetweets = 'AND h.is_retweet = 0';
        }

        $queryDistinctAggregates = <<< QUERY
                SELECT
                ust_name as memberFullName,
                usr_twitter_username as memberName,
                usr_twitter_id as twitterMemberId,
                usr_id as memberId,
                count(h.id) totalHighlights
                FROM highlight h,
                weaving_aggregate a,
                weaving_status s,
                weaving_user m
                WHERE h.member_id = m.usr_id
                AND a.id = h.aggregate_id
                $aggregateRestriction
                AND h.status_id = s.ust_id
                AND DATE(publication_date_time) >= ?
                AND DATE(publication_date_time) <= ?
                AND DATE(COALESCE(retweeted_status_publication_date, ?)) >= ?
                AND DATE(COALESCE(retweeted_status_publication_date, ?)) <= ?
                $clauseAboutRetweets
                $groupBy
                ORDER BY totalHighlights
QUERY;

        /** @var Connection $connection */
        $connection = $this->getEntityManager()->getConnection();

        try {
            $statement = $connection->executeQuery(
                $queryDistinctAggregates,
                [
                    $this->aggregate,
                    $searchParams->getParams()['startDate'],
                    $searchParams->getParams()['endDate'],
                    $searchParams->getParams()['startDate'],
                    $searchParams->getParams()['startDate'],
                    $searchParams->getParams()['endDate'],
                    $searchParams->getParams()['endDate'],
                ],
                [
                    \Pdo::PARAM_STR,
                    Type::DATETIME,
                    Type::DATETIME,
                    Type::DATETIME,
                    Type::DATETIME,
                    Type::DATETIME,
                    Type::DATETIME,
                ]
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return [];
        }

        $aggregates = $statement->fetchAll();

        return $aggregates;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return HighlightRepository
     */
    private function applyConstraintAboutSelectedAggregates(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams): self
    {
        if ($searchParams->hasParam('selectedAggregates') &&
            count($searchParams->getParams()['selectedAggregates']) > 0
        ) {
            $queryBuilder->andWhere(
                self::TABLE_ALIAS . '.member in (:selected_members)'
            );
            $queryBuilder->setParameter(
                'selected_members',
                $searchParams->getParams()['selectedAggregates']
            );
        }

        return $this;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     * @return QueryBuilder
     */
    private function applyConstraintAboutPopularity(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ): QueryBuilder {
        $condition = implode([
            "DATE(DATESUB(COALESCE(p.checkedAt, s.createdAt), 1, 'HOUR')) >= :startDate AND ",
            "DATE(DATESUB(COALESCE(p.checkedAt, s.createdAt), 1, 'HOUR')) <= :endDate"
        ]);

        // Do not consider the last time a status has been checked
        // when searching for statuses by a term
        if ($this->overOneDay($searchParams)) {
            $condition = implode([
                "DATE(DATESUB(p.checkedAt, 1, 'HOUR')) = :startDate",
            ]);
        }

        return $queryBuilder->leftJoin(
            's.popularity',
            'p',
            Join::WITH,
            $condition
        );
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function overOneDay(SearchParams $searchParams): bool
    {
        return $searchParams->getParams()['startDate']->format('Y-m-d') ===
            $searchParams->getParams()['endDate']->format('Y-m-d');
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function overMoreThanADay(SearchParams $searchParams): bool
    {
        return $searchParams->getParams()['startDate']->format('Y-m-d') !==
            $searchParams->getParams()['endDate']->format('Y-m-d');
    }

    public function mapStatuses(SearchParamsInterface $searchParams, $results): array
    {
        return array_map(
            function ($status) use ($searchParams) {
                $extractedProperties = [
                    'status' => $this->extractStatusProperties(
                        [$status],
                        false)[0]
                ];

                $decodedDocument = json_decode($status['original_document'], true);
                $decodedDocument['retweets_count'] = (int) $status['total_retweets'];
                $decodedDocument['favorites_count'] = (int) $status['total_favorites'];
                $extractedProperties['status']['retweet_count'] = (int) $status['total_retweets'];
                $extractedProperties['status']['favorite_count'] = (int) $status['total_favorites'];
                $extractedProperties['status']['original_document'] = json_encode($decodedDocument);

                $status['lastUpdate'] = $status['last_update'];

                $includeRetweets = $searchParams->getParams()['includeRetweets'];
                if ($includeRetweets && $extractedProperties['status']['favorite_count'] === 0) {
                    $extractedProperties['status']['favorite_count'] = $decodedDocument['retweeted_status']['favorite_count'];
                }

                unset(
                    $status['total_retweets'],
                    $status['total_favorites'],
                    $status['original_document'],
                    $status['screen_name'],
                    $status['author_avatar'],
                    $status['status_id'],
                    $status['last_update']
                );

                return array_merge($status, $extractedProperties);
            },
            $results
        );
    }
}
