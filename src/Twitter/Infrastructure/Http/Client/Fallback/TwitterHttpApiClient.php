<?php

namespace App\Twitter\Infrastructure\Http\Client\Fallback;

use App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException;
use App\Twitter\Domain\Http\Client\Fallback\TwitterHttpApiClientInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Infrastructure\DependencyInjection\Http\TwitterHttpApiAwareTrait;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Persistence\TweetPersistenceLayer;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function Safe\gzdecode as safeGzipDecode;

class TwitterHttpApiClient implements TwitterHttpApiClientInterface
{
    use TwitterHttpApiAwareTrait;

    private $memberProfiles = [];

    private array $tokensPool = [];

    public function __construct(
        private readonly string $twitterAPIBearerToken,
        private readonly HttpClientInterface $client,
        private readonly TweetPersistenceLayer $persistenceLayer,
        private readonly LoggerInterface $logger
    )
    {
    }

    /**
     * @throws \JsonException
     * @throws \Safe\Exceptions\ZlibException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Exception
     */
    private function requestAccessToken(): string
    {
        $response = $this->client->request(
            'POST',
            'https://api.twitter.com/1.1/guest/activate.json',
            [
                'headers' => [
                    "accept-encoding"       => "gzip",
                    "accept-language"       => "en-US,en;q=0.5",
                    "connection"            => "keep-alive",
                    "authorization"         => "Bearer {$this->twitterAPIBearerToken}",
                    "content-type"          => "application/json",
                    "x-guest-token"         => "",
                    "x-twitter-active-user" => "yes",
                    "authority"             => "api.twitter.com",
                    "accept"                => "*/*",
                    "DNT"                   => "1"
                ]
            ]
        );

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new \Exception('Invalid response received when requesting access token');
        }

        $accessToken = json_decode(safeGzipDecode($response->getContent()), associative: true, flags: JSON_THROW_ON_ERROR)['guest_token'];

