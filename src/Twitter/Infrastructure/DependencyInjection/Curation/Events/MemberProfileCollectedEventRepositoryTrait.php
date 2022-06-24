<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Curation\Events;

use App\Twitter\Domain\Curation\Repository\MemberProfileCollectedEventRepositoryInterface;

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