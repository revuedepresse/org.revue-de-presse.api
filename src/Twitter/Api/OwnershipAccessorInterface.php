<?php
declare(strict_types=1);

namespace App\Twitter\Api;

use App\Api\Entity\TokenInterface;
use App\Domain\Resource\MemberOwnerships;

interface OwnershipAccessorInterface
{
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        string $screenName,
        TokenInterface $token
    ): MemberOwnerships;
}