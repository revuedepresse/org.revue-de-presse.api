<?php

namespace App\Status\Repository;

use App\Aggregate\Controller\SearchParams;
use App\Aggregate\Repository\PaginationAwareTrait;
use App\Conversation\ConversationAwareTrait;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

class HighlightRepository extends EntityRepository implements PaginationAwareRepositoryInterface
{
    use PaginationAwareTrait;
    use ConversationAwareTrait;

    /**
     * @var string
     */
    public $aggregate;

    /**
     * @var string
     */
    public $adminRouteName;

    const TABLE_ALIAS = 'h';

    public function __construct($entityManager, ClassMetadata $class)
    {
        parent::__construct($entityManager, $class);
    }

    /**
     * @param SearchParams $searchParams
     * @return int
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countTotalPages(SearchParams $searchParams): int
    {
        return $this->howManyPages($searchParams, self::TABLE_ALIAS);
    }

    /**
     * @param SearchParams $searchParams
     * @return array
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
        $queryBuilder->addSelect("COALESCE(p.checkedAt, s.createdAt) as last_update");
        $queryBuilder->addSelect(implode([
            'COALESCE(',
            '   p.totalRetweets, ',
            '   '.self::TABLE_ALIAS.'.totalRetweets',
            ') as total_retweets',
        ]));
        $queryBuilder->addSelect(implode([
            'COALESCE(',
            '   p.totalFavorites, ',
            '   '.self::TABLE_ALIAS.'.totalFavorites',
            ') as total_favorites',
        ]));

        $queryBuilder->setFirstResult($searchParams->getFirstItemIndex());
        $queryBuilder->setMaxResults(min($searchParams->getPageSize(), 10));

        $this->applyCriteria($queryBuilder, $searchParams);

        $queryBuilder->groupBy('s.id');
        $queryBuilder->addOrderBy('total_retweets', 'DESC');
        $queryBuilder->addOrderBy("last_update", 'DESC');

        $results = $queryBuilder->getQuery()->getArrayResult();
        $statuses = array_map(
            function ($status) use ($searchParams) {
                $extractedProperties = [
                    'status' => $this->extractStatusProperties(
                        [$status],
                        false)[0]
                ];

                $decodedDocument = json_decode($status['original_document'], true);
                $decodedDocument['retweets_count'] = intval($status['total_retweets']);
                $decodedDocument['favorites_count'] = intval($status['total_favorites']);
                $extractedProperties['status']['retweet_count'] = intval($status['total_retweets']);
                $extractedProperties['status']['favorite_count'] = intval($status['total_favorites']);
                $extractedProperties['status']['original_document'] = json_encode($decodedDocument);

                $status['lastUpdate'] = $status['last_update'];

                $includeRetweets = $searchParams->getParams()['includeRetweets'];
                if ($includeRetweets && $extractedProperties['status']['favorite_count'] === 0) {
                    $extractedProperties['status']['favorite_count'] = $decodedDocument['retweeted_status']['favorite_count'];
                }

                unset($status['total_retweets']);
                unset($status['total_favorites']);
                unset($status['original_document']);
                unset($status['screen_name']);
                unset($status['author_avatar']);
                unset($status['status_id']);
                unset($status['last_update']);

                return array_merge($status, $extractedProperties);
            },
            $results
        );

        return $statuses;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param SearchParams $searchParams
     */
    public function applyCriteria(QueryBuilder $queryBuilder, SearchParams $searchParams): void
    {
        $queryBuilder->innerJoin(self::TABLE_ALIAS.'.status', 's');
        $queryBuilder->innerJoin(self::TABLE_ALIAS.'.member', 'm');

        $queryBuilder->leftJoin(
            's.popularity',
            'p',
            Join::WITH,
            "DATE(DATESUB(p.checkedAt, 1, 'HOUR')) = :date"
        );

        $this->applyConstraintAboutPublicationDateTime($queryBuilder)
        ->applyConstraintAboutPublicationDateOfRetweetedStatus($queryBuilder)
        ->applyConstraintAboutRetweetedStatus($queryBuilder, $searchParams)
        ->applyConstraintAboutRelatedAggregate($queryBuilder, $searchParams);

        $queryBuilder->setParameter('date', $searchParams->getParams()['date']);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @return HighlightRepository
     */
    private function applyConstraintAboutPublicationDateOfRetweetedStatus(QueryBuilder $queryBuilder): self
    {
        $retweetedStatusPublicationDate = "COALESCE(
                DATE(
                    DATEADD(" .
            self::TABLE_ALIAS . ".retweetedStatusPublicationDate, 1, 'HOUR'
                    )
                ),
                DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR'))
            )";
        $queryBuilder->andWhere($retweetedStatusPublicationDate . " = :date");

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
     * @return HighlightRepository
     */
    private function applyConstraintAboutPublicationDateTime(QueryBuilder $queryBuilder): self
    {
        $queryBuilder->andWhere("DATE(DATEADD(" . self::TABLE_ALIAS . ".publicationDateTime, 1, 'HOUR')) = :date");

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
        if ($searchParams->paramIs('routeName', $this->adminRouteName)) {
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
}
