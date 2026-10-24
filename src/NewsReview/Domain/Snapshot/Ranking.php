<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot;

/**
 * How a day's top 10 is ranked.
 *
 * - first-sighting: counts measured when a post was first fetched, the
 *   snapshots published so far.
 * - end-of-day: counts as they stood at the end of the publication day,
 *   from hourly measurements or rebuilt from like and repost timestamps.
 *
 * The value is what clients pass as `?ranking=`; it is also part of the
 * snapshot file names (see PublisherList::snapshotFileName()).
 */
enum Ranking: string
{
    case FirstSighting = 'first-sighting';
    case EndOfDay = 'end-of-day';

    public static function fromQuery(mixed $value, self $default): self
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (!is_string($value)) {
            throw new UnknownRanking('(non-string)');
        }

        return self::tryFrom($value) ?? throw new UnknownRanking($value);
    }
}
