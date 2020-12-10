<?php
declare (strict_types=1);

namespace App\Member\Repository;

use App\Membership\Entity\MemberInterface;

interface NetworkRepositoryInterface
{
    public function ensureMemberExists(string $memberId): ?MemberInterface;
}