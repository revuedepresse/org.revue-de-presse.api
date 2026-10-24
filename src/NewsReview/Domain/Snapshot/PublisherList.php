<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\Snapshot;

/**
 * Bluesky lists whose members are ranked into a daily top 10.
 *
 * The value is what clients pass as `?list=`; it also names that list's
 * snapshot files, all in one directory: <date>.<list>.first-sighting.json.
 * French media snapshots predate the other lists and keep their <date>.json
 * name.
 */
enum PublisherList: string
{
    case FrenchMedia = 'fr';
    case CoopMedias = 'coop-medias';

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

    public function snapshotFileName(string $date): string
    {
        return match ($this) {
            self::FrenchMedia => $date . '.json',
            self::CoopMedias => $date . '.' . $this->value . '.first-sighting.json',
        };
    }
}
