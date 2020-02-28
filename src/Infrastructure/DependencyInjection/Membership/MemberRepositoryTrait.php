<?php
declare(strict_types=1);

namespace App\Infrastructure\DependencyInjection\Membership;

use App\Infrastructure\Repository\Membership\MemberRepositoryInterface;

trait MemberRepositoryTrait
{
    /**
     * @var MemberRepositoryInterface
     */
    protected MemberRepositoryInterface $memberRepository;

    /**
     * @param MemberRepositoryInterface $memberRepository
     *
     * @return $this
     */
    public function setMemberRepository(MemberRepositoryInterface $memberRepository): self
    {
        $this->memberRepository = $memberRepository;

        return $this;
    }
}