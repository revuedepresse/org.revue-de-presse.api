<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Repository;

use App\Twitter\Infrastructure\Curation\Entity\NotFoundStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use App\Twitter\Infrastructure\Http\Entity\ArchivedTweet;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Domain\Publication\TweetInterface;

class NotFoundStatusRepository extends ServiceEntityRepository
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
