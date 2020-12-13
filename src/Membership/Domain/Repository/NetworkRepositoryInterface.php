<?php
declare (strict_types=1);

namespace App\Membership\Domain\Repository;

use App\Membership\Domain\Entity\MemberInterface;

interface NetworkRepositoryInterface
{
    public function ensureMemberExists(string $memberId): ?MemberInterface;
}