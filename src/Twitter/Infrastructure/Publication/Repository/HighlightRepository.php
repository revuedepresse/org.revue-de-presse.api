<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\NewsReview\Domain\Repository\SearchParamsInterface;
use App\Twitter\Infrastructure\PublishersList\Repository\MemberAggregateSubscriptionRepository;
use App\Twitter\Infrastructure\PublishersList\Repository\PaginationAwareTrait;
use App\Conversation\ConversationAwareTrait;
use App\Twitter\Domain\Publication\Repository\PaginationAwareRepositoryInterface;
use App\Twitter\Infrastructure\Api\Repository\PublishersListRepository;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\SearchParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;

class HighlightRepository extends ServiceEntityRepository implements PaginationAwareRepositoryInterface
{
    private const SEARCH_PERIOD_DATE_FORMAT = 'Y-m-d';

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
     * @throws DBALException
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

        $queryBuilder->groupBy(self::TABLE_ALIAS.'.status');
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
            ->applyConstraintAboutEnclosingAggregate($queryBuilder, $searchParams)
            ->applyConstraintAboutSelectedAggregates($queryBuilder, $searchParams);

        if ($searchParams->hasParam('term')) {
            $this->applyConstraintAboutTerm($queryBuilder, $searchParams);
        }

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
     *
     * @return $this
     */
    private function applyConstraintAboutEnclosingAggregate(
        QueryBuilder $queryBuilder,
        SearchParams $searchParams
    ): self {
        if ($searchParams->hasParam('aggregateIds') &&
            count($searchParams->getParams()['aggregateIds']) > 0
        ) {
            $queryBuilder->innerJoin(
                self::TABLE_ALIAS.'.aggregate',
                PublishersListRepository::TABLE_ALIAS
            );

            $queryBuilder->innerJoin(
                PublishersListRepository::TABLE_ALIAS.'.memberAggregateSubscription',
                MemberAggregateSubscriptionRepository::TABLE_ALIAS,
                Join::WITH,
                implode([
                    MemberAggregateSubscriptionRepository::TABLE_ALIAS,
                    '.',
                    'id in (:aggregate_ids)'
                ])
            );

            $queryBuilder->setParameter(
                'aggregate_ids',
                $searchParams->getParams()['aggregateIds']
            );
        }

        return $this;
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
        $retweetedStatusPublicationDate = 'COALESCE(
                DATE(
                    DATEADD(' .
            self::TABLE_ALIAS . ".retweetedStatusPublicationDate, 1, 'HOUR'
                    )
                ),
                DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR'))
            )";

        if ($this->overOneDay($searchParams) && !$searchParams->hasParam('term')) {
            $queryBuilder->andWhere($retweetedStatusPublicationDate . " = :startDate");
        }

        if ($this->overMoreThanADay($searchParams)) {
            $queryBuilder->andWhere($retweetedStatusPublicationDate . ' >= :startDate');
            $queryBuilder->andWhere($retweetedStatusPublicationDate . ' <= :endDate');
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
        return $searchParams->paramBelongsTo(
            'routeName',
            $this->adminRouteName
        );
    }

    /**
     * @param SearchParams $searchParams
     * @return array
     * @throws DBALException
     */
    public function selectDistinctAggregates(SearchParams $searchParams): array
    {
        $excludeRetweets = !$searchParams->getParams()['includeRetweets'];
        $clauseAboutRetweets = '';
        if ($excludeRetweets) {
            $clauseAboutRetweets = 'AND h.is_retweet = 0';
        }

        $aggregateRestriction = $this->getConditionOnAggregateToSelectDistinctAggregates($searchParams);
        $groupBy = $this->getGroupByClauseToSelectDistinctAggregates($searchParams);

        $queryDistinctAggregates = <<< QUERY
                SELECT
                ust_name as memberFullName,
                usr_twitter_username as memberName,
                usr_twitter_id as twitterMemberId,
                usr_id as memberId,
                count(h.id) totalHighlights
                FROM highlight h,
                publishers_list a,
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

        return $statement->fetchAll();
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
     */
    private function assertSearchPeriodIsValid(SearchParams $searchParams): void {
        if (
            !($searchParams->getParams()['startDate'] instanceof \DateTime)
            || !($searchParams->getParams()['endDate'] instanceof \DateTime)
        ) {
            throw new InvalidArgumentException(
                'Expected end date and start date to be instances of ' . \DateTime::class
            );
        }
    }

    /**
     * @param SearchParams $searchParams
     *
     * @return string
     */
    private function getConditionOnAggregateToSelectDistinctAggregates(
        SearchParams $searchParams
    ): string {
        $aggregateRestriction = 'AND a.name = ? ';
        if ($this->accessingAdministrativeRoute($searchParams)) {
            $aggregateRestriction = 'AND a.name != ? ';
        }

        return $aggregateRestriction;
    }

    /**
     * @param SearchParams $searchParams
     *
     * @return string
     */
    private function getGroupByClauseToSelectDistinctAggregates(
        SearchParams $searchParams
    ): string {
        $groupBy = 'GROUP BY h.member_id';

        if ($searchParams->hasParam(SearchParams::PARAM_AGGREGATE_IDS)) {
            return $groupBy;
        }

        if ($this->accessingAdministrativeRoute($searchParams)) {
            $groupBy = 'GROUP BY h.aggregate_id';
        }

        return $groupBy;
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function overOneDay(SearchParams $searchParams): bool
    {
        $this->assertSearchPeriodIsValid($searchParams);

        return $searchParams->getParams()['startDate']->format(self::SEARCH_PERIOD_DATE_FORMAT) ===
            $searchParams->getParams()['endDate']->format(self::SEARCH_PERIOD_DATE_FORMAT);
    }

    /**
     * @param SearchParams $searchParams
     * @return bool
     */
    private function overMoreThanADay(SearchParams $searchParams): bool
    {
        $this->assertSearchPeriodIsValid($searchParams);

        return $searchParams->getParams()['startDate']->format(self::SEARCH_PERIOD_DATE_FORMAT) !==
            $searchParams->getParams()['endDate']->format(self::SEARCH_PERIOD_DATE_FORMAT);
    }

    public function mapStatuses(SearchParamsInterface $searchParams, $results): array
    {
        return array_map(
            function ($status) use ($searchParams) {
                $statusKey = 'status';
                $totalFavoritesKey = 'total_favorites';
                $totalRetweetsKey = 'total_retweets';
                $favoriteCountKey = 'favorite_count';
                $originalDocumentKey = 'original_document';

                $extractedProperties = [
                    $statusKey => $this->extractStatusProperties(
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
                if ($includeRetweets && $extractedProperties[$statusKey][$favoriteCountKey] === 0) {
                    $extractedProperties[$statusKey][$favoriteCountKey] = $decodedDocument['retweeted_status'][$favoriteCountKey];
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
