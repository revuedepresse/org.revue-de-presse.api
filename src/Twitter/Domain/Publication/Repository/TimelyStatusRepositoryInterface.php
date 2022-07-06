<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Publication\Repository;

use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Publication\TweetInterface;

interface TimelyStatusRepositoryInterface
{
    public function fromTweetInList(
        TweetInterface  $status,
        ?PublishersList $list = null
    );
}
