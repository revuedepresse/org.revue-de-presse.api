<?php

namespace App\Member\Repository;

use App\Member\Entity\ExceptionalMember;
use App\Member\Entity\NotFoundMember;
use App\Member\Entity\ProtectedMember;
use App\Member\Entity\SuspendedMember;
use App\Member\MemberInterface;
use Doctrine\ORM\EntityManager;
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
     * @var EntityManager
     */
    public $entityManager;

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
        $subscriptions = $this->memberSubscriptionRepository->findMissingSubscriptions($member, $subscriptions);

        return array_walk(
            $subscriptions,
            function (string $subscription) use ($member) {
                try {
                    $subscriptionMember = $this->ensureMemberExists($subscription);
                } catch (\Exception $exception) {
                    return;
                }

                $this->logger->info(sprintf(
                    'About to save subscription of member "%s" for member "%s"',
                    $member->getTwitterUsername(),
                    $subscriptionMember->getTwitterUsername()
                ));

                $memberSubscription = $this->memberSubscriptionRepository->saveMemberSubscription(
                    $member,
                    $subscriptionMember
                );
                $this->entityManager->detach($memberSubscription);
            }
        );
    }

    /**
     * @param MemberInterface $member
     * @param array           $subscribees
     * @return array
     */
    private function saveMemberSubscribees(MemberInterface $member, array $subscribees)
    {
        $subscribees = $this->memberSubscribeeRepository->findMissingSubscribees($member, $subscribees);

        return array_walk(
            $subscribees,
            function (string $subscribee) use ($member) {
                try {
                    $subscribeeMember = $this->ensureMemberExists($subscribee);
                } catch (\Exception $exception) {
                    return;
                }

                $this->logger->info(sprintf(
                    'About to save subscribees of member "%s" for member "%s"',
                    $member->getTwitterUsername(),
                    $subscribeeMember->getTwitterUsername()
                ));

                $memberSubscribee = $this->memberSubscribeeRepository->saveMemberSubscribee(
                    $member,
                    $subscribeeMember
                );
                $this->entityManager->detach($memberSubscribee);
            }
        );
    }

    /**
     * @param string $memberId
     * @return ExceptionalMember|MemberInterface|null|object
     */
    public function ensureMemberExists(string $memberId)
    {
        try {
            $member = $this->accessor->ensureMemberHavingIdExists(intval($memberId));
        } catch (NotFoundMemberException $exception) {
            $notFoundMember = new NotFoundMember();
            $this->logger->info($exception->getMessage());

            $member = $notFoundMember->make(
                is_null($exception->screenName) ? $memberId : $exception->screenName,
                intval($memberId)
            );
        } catch (ProtectedAccountException $exception) {
            $protectedMember = new ProtectedMember();
            $this->logger->info($exception->getMessage());

            $member = $protectedMember->make(
                $exception->screenName,
                intval($memberId)
            );
        } catch (SuspendedAccountException $exception) {
            $suspendedMember = new SuspendedMember();
            $this->logger->info($exception->getMessage());

            $member = $suspendedMember->make(
                $exception->screenName,
                intval($memberId)
            );
        } catch (\Exception $exception) {
            $member = new ExceptionalMember();
            $this->logger->critical($exception->getMessage());

            throw $exception;
        } finally {
            if (isset($exception)) {
                $existingMember = $this->memberRepository->findOneBy(['twitter_username' => $exception->screenName]);
                if (!$existingMember instanceof MemberInterface) {
                    $existingMember = $this->memberRepository->findOneBy(['twitterID' => $memberId]);
                }

                if ($existingMember instanceof MemberInterface) {
                    if ($existingMember->getTwitterID() !== $member->getTwitterID() &&
                    $member->getTwitterID() !== null) {
                        $existingMember->setTwitterID($member->getTwitterID());

                        return $this->memberRepository->saveMember($existingMember);
                    }


                    return $existingMember;
                }

                return $this->memberRepository->saveMember($member);
            }
        }

        return $member;
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
