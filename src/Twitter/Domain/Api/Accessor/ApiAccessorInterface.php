<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Membership\Domain\Model\MemberInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Api\Resource\MemberCollectionInterface;
use stdClass;

interface ApiAccessorInterface
{
    public function ensureMemberHavingNameExists(string $memberName): MemberInterface;

    public function guardAgainstApiLimit(
        string $endpoint,
        bool $findNextAvailableToken = true
    ): ?TokenInterface;

    public function getApiBaseUrl(): string;

    public function setAccessToken(string $token): ApiAccessorInterface;

    public function setAccessTokenSecret(string $tokenSecret): ApiAccessorInterface;

    public function fromToken(TokenInterface $token): void;

    public function setConsumerKey(string $secret): self;

    public function setConsumerSecret(string $secret): self;

    public function getListMembers(string $listId): MemberCollectionInterface;

    public function getMemberProfile(string $identifier): stdClass;

    public function contactEndpoint(string $endpoint);
}