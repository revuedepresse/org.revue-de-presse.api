<?php

namespace App\Twitter\Domain\Publication\Repository;

use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Curation\Entity\NotFoundStatus;

interface NotFoundTweetRepositoryInterface
{
    public function markStatusAsNotFound(TweetInterface $status): NotFoundStatus;
}