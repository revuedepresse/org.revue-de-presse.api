<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/**
 * Timestamp identifiers (TIDs): the record keys most AT Protocol clients give
 * likes and reposts. 13 base32-sortable characters encoding 64 bits, whose
 * upper 53 bits after the leading zero are microseconds since the Unix epoch.
 */
final class Tid
{
    private const ALPHABET = '234567abcdefghijklmnopqrstuvwxyz';

    public static function createdAt(string $recordKey): ?\DateTimeImmutable
    {
        if (strlen($recordKey) !== 13) {
            return null;
        }

        $value = 0;
        foreach (str_split($recordKey) as $character) {
            $digit = strpos(self::ALPHABET, $character);
            if ($digit === false) {
                return null;
            }
            $value = ($value << 5) | $digit;
        }

        $microseconds = ($value >> 10) & ((1 << 53) - 1);
        $at = \DateTimeImmutable::createFromFormat(
            'U.u',
            sprintf('%d.%06d', intdiv($microseconds, 1_000_000), $microseconds % 1_000_000),
            new \DateTimeZone('UTC'),
        );

        return $at === false ? null : $at;
    }
}
