<?php
declare(strict_types=1);

namespace App\NewsReview\Infrastructure\Repository\CacheKey;

use App\NewsReview\Domain\Snapshot\PublisherList;
use App\NewsReview\Domain\Snapshot\Ranking;

final class HighlightCacheKey
{
    public static function from(array $queryParams): string
    {
        $parts = [
            self::dateHour($queryParams['startDate'] ?? null),
            self::dateHour($queryParams['endDate'] ?? null),
            'page='   . ($queryParams['page']         ?? 1),
            'items='  . ($queryParams['itemsPerPage'] ?? 25),
            'rt='     . (int) (bool) ($queryParams['includeRetweets'] ?? 0),
            'media='  . (int) !(bool) ($queryParams['excludeMedia']   ?? 0),
            'ds='     . (int) (bool) ($queryParams['distinctSources'] ?? 0),
            'term='   . (string) ($queryParams['term'] ?? ''),
            'aggs='   . self::sortedCsv($queryParams['selectedAggregates'] ?? []),
        ];

        // Appended only for the lists added after French media, so the keys
        // already cached for French media stay valid.
        $list = $queryParams['list'] ?? PublisherList::FrenchMedia;
        if ($list instanceof PublisherList && $list !== PublisherList::FrenchMedia) {
            $parts[] = 'list=' . $list->value;
        }

        // Same for rankings: first-sighting keeps the keys cached before rankings existed.
        $ranking = $queryParams['ranking'] ?? Ranking::FirstSighting;
        if ($ranking instanceof Ranking && $ranking !== Ranking::FirstSighting) {
            $parts[] = 'ranking=' . $ranking->value;
        }

        // A defaulted ranking may hold another ranking's posts on days it has
        // none, which a ranking asked for by name must never be served.
        $fallback = $queryParams['rankingFallback'] ?? null;
        if ($fallback instanceof Ranking) {
            $parts[] = 'fallback=' . $fallback->value;
        }

        return sha1(implode(';', $parts));
    }

    private static function dateHour(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m-d H');
        }

        return '';
    }

    private static function sortedCsv(array $values): string
    {
        $sorted = array_values(array_map('strval', $values));
        sort($sorted);

        return implode(',', $sorted);
    }
}
