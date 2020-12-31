<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Twitter\Domain\Api\MemberOwnershipsAccessorInterface;
use App\Twitter\Domain\Api\Model\TokenInterface;
use App\Twitter\Domain\Resource\MemberCollection;
use stdClass;

interface ApiAccessorInterface extends MemberOwnershipsAccessorInterface
{
    public const MAX_OWNERSHIPS = 800;

    public function getApiBaseUrl(): string;

    public function setAccessToken(string $token): ApiAccessorInterface;

    public function setAccessTokenSecret(string $tokenSecret): ApiAccessorInterface;

    public function fromToken(TokenInterface $token): void;

    public function setConsumerKey(string $secret): self;

    public function setConsumerSecret(string $secret): self;

    public function getListMembers(string $listId): MemberCollection;

    public function getMemberProfile(string $identifier): stdClass;

    public function contactEndpoint(string $endpoint);
}