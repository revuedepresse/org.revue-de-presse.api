<?php
declare(strict_types=1);

namespace App\Tests\NewsReview\Domain\EndOfDay;

use App\NewsReview\Domain\EndOfDay\Interactions;

/** Interactions from fixed timelines; records which timelines were read. */
final class FakeInteractions implements Interactions
{
    /** @var list<string> */
    public array $timelinesRead = [];

    /** @param array<string, array{list<\DateTimeImmutable>, list<\DateTimeImmutable>}> $timelines reposts then likes, per post */
    public function __construct(private readonly array $timelines)
    {
    }

    public function currentCounts(array $uris): array
    {
        $counts = [];
        foreach ($uris as $uri) {
            if (isset($this->timelines[$uri])) {
                $counts[$uri] = ['reposts' => count($this->timelines[$uri][0]), 'likes' => count($this->timelines[$uri][1])];
            }
        }

        return $counts;
    }

    public function repostTimes(string $uri): array
    {
        $this->timelinesRead[] = $uri;

        return $this->timelines[$uri][0];
    }

    public function likeTimes(string $uri): array
    {
        return $this->timelines[$uri][1];
    }
}
