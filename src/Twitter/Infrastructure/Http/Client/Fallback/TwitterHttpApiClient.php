<?php

namespace App\Twitter\Infrastructure\Http\Client\Fallback;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
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
                    'accept-encoding'       => 'gzip',
                    'accept-language'       => 'en-US,en;q=0.5',
                    'connection'            => 'keep-alive',
                    'authorization'         => vsprintf('Bearer %s',  [$this->twitterAPIBearerToken]),
                    'content-type'          => 'application/json',
                    'x-guest-token'         => '',
                    'x-twitter-active-user' => 'yes',
                    'authority'             => 'api.twitter.com',
                    'accept'                => '*/*',
                    'DNT'                   => '1'
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
                    'accept-encoding'       => 'gzip',
                    'accept-language'       => 'en-US,en;q=0.5',
                    'connection'            => 'keep-alive',
                    'authorization'         => vsprintf('Bearer %s',  [$this->twitterAPIBearerToken]),
                    'content-type'          => 'application/json',
                    'x-guest-token'         => "{$accessToken}",
                    'x-twitter-active-user' => 'yes',
                    'authority'             => 'api.twitter.com',
                    'accept'                => '*/*',
                    'DNT'                   => '1',
                ]
            ]
        );
    }

    /**
     * @throws \App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException
     */
    public function getMemberProfile(MemberIdentity $memberIdentity): \stdClass {
        $endpoint = self::API_GRAPHQL_ENDPOINT_MEMBER_PROFILE;
        $this->logEndpointAccess($endpoint);

        $params = [
            'variables' => json_encode([
                'screen_name' => $memberIdentity->screenName(),
                'withSafetyModeUserFields' => true
            ]),
            'features' => json_encode([
                'hidden_profile_likes_enabled' => false,
                'responsive_web_graphql_exclude_directive_enabled' => true,
                'verified_phone_label_enabled' => false,
                'subscriptions_verification_info_verified_since_enabled' => true,
                'highlights_tweets_tab_ui_enabled' => true,
                'creator_subscriptions_tweet_preview_api_enabled' => true,
                'responsive_web_graphql_skip_user_profile_image_extensions_enabled' => false,
                'responsive_web_graphql_timeline_navigation_enabled' => true
            ])
        ];

        $endpoint = sprintf(
            'https://twitter.com/i/api%s%s',
            $endpoint,
            sprintf(
                '?variables=%s&features=%s',
                urlencode($params['variables']),
                urlencode($params['features']),
            ),
        );

        try {
            $response = $this->get($endpoint);
            $content = $response->getContent();

            $memberProfile = json_decode(
                safeGzipDecode($content),
                associative: true,
                flags: JSON_THROW_ON_ERROR
            );

            if (empty($memberProfile['data']['user']['result']['legacy'])) {
                throw new FallbackHttpAccessException('Could not fetch legacy member profile', FallbackHttpAccessException::INVALID_MEMBER_PROFILE, $e);
            }

            return (object) $memberProfile['data']['user']['result']['legacy'];
        } catch (\Throwable $e) {
            throw new FallbackHttpAccessException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @throws FallbackHttpAccessException
     */
    public function getMemberTimeline(
        MemberIdentity $memberIdentity,
        MemberRepositoryInterface $memberRepository
    ): CollectionInterface {
        $endpoint = self::API_GRAPHQL_ENDPOINT_MEMBER_TIMELINE;

        $this->logEndpointAccess($endpoint);

        if (array_key_exists(strtolower($memberIdentity->screenName()), $this->memberProfiles) === false) {
            /** @var \App\Membership\Infrastructure\Entity\Legacy\Member $member */
            $member = $memberRepository->findOneBy(['twitter_username' => $memberIdentity->screenName()]);
            $this->memberProfiles[strtolower($memberIdentity->screenName())] = [
                'id_str' => $member->twitterId()
            ];
        }

        $memberProfile = (array) $this->memberProfiles[strtolower($memberIdentity->screenName())];

        $params = [
            'variables' => json_encode([
                'userId' => $memberProfile['id_str'],
                'count' => 20,
                'includePromotedContent' => true,
                'withQuickPromoteEligibilityTweetFields' => true,
                'withVoice' => true,
                'withV2Timeline' => true
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
                'responsive_web_twitter_article_tweet_consumption_enabled' => false,
                'tweet_awards_web_tipping_enabled' => false,
                'freedom_of_speech_not_reach_fetch_enabled' => true,
                'standardized_nudges_misinfo' => true,
                'tweet_with_visibility_results_prefer_gql_limited_actions_policy_enabled' => true,
                'longform_notetweets_rich_text_read_enabled' => true,
                'longform_notetweets_inline_media_enabled' => true,
                'responsive_web_media_download_video_enabled' => false,
                'responsive_web_enhance_cards_enabled' => false
            ]),
            'fieldToggles' => json_encode([
                'withAuxiliaryUserLabels' => false,
                'withArticleRichContentState' => false
            ])
        ];

        $endpoint = sprintf(
            'https://api.twitter.com%s%s',
            $endpoint,
            sprintf(
                '?variables=%s&features=%s&fieldToggles=%s',
                urlencode($params['variables']),
                urlencode($params['features']),
                urlencode($params['fieldToggles']),
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

            $memberProfile = $this->memberProfileLegacyGetter($memberIdentity);

            $tweets = array_map(
                function ($tweet) use ($memberProfile) {
                    $tweet['content']['itemContent']['tweet_results']['result']['legacy']['user'] = $memberProfile;

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
    public function memberProfileLegacyGetter(MemberIdentity $memberIdentity): \stdClass {

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