<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Twitter\Api\Accessor;

use App\Twitter\Domain\Api\Selector\AuthenticatedSelectorInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;

interface OwnershipAccessorInterface
{
    public function getOwnershipsForMemberHavingScreenNameAndToken(
        AuthenticatedSelectorInterface $selector,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships;
}