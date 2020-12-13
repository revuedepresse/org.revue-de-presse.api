<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Collection;

use App\Domain\Curation\Repository\MemberProfileCollectedEventRepositoryInterface;

trait MemberProfileCollectedEventRepositoryTrait
{
    private MemberProfileCollectedEventRepositoryInterface $memberProfileCollectedEventRepository;

    public function setMemberProfileCollectedEventRepository(
        MemberProfileCollectedEventRepositoryInterface $memberProfileCollectedEventRepository
    ): self {
        $this->memberProfileCollectedEventRepository = $memberProfileCollectedEventRepository;

        return $this;
    }
}