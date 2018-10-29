<?php

namespace App\Member\Repository;

use App\Member\Entity\ExceptionalMember;
use App\Member\Entity\NotFoundMember;
use App\Member\Entity\ProtectedMember;
use App\Member\Entity\SuspendedMember;
use App\Member\MemberInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\NotFoundMemberException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\ProtectedAccountException;
use WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException;
use WTW\UserBundle\Repository\UserRepository;

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
     * @var UserRepository
     */
    public $memberRepository;

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
                $subscriptionMember = $this->ensureMemberExists($subscription);

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
                $subscribeeMember = $this->ensureMemberExists($subscribee);

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
     * @param string $subscription
     * @return ExceptionalMember|MemberInterface|null|object
     */
    public function ensureMemberExists(string $subscription)
    {
        try {
            $subscriptionMember = $this->accessor->ensureMemberHavingIdExists(intval($subscription));
        } catch (NotFoundMemberException $exception) {
            $notFoundMember = new NotFoundMember();
            $this->logger->info($exception->getMessage());

            $subscriptionMember = $notFoundMember->make($exception->screenName);
        } catch (ProtectedAccountException $exception) {
            $protectedMember = new ProtectedMember();
            $this->logger->info($exception->getMessage());

            $subscriptionMember = $protectedMember->make($exception->screenName);
        } catch (SuspendedAccountException $exception) {
            $suspendedMember = new SuspendedMember();
            $this->logger->info($exception->getMessage());

            $subscriptionMember = $suspendedMember->make($exception->screenName);
        } catch (\Exception $exception) {
            $subscriptionMember = new ExceptionalMember();
            $this->logger->critical($exception->getMessage());
        } finally {
            if (isset($exception)) {
                $existingMember = $this->memberRepository->findOneBy(['twitter_username' => $subscription]);
                if (!($existingMember instanceof MemberInterface)) {
                    $this->memberRepository->saveMember($subscriptionMember);
                }
            }
        }

        return $subscriptionMember;
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
