<?php

namespace App\Member\Repository;

use App\Member\Entity\NotFoundMember;
use App\Member\MemberInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;

class NetworkRepository
{

    /**
     * @var MemberSubscribeeRepository
     */
    public $memberSubscribeeRepository;

    /**
     * @var MemberSubscriptionRepository
     */
    public $memberSubscriptionRepository;

    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @param MemberInterface $member
     * @param array           $subscriptions
     * @return array
     */
    private function saveMemberSubscriptions(MemberInterface $member, array $subscriptions)
    {
        return array_map(
            function (string $subscription) use ($member) {
                try {
                    $subscriptionMember = $this->accessor->ensureMemberHavingIdExists(intval($subscription));
                } catch (NotFoundMemberException $exception) {
                    return new NotFoundMember();
                } catch (ProtectedAccountException $exception) {
                    return new ProtectedAccountException();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());

                    return;
                }

                $this->logger->info(sprintf(
                    'About to save subscription of member "%s" for member "%s"',
                    $member->getTwitterUsername(),
                    $subscriptionMember->getTwitterUsername()
                ));

                return $this->memberSubscriptionRepository->saveMemberSubscription($member, $subscriptionMember);
            },
            $subscriptions
        );
    }

    /**
     * @param MemberInterface $member
     * @param array           $subscribees
     * @return array
     */
    private function saveMemberSubscribees(MemberInterface $member, array $subscribees)
    {
        return array_map(
            function (string $subscribee) use ($member) {
                try {
                    $subscribeeMember = $this->accessor->ensureMemberHavingIdExists(intval($subscribee));
                } catch (NotFoundMemberException $exception) {
                    return new NotFoundMember();
                } catch (ProtectedAccountException $exception) {
                    return new ProtectedAccountException();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());

                    return;
                }

                $this->logger->info(sprintf(
                    'About to save subscribees of member "%s" for member "%s"',
                    $member->getTwitterUsername(),
                    $subscribeeMember->getTwitterUsername()
                ));

                return $this->memberSubscribeeRepository->saveMemberSubscribee($member, $subscribeeMember);
            },
            $subscribees
        );
    }

    /**
     * @param $members
     */
    public function saveNetwork($members)
    {
        array_walk(
            $members,
            function (string $member) {
                $member = $this->accessor->ensureMemberHavingNameExists($member);

                $friends = $this->accessor->showUserFriends($member->getTwitterUsername());
                if ($member instanceof MemberInterface) {
                    $this->saveMemberSubscriptions($member, $friends->ids);
                }

                $subscribees = $this->accessor->showMemberSubscribees($member->getTwitterUsername());
                if ($member instanceof MemberInterface) {
                    $this->saveMemberSubscribees($member, $subscribees->ids);
                }
            }
        );
    }
}
