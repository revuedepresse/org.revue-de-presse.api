<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Trends\Domain\Repository\SearchParamsInterface;
use App\Trends\Infrastructure\Repository\PaginationAwareTrait;
use App\Conversation\ConversationAwareTrait;
use App\Twitter\Domain\Publication\Repository\PaginationAwareRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\SearchParams;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
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

    public string $adminRouteName;

    private const TABLE_ALIAS = 'h';

    /**
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

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
                    $this->list,
                    $searchParams->getParams()['startDate'],
                    $searchParams->getParams()['endDate'],
                    $searchParams->getParams()['startDate'],
                    $searchParams->getParams()['startDate'],
                    $searchParams->getParams()['endDate'],
                    $searchParams->getParams()['endDate'],
                ],
                [
                    \Pdo::PARAM_STR,
                    Types::DATETIME_MUTABLE,
                    Types::DATETIME_MUTABLE,
                    Types::DATETIME_MUTABLE,
                    Types::DATETIME_MUTABLE,
                    Types::DATETIME_MUTABLE,
                    Types::DATETIME_MUTABLE,
                ]
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return [];
        }

        $aggregates = $statement->fetchAllAssociative();

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

                $decodedDocument = json_decode($status[$originalDocumentKey], true);
                $decodedDocument['retweets_count'] = (int) $status[$totalRetweetsKey];
                $decodedDocument[$favoriteCountKey] = (int) $status[$totalFavoritesKey];

                $extractedProperties['status']['retweet_count'] = (int) $status[$totalRetweetsKey];
                $extractedProperties['status']['favorite_count'] = (int) $status[$totalFavoritesKey];
                $extractedProperties['status'][$originalDocumentKey] = json_encode($decodedDocument);

                $status['lastUpdate'] = $status['last_update'];

                $includeRetweets = $searchParams->getParams()['includeRetweets'];
                if ($includeRetweets && $extractedProperties[$statusKey][$favoriteCountKey] === 0) {
                    $extractedProperties[$statusKey][$favoriteCountKey] = $decodedDocument['retweeted_status'][$favoriteCountKey];
                }

                if (
                    !isset($extractedProperties['status']['base64_encoded_media']) &&
                    isset($decodedDocument['extended_entities']['media'][0]['media_url'])
                ) {
                    $smallMediaUrl = $decodedDocument['extended_entities']['media'][0]['media_url'].':small';

                    try {
                        $contents = file_get_contents($smallMediaUrl);
                    } catch (\Exception) {
                        $contents = false;
                    }

                    if ($contents !== false) {
                        $extractedProperties['status']['base64_encoded_media'] = 'data:image/jpeg;base64,'.base64_encode($contents);
                    }
                }

                unset(
                    $status[$totalRetweetsKey],
                    $status[$totalFavoritesKey],
                    $status[$originalDocumentKey],
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
