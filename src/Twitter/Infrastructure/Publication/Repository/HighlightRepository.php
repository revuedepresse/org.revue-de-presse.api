<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Conversation\ConversationAwareTrait;
use App\Media\Image;
use App\Trends\Domain\Repository\SearchParamsInterface;
use App\Trends\Infrastructure\Repository\PaginationAwareTrait;
use App\Twitter\Domain\Publication\Repository\PaginationAwareRepositoryInterface;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\Http\SearchParams;
use Cassandra\Date;
use DateTime;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Result;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use InvalidArgumentException;
use JoliTypo\Fixer;
use LitEmoji\LitEmoji;
use Safe\Exceptions\FilesystemException;

class HighlightRepository extends ServiceEntityRepository implements PaginationAwareRepositoryInterface
{
    private const SEARCH_PERIOD_DATE_FORMAT = 'Y-m-d';

    use PaginationAwareTrait;
    use ConversationAwareTrait;
    use LoggerTrait;

    public string $defaultList;

    public string $mediaDirectory;

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
            !($searchParams->getParams()['startDate'] instanceof DateTime)
            || !($searchParams->getParams()['endDate'] instanceof DateTime)
        ) {
            throw new InvalidArgumentException(
                'Expected end date and start date to be instances of ' . DateTime::class
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

    /**
     * @throws \App\Conversation\Exception\InvalidStatusException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \JsonException
     */
    public function mapStatuses(SearchParamsInterface $searchParams, $tweets): array
    {
        return array_filter(array_map(
            function ($tweet) use ($searchParams) {
                $lightweightJSON = $this->stripUpstreamTweetDocumentFromExtraProperties($searchParams, $tweet['json']);

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
                    $searchParams,
                    $tweetDocument,
                    $lightweightJSON
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
        if ($this->guardAgainstNonExistingMedia($lightweightJSON)) {
            return false;
        }

        $smallMediaUrl = $lightweightJSON['extended_entities']['media'][0]['media_url'] . ':large';

        try {
            $jpegImageContents = file_get_contents($smallMediaUrl);
            $webpImageContents = Image::fromJpegToResizedWebp(
                $jpegImageContents,
                $lightweightJSON['extended_entities']['media'][0]['sizes']['large']['w'],
                $lightweightJSON['extended_entities']['media'][0]['sizes']['large']['h']
            );

            return 'data:image/webp;base64,' . base64_encode($webpImageContents);
        } catch (\Exception) {
            return false;
        }
    }

    public function stripUpstreamTweetDocumentFromExtraProperties(SearchParamsInterface $searchParams, string $json): array
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
            $textIndex   => $this->processText($text),
            'id_str'     => $upstreamDocument['id_str'],
            'metrics'    => $this->tweetMetrics($upstreamDocument['id_str'])
        ];

        if (isset($upstreamDocument['user']['profile_image_url_https'])) {
            $lightweightJSON['user']['profile_image_url_https'] = $upstreamDocument['user']['profile_image_url_https'];
        }

        if ($searchParams->includeMedia()) {
            if (isset($upstreamDocument['extended_entities']['media'][0]['media_url'])) {
                $upstreamDocument['extended_entities']['media'][0]['sizes']['small']['h'] = Image::scaleImageHeight(
                    $upstreamDocument['extended_entities']['media'][0]['sizes']['large']['w'],
                    $upstreamDocument['extended_entities']['media'][0]['sizes']['large']['h'],
                );

                $lightweightJSON['extended_entities'] = [
                    'media' => [
                        [
                            'media_url' => $upstreamDocument['extended_entities']['media'][0]['media_url'],
                            'sizes' => $upstreamDocument['extended_entities']['media'][0]['sizes']
                        ]
                    ]
                ];
            }

            if (isset($upstreamDocument['entities']['media'])) {
                $upstreamDocument['entities']['media'][0]['sizes']['small']['h'] = Image::scaleImageHeight(
                    $upstreamDocument['extended_entities']['media'][0]['sizes']['large']['w'],
                    $upstreamDocument['extended_entities']['media'][0]['sizes']['large']['h'],
                );

                $lightweightJSON['entities'] = [
                    'media' => $upstreamDocument['entities']['media'],
                    'urls'  => $upstreamDocument['entities']['urls']
                ];
            }
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
        SearchParamsInterface $searchParams,
        array                 $tweetAsJSON,
        array                 $lightweightJSON
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

        if (!$searchParams->includeMedia()) {
            return $properties;
        }

        if ($this->guardAgainstNonExistingMedia($lightweightJSON)) {
            return $properties;
        }

        if (isset($properties[$tweetIndex]['base64_encoded_media'])) {
            return $properties;
        }

        try {
            $properties[$tweetIndex]['base64_encoded_media'] = $this->getExistingMediaOrFetchIt($lightweightJSON);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }

        return $properties;
    }

    public function getTypographyFixer(): Fixer
    {
        $fixer = new Fixer([
            'Ellipsis',
            'Dimension',
            'Unit',
            'Dash',
            'SmartQuotes',
            'FrenchNoBreakSpace',
            'NoSpaceBeforeComma',
            'CurlyQuote',
            'Hyphen',
            'Trademark'
        ]);
        $fixer->setLocale('fr_FR');

        return $fixer;
    }

    public function processText(mixed $subject): string
    {
        $subject = LitEmoji::encodeUnicode($subject);

        $urls = [];
        $counter = 0;
        $subject = preg_replace_callback(
            '#(?<url>http(?:s)?://[^\s]+)(?<comma>\.?)#m',
            function ($matches) use (&$counter, &$urls) {
                $urls[] = $matches['url'];
                $replacement = '\\'.$counter;
                $counter++;

                return $replacement;
            }, $subject);

        $fixedSubject = $this->getTypographyFixer()->fixString($subject);

        $counter = 0;

        return array_reduce(
            $urls,
            function ($subject, $url) use (&$counter) {
                $subject = str_replace('\\'.$counter, $url, $subject);
                $counter++;
                return $subject;
            },
            $fixedSubject
        );
    }

    public function guardAgainstNonExistingMedia(array $lightweightJSON): bool
    {
        return !isset($lightweightJSON['extended_entities']['media'][0]['media_url']);
    }

    /**
     * @throws \Safe\Exceptions\FilesystemException
     */
    public function getExistingMediaOrFetchIt(array $lightweightJSON): string
    {
        $encodedMediaPath = sprintf(
            '%s/%s.%s',
            $this->mediaDirectory,
            $lightweightJSON['id_str'],
            'b64'
        );

        if (file_exists($encodedMediaPath)) {
            return \Safe\file_get_contents($encodedMediaPath);
        }

        $contents = $this->extractMediaContents($lightweightJSON);

        if ($contents !== false) {
            \Safe\file_put_contents($encodedMediaPath, $contents);

            return $contents;
        }

        throw new InvalidArgumentException('Cannot extract media contents');
    }

    public function tweetMetrics(string $tweetId): array|false
    {
        try {
            $statement = $this->queryMetrics($tweetId);
        } catch (Exception $e) {
            return ['retweets' => [], 'favorites' => []];
        }

        try {
            $rawMetrics = $statement->fetchAssociative();
        } catch (Exception $e) {
            $rawMetrics = [];
        }

        if (!$rawMetrics || count($rawMetrics) === 0) {
            return ['retweets' => [], 'favorites' => []];
        }

        return [
            'favorites' => $this->foldMetrics('favorites', $rawMetrics),
            'retweets' => $this->foldMetrics('retweets', $rawMetrics)
        ];
    }

    public function queryMetrics(string $tweetId): Result
    {
        $queryTemplate = <<<QUERY
            SELECT
            highlight.status_id as tweetId,
            array_to_json(
                array_agg(
                    concat(
                        coalesce(status_popularity.checked_at, highlight.publication_date_time),
                        '|',
                        coalesce(status_popularity.total_retweets, highlight.total_retweets)
                    )
                    order by status_popularity.checked_at asc
                )
            ) as retweets,
            array_to_json(
                array_agg(
                    concat(
                        coalesce(status_popularity.checked_at, highlight.publication_date_time),
                        '|',
                        coalesce(status_popularity.total_favorites, highlight.total_favorites)
                    )
                    order by status_popularity.checked_at asc
                )
            ) as favorites
            FROM highlight
            INNER JOIN weaving_status s ON s.ust_status_id = ? and s.ust_id = highlight.status_id
            LEFT JOIN status_popularity ON (
                status_popularity.status_id = highlight.status_id AND
                publication_date_time::date = status_popularity.checked_at::date
            )
            WHERE highlight.aggregate_id in (
                select id from publishers_list
                where name = ?
                and deleted_at is null
            )
            AND is_retweet = false
            GROUP BY highlight.status_id, highlight.id
QUERY;

        return $this->getEntityManager()->getConnection()->executeQuery(
            $queryTemplate,
            [
                $tweetId,
                $this->defaultList
            ],
            [
                \PDO::PARAM_STR,
                \PDO::PARAM_STR,
            ]
        );
    }

    public function foldMetrics(string $metricsType, array $rawMetrics): mixed
    {
        $metrics = $rawMetrics[$metricsType];

        $template = array_fill(7, 23 - 7 + 1, [
            'checkedAt'  => null,
            'delta'      => 0,
            $metricsType => null,
        ]);

        $parts = array_map(fn($rt) => explode('|', $rt), json_decode($metrics));
        $retweetsMetrics = array_map(
            fn($rt) => [$metricsType => $rt[1],
                        'checkedAt'  => (
                        new \DateTimeImmutable(
                            $rt[0],
                            new \DateTimeZone('UTC')
                        )
                        )->format(DateTime::ATOM)],
            $parts
        );

        $reducedMetrics = array_reduce($retweetsMetrics, function ($acc, $item) use ($metricsType) {
            if ($acc[count($acc) - 1]['checkedAt'] === $item['checkedAt']) {
                $acc[count($acc) - 1]['delta'] = intval($item[$metricsType]);

                return $acc;
            }

            $item['delta'] = $item[$metricsType] - $acc[count($acc) - 1][$metricsType];
            $acc[] = $item;

            return $acc;
        }, [$retweetsMetrics[0]]);

        try {
            $mappedMetrics = array_map(
                fn($r) => (new \DateTimeImmutable($r['checkedAt']))->format('G'),
                $reducedMetrics
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());

            return [];
        }

        $indexedMetrics = array_combine($mappedMetrics, $reducedMetrics);
        $filledColl = array_replace($template, $indexedMetrics);

        $date = $reducedMetrics[0]['checkedAt'];
        $hour = 7;

        $reducedMetrics = array_reduce($filledColl, function ($carry, $item) use (&$hour, $date, $metricsType) {
            if ($item['checkedAt'] === null) {
                $date = new \DateTimeImmutable($date, new \DateTimeZone('UTC'));
                $laterDate = $date->setTime($hour, 0);
                $item['checkedAt'] = $laterDate->format(DateTimeInterface::ATOM);

                if (count($carry) > 1 && array_key_exists(count($carry) - 1, $carry)) {
                    $item[$metricsType] = (int)$carry[count($carry) - 1][$metricsType];
                } else {
                    $item[$metricsType] = 0;
                }

                $item['delta'] = 0;
            }

            $hour++;

            $item[$metricsType] = intval($item[$metricsType]);

            $carry[] = $item;

            return $carry;
        }, []);

        usort($reducedMetrics, function ($left, $right) {
            if ($left['checkedAt'] === $right['checkedAt']) {
                return 0;
            }

            if ($left['checkedAt'] > $right['checkedAt']) {
                return 1;
            }

            return -1;
        });

        return $reducedMetrics;
    }
}
