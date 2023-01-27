<?php

namespace App\QualityAssurance\Domain\Repository;

interface TrendsRepositoryInterface
{
    public function updateTweetDocument(
        string $tweetId,
        \DateTimeInterface $date,
        string $document
    );
}