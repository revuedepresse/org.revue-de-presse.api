<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Persistence;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\DependencyInjection\MemberRepositoryTrait;
use App\Search\Domain\Entity\SavedSearch;
use App\Twitter\Domain\Http\Client\MemberProfileAwareHttpClientInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Domain\Persistence\PersistenceLayerInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\TweetPersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Publication\TweetPublicationPersistenceLayerTrait;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Adapter\StatusToArray;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Operation\Collection\Collection;
use App\Twitter\Infrastructure\Publication\Dto\TaggedTweet;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use Doctrine\ORM\EntityManagerInterface;
use function count;

const PROP_SCREEN_NAME = 'screen_name';
const PROP_FOREIGN_USER_ID = 'foreign_user_id';
class PersistenceLayer implements PersistenceLayerInterface
{
    use MemberRepositoryTrait;
    use TweetPublicationPersistenceLayerTrait;
    use TweetPersistenceLayerTrait;
    private MemberProfileAwareHttpClientInterface $memberProfileHttpClient;
    private EntityManagerInterface $entityManager;

    public function __construct(
        MemberProfileAwareHttpClientInterface     $memberProfileHttpClient,
        MemberRepositoryInterface                 $memberRepository,
        EntityManagerInterface                    $entityManager,
    ) {
        $this->entityManager = $entityManager;
        $this->memberRepository = $memberRepository;
        $this->memberProfileHttpClient = $memberProfileHttpClient;
    }

    public function persistSearchBasedTweetsCollection(
        AccessToken $identifier,
        SavedSearch $savedSearch,
        array $rawTweets
    ): CollectionInterface {
        $tweetPersistenceLayer = $this->tweetPersistenceLayer;
        $result                = $tweetPersistenceLayer->persistSearchQueryBasedTweetsCollection(
            $identifier,
            $savedSearch,
            $rawTweets
        );
        $normalizedTweetsCollection = $result[$tweetPersistenceLayer::PROPERTY_NORMALIZED_STATUS];
        $searchQuery                = $result[$tweetPersistenceLayer::PROPERTY_SEARCH_QUERY];

        // Mark status as published
        $tweetCollection = new Collection(
            $result[$tweetPersistenceLayer::PROPERTY_STATUS]->toArray()
        );
        $tweetCollection->map(fn(TweetInterface $tweet) => $tweet->markAsPublished());

        // Make publications
        $tweetAsArrayCollection = StatusToArray::fromStatusCollection($tweetCollection);
        $this->tweetPublicationPersistenceLayer->persistTweetsCollection($tweetAsArrayCollection);

        // Commit transaction
        $this->entityManager->flush();

        if (($normalizedTweetsCollection instanceof CollectionInterface) && $normalizedTweetsCollection->count() > 0) {
            $col = [];
            $normalizedTweetsCollection->map(function (TaggedTweet $tweet) use (&$col) {
                $col[$tweet->screenName()] = [
                    PROP_SCREEN_NAME => $tweet->screenName(),
                    PROP_FOREIGN_USER_ID => json_decode($tweet->document())->user->id_str
                ];
            });

            foreach ($col as $el) {
                $this->memberProfileHttpClient->getMemberByIdentity(
                    new MemberIdentity(
                        $el[PROP_SCREEN_NAME],
                        $el[PROP_FOREIGN_USER_ID]
                    )
                );
            }
        }

        return $tweetCollection;
    }


    public function persistTweetsCollection(
        array $tweets,
        AccessToken $identifier,
        PublishersList $twitterList = null
    ): CollectionInterface {
        $tweetPersistenceLayer = $this->tweetPersistenceLayer;
        $result           = $tweetPersistenceLayer->persistTweetsCollection(
            $tweets,
            $identifier,
            $twitterList
        );
        $normalizedTweetsCollection = $result[$tweetPersistenceLayer::PROPERTY_NORMALIZED_STATUS];
        $screenName       = $result[$tweetPersistenceLayer::PROPERTY_SCREEN_NAME];

        // Mark status as published
        $tweetCollection = new Collection(
            $result[$tweetPersistenceLayer::PROPERTY_STATUS]->toArray()
        );
        $tweetCollection->map(fn(TweetInterface $status) => $status->markAsPublished());

        // Make publications
        $tweetCollection = StatusToArray::fromStatusCollection($tweetCollection);
        $this->tweetPublicationPersistenceLayer->persistTweetsCollection($tweetCollection);

        // Commit transaction
        $this->entityManager->flush();

        if (count($normalizedTweetsCollection) > 0) {
            $this->memberRepository->incrementTotalStatusesOfMemberWithName(
                count($normalizedTweetsCollection),
                $screenName
            );
        }

        return $normalizedTweetsCollection;
    }
}
