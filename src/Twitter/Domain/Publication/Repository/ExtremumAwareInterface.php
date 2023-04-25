<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

/**
 * @package App\Twitter\Infrastructure\Publication\Repository
 */
interface ExtremumAwareInterface
{
    public const FINDING_IN_ASCENDING_ORDER = 'asc';
    public const FINDING_IN_DESCENDING_ORDER = 'desc';

    public const EXTREMUM_FROM_MEMBER = 'fromMember';
    public const EXTREMUM_STATUS_ID = 'statusId';

    public function findLocalMaximum(
        string  $memberUsername,
        ?string $before = null
    ): array;

    public function findNextExtremum(
        string  $memberUsername,
        string  $direction = self::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array;

    public function getIdsOfExtremeStatusesSavedForMemberHavingScreenName(string $memberName): array;
}
