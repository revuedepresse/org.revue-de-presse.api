<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Persistence;

use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use Doctrine\ORM\EntityManagerInterface;

interface TweetPersistenceLayerInterface
{
    public function persistTweetsCollection(
        array $statuses,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): array;

    public function unarchiveStatus(
        TweetInterface         $status,
        EntityManagerInterface $entityManager
    ): TweetInterface;

    public function savePublicationsForScreenName(
        array $statuses,
        string $screenName,
        CurationSelectorsInterface $selectors
    );
}
