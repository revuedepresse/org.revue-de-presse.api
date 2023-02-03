<?php

namespace App\QualityAssurance\Domain\Repository;

use DateTimeInterface;

interface TrendsRepositoryInterface
{
    public function updateTweetDocument(
        string $tweetId,
        DateTimeInterface $date,
        string $document
    );

    public function removeTweetFromTrends(
        string $tweetId,
        DateTimeInterface $createdAt
    );
}