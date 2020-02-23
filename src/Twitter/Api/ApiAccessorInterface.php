<?php
declare(strict_types=1);

namespace App\Twitter\Api;

use App\Api\Entity\TokenInterface;
use App\Twitter\Api\Resource\OwnershipCollection;
use App\Twitter\Exception\UnavailableResourceException;

interface ApiAccessorInterface
{
    public function setAccessToken(TokenInterface $token);

    public function setOAuthToken(string $token): self;

    public function setOAuthSecret(string $secret): self;

    public function setConsumerKey(string $secret): self;

    public function setConsumerSecret(string $secret): self;

    public function getMemberOwnerships(
        string $screenName,
        int $cursor = -1,
        int $count = 800
    ): OwnershipCollection;

    public function getListMembers(int $listId): \stdClass;

    public function getMemberProfile($identifier): \stdClass;
}