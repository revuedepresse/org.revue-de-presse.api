<?php

namespace App\Twitter\Domain\Api\Accessor;

interface MembersListAccessorInterface
{
    public function addMembersToList(array $members, string $listId);
}