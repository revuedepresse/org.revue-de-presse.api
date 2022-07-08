<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Publication;

use App\Twitter\Domain\PublishersList\Repository\MembersListRepositoryInterface;

trait MembersListRepositoryTrait
{
    private MembersListRepositoryInterface $membersListRepository;

    public function setMembersListRepository(
        MembersListRepositoryInterface $membersListRepository
    ): self {
        $this->membersListRepository = $membersListRepository;

        return $this;
    }

}
