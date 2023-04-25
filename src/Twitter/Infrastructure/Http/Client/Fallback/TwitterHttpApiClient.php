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

        $response = $this->get(sprintf(
            '%s%s.json?tweet_mode=extended&include_entities=1&include_rts=1&exclude_replies=0&trim_user=0&screen_name=%s',
            $this->getApiBaseUrl(),
            self::API_ENDPOINT_MEMBER_TIMELINE,
            $memberIdentity->screenName()
        ));

        $content = $response->getContent();

        try {
            $tweets = json_decode(safeGzipDecode($content), flags: JSON_THROW_ON_ERROR);
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

        try {
            return json_decode(safeGzipDecode($content), flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new FallbackHttpAccessException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function logEndpointAccess(string $endpoint): void
    {
        $this->logger->info("[ accessing Twitter API endpoint via fallback: \"{$endpoint}\" ]");
    }
}