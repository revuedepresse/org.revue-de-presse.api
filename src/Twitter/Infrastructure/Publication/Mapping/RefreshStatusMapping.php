<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Publication\Mapping;

use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException;
use App\Twitter\Infrastructure\Publication\Entity\PublishersList;
use App\Twitter\Infrastructure\Http\Entity\Tweet;
use App\Twitter\Infrastructure\DependencyInjection\Http\HttpClientTrait;
use App\Twitter\Infrastructure\DependencyInjection\LoggerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Persistence\PersistenceLayerTrait;
use App\Twitter\Infrastructure\DependencyInjection\Status\TweetRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;

class RefreshStatusMapping implements MappingAwareInterface
{
    use HttpClientTrait;
    use LoggerTrait;
    use PersistenceLayerTrait;
    use TweetRepositoryTrait;

    private array $oauthTokens;

    public function __construct(HttpClientInterface $accessor)
    {
        $this->httpClient = $accessor;
    }

    public function setOAuthTokens(array $oauthTokens) {
        $this->oauthTokens = $oauthTokens;
        $this->setupAccessor($oauthTokens);
    }

    /**
     * @param $oauthTokens
     */
    protected function setupAccessor($oauthTokens)
    {
        $this->httpClient->propagateNotFoundStatuses = true;

        $this->httpClient->setAccessToken($oauthTokens['token']);
        $this->httpClient->setAccessTokenSecret($oauthTokens['secret']);

        if (array_key_exists('consumer_token', $oauthTokens)) {
            $this->httpClient->setConsumerKey($oauthTokens['consumer_token']);
            $this->httpClient->setConsumerSecret($oauthTokens['consumer_secret']);
        }
    }

    /**
     * @param Tweet $status
     * @return Tweet
     */
    public function apply(Tweet $status): Tweet {
        try {
            $apiDocument = $this->httpClient->showStatus($status->getStatusId());
        } catch (TweetNotFoundException $exception) {
            return $status;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            return $status;
        }

        // TODO point at Status Logger
        $reachBeforeRefresh = $this->tweetRepository->extractTweetReach($status);

        $membersList = $status->getAggregates()->first();
        if (!($membersList instanceof PublishersList)) {
            $membersList = null;
        }

        try {
            $this->persistenceLayer->persistTweetsCollection(
                [$apiDocument],
                new AccessToken($status->getIdentifier()),
                $membersList
            );
        } catch (NotFoundMemberException $exception) {
            $this->httpClient->ensureMemberHavingNameExists($exception->screenName);
            $this->persistenceLayer->persistTweetsCollection(
                [$apiDocument],
                new AccessToken($status->getIdentifier()),
                $membersList
            );
        }

        $refreshedStatus = $this->tweetRepository->findOneBy(['id' => $status->getId()]);
        $reachAfterRefresh = $this->tweetRepository->extractTweetReach($refreshedStatus);

        $this->logger->info(sprintf(
            'Status with id %s had retweet count going from %d to %d',
            $status->getStatusId(),
            $reachBeforeRefresh['retweet_count'],
            $reachAfterRefresh['retweet_count']
        ));
        $this->logger->info(sprintf(
            'Status with id %s had favorite count going from %d to %d',
            $status->getStatusId(),
            $reachBeforeRefresh['favorite_count'],
            $reachAfterRefresh['favorite_count']
        ));

        return $refreshedStatus;
    }
}
