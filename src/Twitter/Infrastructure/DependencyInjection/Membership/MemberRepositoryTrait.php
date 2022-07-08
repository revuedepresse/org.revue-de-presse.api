<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;

trait MemberRepositoryTrait
{
    protected MemberRepositoryInterface $memberRepository;

    public function setMemberRepository(MemberRepositoryInterface $memberRepository): self
    {
        $this->memberRepository = $memberRepository;

        return $this;
    }
}