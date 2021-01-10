<?php

namespace App\Twitter\Domain\Api\Accessor;

use stdClass;

interface MembersListAccessorInterface
{
    public function addMembersToList(array $members, string $listId): ?stdClass;
}