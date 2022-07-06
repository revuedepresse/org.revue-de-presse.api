<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Http\Client;

use App\Twitter\Domain\Http\Selector\AuthenticatedSelectorInterface;
use App\Twitter\Domain\Http\Selector\ListSelectorInterface;
use App\Twitter\Infrastructure\Http\Resource\MemberOwnerships;
use App\Twitter\Infrastructure\Http\Resource\OwnershipCollectionInterface;

interface ListAwareHttpClientInterface extends ApiEndpointsAwareInterface
{
    public const MAX_OWNERSHIPS = 800;

    public function getMemberOwnerships(ListSelectorInterface $selector): OwnershipCollectionInterface;

    public function getOwnershipsForMemberHavingScreenNameAndToken(
        AuthenticatedSelectorInterface $selector,
        MemberOwnerships $memberOwnership = null
    ): MemberOwnerships;
}