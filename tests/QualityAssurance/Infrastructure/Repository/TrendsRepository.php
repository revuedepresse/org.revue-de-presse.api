<?php

namespace App\Tests\QualityAssurance\Infrastructure\Repository;

use App\QualityAssurance\Domain\Repository\TrendsRepositoryInterface;
use DateTimeInterface;
use Symfony\Component\HttpFoundation\Request;

class TrendsRepository implements TrendsRepositoryInterface
{
    public function updateTweetDocument(string $tweetId, DateTimeInterface $date, string $document)
    {
        // noop
    }

    public function removeTweetFromTrends(string $tweetId, DateTimeInterface $createdAt)
    {
        // noop
    }
}