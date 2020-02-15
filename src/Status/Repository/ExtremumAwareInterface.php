<?php


namespace App\Status\Repository;

/**
 * @package App\Status\Repository
 */
interface ExtremumAwareInterface
{
    /**
     * @param string         $memberName
     * @param \DateTime|null $before
     * @return array
     */
    public function findLocalMaximum(string $memberName, \DateTime $before = null): array;

    /**
     * @param string         $screenName
     * @param string         $direction
     * @param \DateTime|null $before
     * @return mixed
     */
    public function findNextExtremum(
        string $screenName,
        string $direction = 'asc',
        \DateTime $before = null
    ): array;

    /**
     * @param string $memberName
     * @return array
     */
    public function getIdsOfExtremeStatusesSavedForMemberHavingScreenName(string $memberName): array;
}
