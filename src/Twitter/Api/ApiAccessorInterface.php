<?php
declare(strict_types=1);

namespace App\Twitter\Api;

use App\Api\Entity\TokenInterface;
use App\Domain\Resource\MemberCollection;
use App\Domain\Resource\OwnershipCollection;
use stdClass;

interface ApiAccessorInterface
{
    public const MAX_OWNERSHIPS = 800;

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
}