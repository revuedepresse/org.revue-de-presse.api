<?php

namespace App\Twitter\Domain\Http\Client;

interface MembersBatchAwareHttpClientInterface
{
    public function addMembersToListSequentially(array $members, string $listId);

}