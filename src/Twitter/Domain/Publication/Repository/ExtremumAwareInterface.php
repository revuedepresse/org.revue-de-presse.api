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

    /**
     * @param string      $memberName
     * @param string|null $before
     * @return array
     */
    public function findLocalMaximum(
        string $memberName,
        ?string $before = null
    ): array;

    /**
     * @param string         $screenName
     * @param string         $direction
     * @param string|null $before
     * @return mixed
     */
    public function findNextExtremum(
        string $screenName,
        string $direction = self::FINDING_IN_ASCENDING_ORDER,
        ?string $before = null
    ): array;

    /**
     * @param string $memberName
     * @return array
     */
    public function getIdsOfExtremeStatusesSavedForMemberHavingScreenName(string $memberName): array;
}
