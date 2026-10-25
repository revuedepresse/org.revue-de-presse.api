<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot;

/**
 * Bluesky lists whose members are ranked into a daily top 10.
 *
 * The value is what clients pass as `?list=`; it also names that list's
 * snapshot files, all in one directory: <date>.<list>.<ranking>.json.
 * French media first-sighting snapshots predate the other lists and rankings
 * and keep their <date>.json name.
 */
enum PublisherList: string
{
    case FrenchMedia = 'fr';
    case CoopMedias = 'coop-medias';
    // Members of either list, each post counted once.
    case FrenchAndCoopMedias = 'fr-coop-medias';

    public static function fromQuery(mixed $value): self
    {
        if ($value === null || $value === '') {
            return self::FrenchMedia;
        }

        if (!is_string($value)) {
            throw new UnknownPublisherList('(non-string)');
        }

        return self::tryFrom($value) ?? throw new UnknownPublisherList($value);
    }

    public function snapshotFileName(string $date, Ranking $ranking = Ranking::FirstSighting): string
    {
        if ($this === self::FrenchMedia && $ranking === Ranking::FirstSighting) {
            return $date . '.json';
        }

        return $date . '.' . $this->value . '.' . $ranking->value . '.json';
    }
}
