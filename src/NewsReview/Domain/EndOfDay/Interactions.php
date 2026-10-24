<?php
declare(strict_types=1);

namespace App\NewsReview\Domain\EndOfDay;

/** Where a post's current counts and the times of its reposts and likes come from. */
interface Interactions
{
    /**
     * @param list<string> $uris
     * @return array<string, array{reposts: int, likes: int}> posts that still exist, by URI
     */
    public function currentCounts(array $uris): array;

    /** @return list<?\DateTimeImmutable> one entry per repost, null when its time is unknown */
    public function repostTimes(string $uri): array;

    /** @return list<\DateTimeImmutable> */
    public function likeTimes(string $uri): array;
}
