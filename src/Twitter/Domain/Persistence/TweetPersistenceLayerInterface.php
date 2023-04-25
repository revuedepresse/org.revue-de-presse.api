<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Persistence;

use App\Search\Domain\Entity\SavedSearch;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Domain\Curation\CurationSelectorsInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use Doctrine\ORM\EntityManagerInterface;

interface TweetPersistenceLayerInterface
{
    public const PROPERTY_NORMALIZED_STATUS = 'normalized_status';
    public const PROPERTY_SCREEN_NAME       = 'screen_name';
    public const PROPERTY_SEARCH_QUERY      = 'search_query';
    public const PROPERTY_TWEET            = 'status';

    public function persistSearchQueryBasedTweetsCollection(
        AccessToken $identifier,
        SavedSearch $savedSearch,
        array $tweets
    ): array;

    public function persistTweetsCollection(
        array $statuses,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): array;

    public function saveTweetsAuthoredByMemberHavingScreenName(
        array $statuses,
        string $screenName,
        CurationSelectorsInterface $selectors
    ): ?int;

    public function unarchiveStatus(
        TweetInterface         $status,
        EntityManagerInterface $entityManager
    ): TweetInterface;
}
