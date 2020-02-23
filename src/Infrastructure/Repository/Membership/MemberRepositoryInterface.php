<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Membership;

use App\Domain\Resource\MemberIdentity;
use App\Membership\Entity\MemberInterface;

interface MemberRepositoryInterface
{
    public function saveProtectedMember(MemberIdentity $memberIdentity): MemberInterface;

    public function saveSuspendedMember(MemberIdentity $memberIdentity): MemberInterface;
}