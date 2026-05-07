<?php
declare(strict_types=1);

namespace App\Twitter\Domain\Membership\Repository;

use App\Membership\Domain\Entity\MemberInterface;

interface MemberRepositoryInterface
{
    public function getMemberHavingApiKey(): MemberInterface;
}