        if (count($this->tokensPool) < 10) {
            $this->tokensPool[] = $accessToken;

            return $accessToken;
        } else {
            return $this->tokensPool[0];
        }
    }

    /**
     * @param string $endpoint
     * @return \Symfony\Contracts\HttpClient\ResponseInterface
     * @throws \JsonException
     * @throws \Safe\Exceptions\ZlibException
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function get(string $endpoint): ResponseInterface
    {
        $accessToken = $this->requestAccessToken();

        return $this->client->request(
            'GET',
            $endpoint,
            [
                'headers' => [
                    "accept-encoding"       => "gzip",
                    "accept-language"       => "en-US,en;q=0.5",
                    "connection"            => "keep-alive",
                    "authorization"         => "Bearer {$this->twitterAPIBearerToken}",
                    "content-type"          => "application/json",
                    "x-guest-token"         => "{$accessToken}",
                    "x-twitter-active-user" => "yes",
                    "authority"             => "api.twitter.com",
                    "accept"                => "*/*",
                    "DNT"                   => "1"
                ]
            ]
        );
    }

    /**
     * @throws \App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Safe\Exceptions\ZlibException
     * @throws \JsonException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getMemberTimeline(MemberIdentity $memberIdentity): CollectionInterface
    {
        $this->logEndpointAccess(self::API_ENDPOINT_MEMBER_TIMELINE);

        $memberProfile = (array) $this->memberProfiles[strtolower($memberIdentity->screenName())];

        $params = [
            'variables' => json_encode([
                "userId" =>  $memberProfile['id_str'],
                "count" => 40,
                "includePromotedContent" => true,
                "withQuickPromoteEligibilityTweetFields" => true,
                "withVoice" => true,
                "withV2Timeline" => true,
                "includeProfileInterstitialType" => false,
                "includeBlocking" => false,
                "includeBlockedBy" => false,
                "includeFollowedBy" => false,
                "includeWantRetweets" => false,
                "includeMuteEdge" => false,
                "includeCanDm" => false,
                "includeCanMediaTag" => true,
                "includeExtIsBlueVerified" => true,
                "skipStatus" => true,
                "cardsPlatform" => "Web-12",
                "includeCards" => true,
                "includeComposerSource" => false,
                "includeReplyCount" => true,
                "tweetMode" => "extended",
                "includeEntities" => true,
                "includeUserEntities" => true,
                "includeExtMediaColor" => false,
                "sendErrorCodes" => true,
                "simpleQuotedTweet" => true,
                "includeQuoteCount" => true
            ]),

            'features' => json_encode([
                'rweb_lists_timeline_redesign_enabled' => true,
                'responsive_web_graphql_exclude_directive_enabled' => true,
                'verified_phone_label_enabled' => false,
                'creator_subscriptions_tweet_preview_api_enabled' => true,
                'responsive_web_graphql_timeline_navigation_enabled' => true,
                'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
                'tweetypie_unmention_optimization_enabled' => true,
                'responsive_web_edit_tweet_api_enabled' => true,
                'graphql_is_translatable_rweb_tweet_is_translatable_enabled' => true,
                'view_counts_everywhere_api_enabled' => true,
                'longform_notetweets_consumption_enabled' => true,
                'tweet_awards_web_tipping_enabled' => false,
                'freedom_of_speech_not_reach_fetch_enabled' => true,
                'standardized_nudges_misinfo' => true,
                'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => false,
                'interactive_text_enabled' => true,
                'responsive_web_text_conversations_enabled' => false,
                'longform_notetweets_rich_text_read_enabled' => true,
                'longform_notetweets_inline_media_enabled' => false,
                'responsive_web_enhance_cards_enabled' => false,
            ])
        ];

        $endpoint = sprintf(
            'https://twitter.com/i/api%s%s',
            self::API_GRAPHQL_ENDPOINT_MEMBER_TIMELINE,
            sprintf(
                '?variables=%s&features=%s',
                urlencode($params['variables']),
                urlencode($params['features']),
            ),
        );

        try {
            $response = $this->get($endpoint);
            $content = $response->getContent();

            $tweets = json_decode(
                safeGzipDecode($content),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );

            if (!isset($tweets['data']['user']['result']['timeline_v2']['timeline']['instructions'][1]['entries'])) {
                throw new \Exception('Invalid tweets');
            }

            $tweets = $tweets['data']['user']['result']['timeline_v2']['timeline']['instructions'][1]['entries'];

            $tweets = array_map(
                function ($tweet) {
                    $userId = $tweet['content']['itemContent']['tweet_results']['result']['legacy']['user_id_str'];
                    $tweet['content']['itemContent']['tweet_results']['result']['legacy']['user'] = $this->memberProfiles[$userId];

                    return (object) $tweet['content']['itemContent']['tweet_results']['result']['legacy'];
                },
                array_filter(
                    $tweets,
                    function ($tweet) {
                        return isset($tweet['content']['itemContent']['tweet_results']['result']['legacy']);
                    }
                )
            );

            $persistedTweetCollection = $this->persistenceLayer->persistTweetsCollection(
                $tweets,
                new AccessToken('guest-token'),
            );

            return $persistedTweetCollection[$this->persistenceLayer::PROPERTY_TWEET];
        } catch (\Throwable $e) {
            throw new FallbackHttpAccessException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws \App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \JsonException
     * @throws \Safe\Exceptions\ZlibException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getMemberOwnerships(ListSelectorInterface $selector): OwnershipCollectionInterface {

        $this->logEndpointAccess(self::API_ENDPOINT_OWNERSHIPS);

        $response = $this->get(sprintf(
            '%s%s.json?screen_name=%s&count=1000&cursor=%d',
            $this->getApiBaseUrl(),
            self::API_ENDPOINT_OWNERSHIPS,
            $selector->screenName(),
            $selector->cursor(),
        ));

        $content = $response->getContent();

        try {
            $ownerships = json_decode(safeGzipDecode($content), flags: JSON_THROW_ON_ERROR);

            return OwnershipCollection::fromArray(
                $ownerships->lists,
                $ownerships->next_cursor,
            );
        } catch (\Throwable $e) {
            throw new FallbackHttpAccessException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws \App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \JsonException
     * @throws \Safe\Exceptions\ZlibException
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     */
    public function getMemberProfile(MemberIdentity $memberIdentity): \stdClass {

        $endpoint = self::API_ENDPOINT_GET_MEMBER_PROFILE;

        $this->logEndpointAccess($endpoint);

        $response = $this->get(sprintf(
            '%s%s.json?screen_name=%s',
            $this->getApiBaseUrl(),
            $endpoint,
            $memberIdentity->screenName(),
        ));

        $content = $response->getContent();

        $accountIdentifier = ((array) json_decode(safeGzipDecode($content)))['id_str'];

        $this->memberProfiles[strtolower($memberIdentity->screenName())] = json_decode(safeGzipDecode($content), flags: JSON_THROW_ON_ERROR);
        $this->memberProfiles[$accountIdentifier] = json_decode(safeGzipDecode($content), flags: JSON_THROW_ON_ERROR);

        try {
            return $this->memberProfiles[strtolower($memberIdentity->screenName())];
        } catch (\Throwable $e) {
            throw new FallbackHttpAccessException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function logEndpointAccess(string $endpoint): void
    {
        $this->logger->info("[ accessing Twitter API endpoint via fallback: \"{$endpoint}\" ]");
    }
}