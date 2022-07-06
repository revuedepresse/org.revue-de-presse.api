<?php

namespace App\Twitter\Domain\Http\Client;

interface MembersBatchAwareHttpClientInterface
{
    public function addMembersToList(array $members, string $listId);
}