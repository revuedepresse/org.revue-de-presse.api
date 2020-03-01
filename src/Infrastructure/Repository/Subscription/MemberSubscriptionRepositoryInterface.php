<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Subscription;

use App\Membership\Entity\MemberInterface;
use Symfony\Component\HttpFoundation\Request;

interface MemberSubscriptionRepositoryInterface
{
    public function getMemberSubscriptions(
        MemberInterface $member,
        Request $request
    ): array;
}