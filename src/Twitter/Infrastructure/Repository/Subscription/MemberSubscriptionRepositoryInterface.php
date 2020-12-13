<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Repository\Subscription;

use App\Membership\Domain\Entity\MemberSubscription;
use App\Membership\Domain\Entity\MemberInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @method MemberSubscription|null find($id, $lockMode = null, $lockVersion = null)
 * @method MemberSubscription|null findOneBy(array $criteria, array $orderBy = null)
 * @method MemberSubscription[]    findAll()
 * @method MemberSubscription[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
interface MemberSubscriptionRepositoryInterface
{
    public function getMemberSubscriptions(
        MemberInterface $member,
        Request $request
    ): array;

    public function getCancelledMemberSubscriptions(MemberInterface $subscriber): array;

    public function cancelMemberSubscription(
        MemberInterface $member,
        MemberInterface $subscription
    ): MemberSubscription;
}