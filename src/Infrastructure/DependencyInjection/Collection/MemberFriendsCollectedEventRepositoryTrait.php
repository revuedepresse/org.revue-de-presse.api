<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Collection;

use App\Infrastructure\Collection\Repository\MemberFriendsCollectedEventRepositoryInterface;

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