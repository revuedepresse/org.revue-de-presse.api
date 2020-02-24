<?php
declare(strict_types=1);

namespace App\Infrastructure\Twitter\Api\Accessor;

use App\Api\Entity\TokenInterface;
use App\Domain\Resource\MemberOwnerships;

interface OwnershipAccessorInterface
{
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        string $screenName,
        TokenInterface $token
    ): MemberOwnerships;
}