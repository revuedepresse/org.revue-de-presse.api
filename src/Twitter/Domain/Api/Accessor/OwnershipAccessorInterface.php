<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Api\Accessor;

use App\Twitter\Domain\Api\Selector\AuthenticatedSelectorInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;
use App\Twitter\Domain\Resource\MemberOwnerships;
use App\Twitter\Domain\Resource\OwnershipCollectionInterface;

interface OwnershipAccessorInterface extends TwitterApiEndpointsAwareInterface
{
    public const MAX_OWNERSHIPS = 800;

    public function getMemberOwnerships(ListSelectorInterface $selector): OwnershipCollectionInterface;

    public function getOwnershipsForMemberHavingScreenNameAndToken(
        AuthenticatedSelectorInterface $selector,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships;
}