<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Client;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Http\Resource\MemberCollectionInterface;
use App\Twitter\Domain\Http\ApiErrorCodeAwareInterface;
use stdClass;

interface HttpClientInterface extends ApiErrorCodeAwareInterface
{
    public const BASE_URL = 'https://api.twitter.com/';

    public const TWITTER_API_VERSION_1_1 = '1.1';

    public const TWITTER_API_VERSION_2 = '2';

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface;

    public function guardAgainstApiLimit(
        string $endpoint,
        bool $findNextAvailableToken = true
    ): ?TokenInterface;

    public function getApiBaseUrl(string $version = self::TWITTER_API_VERSION_1_1): string;

    public function setAccessToken(string $token): HttpClientInterface;

    public function accessToken(): string;

    public function setAccessTokenSecret(string $tokenSecret): HttpClientInterface;

    public function showStatus(string $identifier): mixed;

    public function fromToken(TokenInterface $token): void;

    public function setConsumerKey(string $secret): self;

    public function consumerKey(): string;

    public function setConsumerSecret(string $secret): self;

    public function getListMembers(string $listId): MemberCollectionInterface;

    public function getMemberProfile(string $identifier): stdClass;

    public function contactEndpoint(string $endpoint);
}