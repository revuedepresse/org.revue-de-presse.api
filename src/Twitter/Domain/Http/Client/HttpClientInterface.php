<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Client;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Http\Model\TokenInterface;
use App\Twitter\Domain\Http\Resource\MemberCollectionInterface;
use App\Twitter\Domain\Http\TwitterAPIAwareInterface;
use stdClass;

interface HttpClientInterface extends TwitterAPIAwareInterface
{
    public function contactEndpoint(string $endpoint);

    public function ensureMemberHavingNameExists(string $memberName): MemberInterface;

    public function guardAgainstApiLimit(
        string $endpoint,
        bool $findNextAvailableToken = true
    ): ?TokenInterface;

    public function getApiBaseUrl(string $version = self::TWITTER_API_VERSION_1_1): string;

    public function setAccessToken(string $token): HttpClientInterface;

    public function accessToken(): string;

    public function setAccessTokenSecret(string $tokenSecret): HttpClientInterface;

    public function fromToken(TokenInterface $token): void;

    public function setConsumerKey(string $secret): self;

    public function consumerKey(): string;

    public function setConsumerSecret(string $secret): self;

    public function getListMembers(string $listId): MemberCollectionInterface;

    public function getMemberProfile(string $identifier): stdClass;
}
