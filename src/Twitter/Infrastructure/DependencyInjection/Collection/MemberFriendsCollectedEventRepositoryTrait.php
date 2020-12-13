<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Collection;

use App\Twitter\Infrastructure\Curation\Repository\MemberFriendsCollectedEventRepositoryInterface;

trait MemberFriendsCollectedEventRepositoryTrait
{
    private MemberFriendsCollectedEventRepositoryInterface $memberFriendsCollectedEventRepository;

    public function setMemberFriendsCollectedEventRepository(
        MemberFriendsCollectedEventRepositoryInterface $memberFriendsCollectedEventRepository
    ): self {
        $this->memberFriendsCollectedEventRepository = $memberFriendsCollectedEventRepository;

        return $this;
    }
}