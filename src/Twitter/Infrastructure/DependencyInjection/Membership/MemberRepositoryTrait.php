<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;

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