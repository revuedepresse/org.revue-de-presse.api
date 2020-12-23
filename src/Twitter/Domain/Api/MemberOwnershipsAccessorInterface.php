<?php
declare (strict_types=1);

namespace App\Twitter\Domain\Api;

use App\Twitter\Domain\Resource\OwnershipCollectionInterface;
use App\Twitter\Domain\Api\Selector\ListSelectorInterface;

interface MemberOwnershipsAccessorInterface
{
    public function getMemberOwnerships(ListSelectorInterface $selector): OwnershipCollectionInterface;
}