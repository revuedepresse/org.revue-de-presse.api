<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Infrastructure\Api\Entity\TokenInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;

interface OwnershipAccessorInterface
{
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        string $screenName,
        TokenInterface $token,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships;
}