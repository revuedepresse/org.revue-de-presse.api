<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Subscription;

use App\Twitter\Infrastructure\Repository\Subscription\MemberSubscriptionRepositoryInterface;

trait MemberSubscriptionRepositoryTrait
{
    private MemberSubscriptionRepositoryInterface $memberSubscriptionRepository;

    public function setMemberSubscriptionRepository(
        MemberSubscriptionRepositoryInterface $memberSubscriptionRepository
    ): self {
        $this->memberSubscriptionRepository = $memberSubscriptionRepository;

        return $this;
    }
}