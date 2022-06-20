<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Api\Resource\MemberCollectionInterface;
use App\Twitter\Domain\Api\TwitterErrorAwareInterface;
use stdClass;

interface ApiAccessorInterface extends TwitterErrorAwareInterface
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

    public function setAccessToken(string $token): ApiAccessorInterface;

    public function accessToken(): string;

    public function setAccessTokenSecret(string $tokenSecret): ApiAccessorInterface;

    public function fromToken(TokenInterface $token): void;

    public function setConsumerKey(string $secret): self;

    public function consumerKey(): string;

    public function setConsumerSecret(string $secret): self;

    public function getListMembers(string $listId): MemberCollectionInterface;

    public function getMemberProfile(string $identifier): stdClass;

    public function contactEndpoint(string $endpoint);
}