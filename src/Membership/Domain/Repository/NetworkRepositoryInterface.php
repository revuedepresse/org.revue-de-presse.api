<?php
declare (strict_types=1);

namespace App\Membership\Domain\Repository;

use App\Membership\Domain\Model\MemberInterface;

interface NetworkRepositoryInterface
{
    public function ensureMemberExists(string $memberId): ?MemberInterface;

    public function saveNetwork(array $members): void;
}