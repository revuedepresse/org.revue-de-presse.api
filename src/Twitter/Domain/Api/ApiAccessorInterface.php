<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberCollection;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollection;
use stdClass;

interface ApiAccessorInterface
{
    public const MAX_OWNERSHIPS = 800;

    public function getApiBaseUrl(): string;

    public function setAccessToken(TokenInterface $token);

    public function setConsumerKey(string $secret): self;

    public function setConsumerSecret(string $secret): self;

    public function getMemberOwnerships(
        string $screenName,
        int $cursor = -1,
        int $count = self::MAX_OWNERSHIPS
    ): OwnershipCollection;

    public function getListMembers(string $listId): MemberCollection;

    public function getMemberProfile(string $identifier): stdClass;

    public function contactEndpoint(string $endpoint);
}