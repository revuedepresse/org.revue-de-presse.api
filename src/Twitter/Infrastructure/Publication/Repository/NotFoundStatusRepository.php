<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Twitter\Domain\Publication\Repository\NotFoundTweetRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Curation\Entity\NotFoundStatus;
use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class NotFoundStatusRepository extends ServiceEntityRepository implements NotFoundTweetRepositoryInterface
{
    public function markStatusAsNotFound(TweetInterface $status): NotFoundStatus
    {
        if ($status instanceof ArchivedTweet) {
            return new NotFoundStatus(null, $status);
        }

        if ($status instanceof Tweet) {
            return new NotFoundStatus($status);
        }

        throw new \LogicException('A valid input status should be declared as not found');
    }
}
