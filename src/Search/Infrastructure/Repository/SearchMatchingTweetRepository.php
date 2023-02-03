<?php

namespace App\Search\Infrastructure\Repository;

use App\Search\Domain\Entity\RecordedSearchMatchingTweet;
use App\Search\Domain\Entity\SavedSearch;
use App\Search\Domain\Entity\SearchMatchingTweet;
use App\Twitter\Domain\Publication\Repository\TweetRepositoryInterface;
use App\Twitter\Domain\Publication\TweetInterface;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Psr\Log\LoggerInterface;

class SearchMatchingTweetRepository extends ServiceEntityRepository
{
    public LoggerInterface $logger;

    public TweetRepositoryInterface $tweetRepository;

    public function saveSearchMatchingStatus(
        SavedSearch $savedSearch,
        array       $rawTweets,
        string      $identifier
    ): int {
        $col = $this->tweetRepository->persistSearchBasedTweetsCollection(
            new AccessToken($identifier),
            $savedSearch,
            $rawTweets
        );

        $tweets = $col->toArray();

        $recordedTweets = array_filter(
            array_map(
                function (TweetInterface $tweets) use ($savedSearch): SearchMatchingTweet {
                    $searchMatchingStatus = $this->findOneBy(['tweet' => $tweets, 'savedSearch' => $savedSearch]);
                    if ($searchMatchingStatus instanceof SearchMatchingTweet) {
                        return RecordedSearchMatchingTweet::fromSearchQueryMatchingTweet($searchMatchingStatus);
                    }

                    $searchMatchingStatus = new SearchMatchingTweet($tweets, $savedSearch);

                    $this->getEntityManager()->persist($searchMatchingStatus);

                    return $searchMatchingStatus;
                },
                $tweets
            ),
            fn ($subject) => !($subject instanceof RecordedSearchMatchingTweet)
        );

        $this->getEntityManager()->flush();

        return count($recordedTweets);
    }
}
