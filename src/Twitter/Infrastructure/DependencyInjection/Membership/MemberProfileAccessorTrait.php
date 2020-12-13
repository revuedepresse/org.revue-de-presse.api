<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\MemberProfileAccessorInterface;

trait MemberProfileAccessorTrait
{
    private MemberProfileAccessorInterface $memberProfileAccessor;

    /**
     * @param MemberProfileAccessorInterface $memberProfileAccessor
     *
     * @return $this
     */
    public function setMemberProfileAccessor(
        MemberProfileAccessorInterface $memberProfileAccessor
    ): self {
        $this->memberProfileAccessor = $memberProfileAccessor;

        return $this;
    }
}