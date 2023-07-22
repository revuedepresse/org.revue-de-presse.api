<?php

namespace App\Twitter\Infrastructure\Http\Client\Fallback;

use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\Entity\Legacy\Member;
use App\Twitter\Domain\Http\Client\Fallback\Exception\FallbackHttpAccessException;
use App\Twitter\Domain\Http\Client\Fallback\TwitterHttpApiClientInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Domain\Operation\Collection\CollectionInterface;
use App\Twitter\Infrastructure\DependencyInjection\Http\TwitterHttpApiAwareTrait;
use App\Twitter\Infrastructure\Http\AccessToken\AccessToken;
use App\Twitter\Infrastructure\Http\Client\Exception\InvalidMemberTimelineException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;
use App\Twitter\Infrastructure\Persistence\TweetPersistenceLayer;
use Exception;
use JsonException;
use Psr\Log\LoggerInterface;
use Safe\Exceptions\ZlibException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
     * @throws JsonException
     * @throws ZlibException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    private function requestAccessToken(): string
    {
        if (count($this->tokensPool) === 10) {
            return $this->tokensPool[0];
        }

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
            if (count($this->tokensPool) > 2) {
                array_shift($this->tokensPool);
                return $this->tokensPool[0];
            }  else {
                return '';
            }

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
     * @return ResponseInterface
     * @throws JsonException
     * @throws ZlibException
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function get(string $endpoint): ResponseInterface
    {
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
                    'x-guest-token'         => "{$this->requestAccessToken()}",
                    'x-twitter-active-user' => 'yes',
                    'authority'             => 'api.twitter.com',
                    'accept'                => '*/*',
                    'DNT'                   => '1',
                ]
            ]
        );
    }

    /**
     * @throws FallbackHttpAccessException
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
                throw new FallbackHttpAccessException(
                    'Could not fetch legacy member profile',
                    FallbackHttpAccessException::INVALID_MEMBER_PROFILE,
                );
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
        $endpoint = self::API_ENDPOINT_UNIVERSAL_SEARCH;

        $this->logEndpointAccess($endpoint);

        if (array_key_exists(strtolower($memberIdentity->screenName()), $this->memberProfiles) === false) {
            /** @var Member $member */
            $member = $memberRepository->findOneBy(['twitter_username' => $memberIdentity->screenName()]);
            $this->memberProfiles[strtolower($memberIdentity->screenName())] = [
                'id_str' => $member->twitterId()
            ];
        }

        $params = [
            'q' => "from:{$memberIdentity->screenName()} filter:self_threads OR-filter:replies include:nativeretweets",
            'modules' => 'status',
            'result_type' => 'recent',
        ];

        $endpoint = sprintf(
            'https://api.twitter.com%s%s',
            $endpoint,
            sprintf(
                '?q=%s&modules=%s&result_type=%s',
                urlencode($params['q']),
                urlencode($params['modules']),
                urlencode($params['result_type']),
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

            $memberProfile = $this->memberProfileLegacyGetter($memberIdentity);

            if (empty($tweets['modules'][0]['status']['data'])) {
                InvalidMemberTimelineException::throws($memberIdentity->screenName());
            } else {
                $tweets = array_map(
                    function ($t) use ($memberProfile) {
                        $t['status']['data']['user'] = $memberProfile;

                        return (object) $t['status']['data'];
                    },
                    $tweets['modules']
                );
            }

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
     * @throws FallbackHttpAccessException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws ZlibException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
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
     * @throws FallbackHttpAccessException
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws JsonException
     * @throws ZlibException
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
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