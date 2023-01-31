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

    public function mapStatuses(SearchParamsInterface $searchParams, $tweets): array
    {
        return array_filter(array_map(
            function ($tweet) use ($searchParams) {
                $lightweightJSON = $this->stripUpstreamTweetDocumentFromExtraProperties($tweet['json']);

                if (!array_key_exists('id', $tweet)) {
                    return false;
                }

                $tweetDocument = [
                    'id' => $tweet['id'],
                    'lastUpdate' => $tweet['checkedAt'],
                    'publicationDateTime' => $tweet['publishedAt'],
                    'screen_name' => $tweet['username'],
                    'total_retweets' => $tweet['totalRetweets'],
                    'total_favorites' => $tweet['totalFavorites'],
                    'original_document' => json_encode($lightweightJSON),
                ];

                $tweetPropertiesToOverride = $this->extractTweetPropertiesToOverride(
                    $tweetDocument,
                    $lightweightJSON,
                    $searchParams
                );

                unset(
                    $tweetDocument['original_document'],
                    $tweetDocument['total_favorites'],
                    $tweetDocument['total_retweets'],
                    $tweetDocument['author_avatar'],
                    $tweetDocument['screen_name'],
                    $tweetDocument['status_id']
                );

                return array_merge($tweetDocument, $tweetPropertiesToOverride);
            },
            $tweets
        ));
    }

    public function extractMemberFullName(mixed $decodedDocument): string
    {
        $fullMemberName = '';
        if (isset($decodedDocument['user']['name'])) {
            $fullMemberName = $decodedDocument['user']['name'];
        }

        return $fullMemberName;
    }

    public function extractEntitiesUrls(mixed $decodedDocument): array
    {
        $entitiesUrls = [];
        if (isset($decodedDocument['entities']['urls'])) {
            $entitiesUrls = $decodedDocument['entities']['urls'];
        }

        return $entitiesUrls;
    }

    public function extractMediaContents(array $lightweightJSON): string|false
    {
        if (!isset($lightweightJSON['extended_entities']['media'][0]['media_url'])) {
            return false;
        }

        $smallMediaUrl = $lightweightJSON['extended_entities']['media'][0]['media_url'] . ':small';

        try {
            $jpegImageContents = file_get_contents($smallMediaUrl);
            $webpImageContents = $this->convertFromJpegToWebp($jpegImageContents);

            return 'data:image/webp;base64,' . base64_encode($webpImageContents);
        } catch (\Exception) {
            return false;
        }
    }

    public function stripUpstreamTweetDocumentFromExtraProperties(string $json): array
    {
        $upstreamDocument = json_decode($json, associative: true);

        if (array_key_exists('text', $upstreamDocument)) {
            $text = $upstreamDocument['text'];
            $textIndex = 'text';
        } else {
            $text = $upstreamDocument['full_text'];
            $textIndex = 'full_text';
        }

        $lightweightJSON = [
            'created_at' => $upstreamDocument['created_at'],
            'user'       => ['name' => $this->extractMemberFullName($upstreamDocument)],
            'entities'   => ['urls' => $this->extractEntitiesUrls($upstreamDocument)],
            $textIndex   => $text,
            'id_str'     => $upstreamDocument['id_str']
        ];

        if (isset($upstreamDocument['user']['profile_image_url_https'])) {
            $lightweightJSON['user']['profile_image_url_https'] = $upstreamDocument['user']['profile_image_url_https'];
        }

        if (isset($upstreamDocument['extended_entities']['media'][0]['media_url'])) {
            $lightweightJSON['extended_entities'] = [
                'media' => [
                    ['media_url' => $upstreamDocument['extended_entities']['media'][0]['media_url']]
                ]
            ];
        }

        if (isset($upstreamDocument['entities']['media'])) {
            $lightweightJSON['entities'] = [
                'media' => $upstreamDocument['entities']['media']
            ];
        }

        return $lightweightJSON;
    }

    /**
     * @throws \App\Conversation\Exception\InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     */
    private function extractTweetPropertiesToOverride(
        array                 $tweetAsJSON,
        array                 $lightweightJSON,
        SearchParamsInterface $searchParams
    ): array
    {
        $favoriteCountIndex = 'favorite_count';
        $originalDocumentIndex = 'original_document';
        $retweetCountIndex = 'retweet_count';
        $totalFavoritesIndex = 'total_favorites';
        $totalRetweetsIndex = 'total_retweets';
        $tweetIndex = 'status';

        $properties = [$tweetIndex => $this->extractTweetProperties([$tweetAsJSON])[0]];

        $lightweightJSON[$retweetCountIndex] = (int)$tweetAsJSON[$totalRetweetsIndex];
        $lightweightJSON[$favoriteCountIndex] = (int)$tweetAsJSON[$totalFavoritesIndex];

        $properties[$tweetIndex][$retweetCountIndex] = (int)$tweetAsJSON[$totalRetweetsIndex];
        $properties[$tweetIndex][$favoriteCountIndex] = (int)$tweetAsJSON[$totalFavoritesIndex];
        $properties[$tweetIndex][$originalDocumentIndex] = json_encode($lightweightJSON);

        $includeRetweets = $searchParams->getParams()['includeRetweets'];
        if ($includeRetweets && $properties[$tweetIndex][$favoriteCountIndex] === 0) {
            $properties[$tweetIndex][$favoriteCountIndex] = $lightweightJSON['retweeted_status'][$favoriteCountIndex];
        }

        $contents = $this->extractMediaContents($lightweightJSON);
        if (!isset($properties[$tweetIndex]['base64_encoded_media']) && $contents !== false) {
            $properties[$tweetIndex]['base64_encoded_media'] = $contents;
        }

        return $properties;
    }
}
