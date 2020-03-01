<?php
declare(strict_types=1);

namespace App\Infrastructure\Repository\Subscription;

use App\Member\MemberInterface;
use Symfony\Component\HttpFoundation\Request;
use WeavingTheWeb\Bundle\ApiBundle\Entity\MemberSubscriptionInterface;

interface MemberSubscriptionRepositoryInterface
{
    public function getMemberSubscriptions(
        MemberInterface $member,
        Request $request
    ): MemberSubscriptionInterface;
}