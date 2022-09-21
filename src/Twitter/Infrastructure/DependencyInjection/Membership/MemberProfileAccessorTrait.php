<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\DependencyInjection\Membership;

use App\Twitter\Domain\Http\Client\MemberProfileAwareHttpClientInterface;

trait MemberProfileAccessorTrait
{
    private MemberProfileAwareHttpClientInterface $memberProfileAccessor;

    public function setMemberProfileAccessor(
        MemberProfileAwareHttpClientInterface $memberProfileAccessor
    ): self {
        $this->memberProfileAccessor = $memberProfileAccessor;

        return $this;
    }
}
