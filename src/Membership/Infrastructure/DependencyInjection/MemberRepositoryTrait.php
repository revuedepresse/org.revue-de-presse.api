<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\DependencyInjection;

use App\Membership\Domain\Repository\MemberRepositoryInterface;

trait MemberRepositoryTrait
{
    protected MemberRepositoryInterface $memberRepository;

    public function setMemberRepository(MemberRepositoryInterface $memberRepository): self
    {
        $this->memberRepository = $memberRepository;

        return $this;
    }
}