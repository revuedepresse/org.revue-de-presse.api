<?php
declare(strict_types=1);

namespace App\Twitter\Api;

use App\Api\Entity\TokenInterface;
use App\Twitter\Api\Resource\MemberCollection;
use App\Twitter\Api\Resource\OwnershipCollection;

interface ApiAccessorInterface
{
    public function setAccessToken(TokenInterface $token);

    public function setConsumerKey(string $secret): self;

    public function setConsumerSecret(string $secret): self;

    public function getMemberOwnerships(
        string $screenName,
        int $cursor = -1,
        int $count = 800
    ): OwnershipCollection;

    public function getListMembers(int $listId): MemberCollection;

    public function getMemberProfile($identifier): \stdClass;
}