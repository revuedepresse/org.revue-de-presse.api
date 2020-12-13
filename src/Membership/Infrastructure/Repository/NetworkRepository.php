<?php
declare(strict_types=1);

namespace App\Membership\Infrastructure\Repository;

use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Domain\Entity\ExceptionalMember;
use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Domain\Entity\NotFoundMember;
use App\Membership\Domain\Entity\ProtectedMember;
use App\Membership\Domain\Entity\SuspendedMember;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;

class NetworkRepository implements NetworkRepositoryInterface
{
    public MemberSubscribeeRepository $memberSubscribeeRepository;

    public MemberSubscriptionRepository $memberSubscriptionRepository;

    public MemberRepository $memberRepository;

    public EntityManager $entityManager;

    public ApiAccessorInterface $accessor;

    public LoggerInterface $logger;

    /**
     * @param MemberInterface $member
     * @param array           $subscriptions
     *
     * @return bool
     * @throws DBALException
     */
    private function saveMemberSubscriptions(MemberInterface $member, array $subscriptions)
    {
        if (count($subscriptions) > 0) {
            $subscriptions = $this->memberSubscriptionRepository->findMissingSubscriptions($member, $subscriptions);
        }

        return array_walk(
            $subscriptions,
            function (string $subscription) use ($member) {
                try {
                    $subscriptionMember = $this->ensureMemberExists($subscription);
                } catch (\Exception $exception) {
                    return;
                }

                if (!($subscriptionMember instanceof MemberInterface)) {
                    $this->logger->critical(
                        sprintf(
                            'Could not ensure a member with id "%s" exists.',
                            $subscription
                        )
                    );

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
     *
     * @return bool
     * @throws DBALException
     */
    private function saveMemberSubscribees(MemberInterface $member, array $subscribees)
    {
        if (count($subscribees) > 0) {
            $subscribees = $this->memberSubscribeeRepository->findMissingSubscribees($member, $subscribees);
        }

        return array_walk(
            $subscribees,
            function (string $subscribee) use ($member) {
                try {
                    $subscribeeMember = $this->ensureMemberExists($subscribee);
                } catch (\Exception $exception) {
                    return;
                }

                if (!($subscribeeMember instanceof MemberInterface)) {
                    $this->logger->critical(
                        sprintf(
                            'Could not ensure a member with id "%s" exists',
                            $subscribee
                        )
                    );

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
     *
     * @return MemberInterface|object|null
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     */
    public function ensureMemberExists(string $memberId): ?MemberInterface
    {
        return $this->guardAgainstExceptionalMemberWhenLookingForOne(
            function (string $memberId) {
                return $this->accessor->ensureMemberHavingIdExists($memberId);
            },
            $memberId
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

                $friends = $this->accessor->getFriendsOfMemberHavingScreenName($member->getTwitterUsername());
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

    /**
     * @param callable $doing
     * @param string   $memberId
     *
     * @return MemberInterface|null|object
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function guardAgainstExceptionalMemberWhenLookingForOne(
        callable $doing,
        string $memberId
    ) {
        try {
            $existingMember = $doing($memberId);
        } catch (NotFoundMemberException $exception) {
            $notFoundMember = new NotFoundMember();
            $this->logger->info($exception->getMessage());

            $member = $notFoundMember->make(
                is_null($exception->screenName) ? $memberId : $exception->screenName,
                (int) $memberId
            );
        } catch (ProtectedAccountException $exception) {
            $protectedMember = new ProtectedMember();
            $this->logger->info($exception->getMessage());

            $member = $protectedMember->make(
                $exception->screenName,
                (int) $memberId
            );
        } catch (SuspendedAccountException $exception) {
            $suspendedMember = new SuspendedMember();
            $this->logger->info($exception->getMessage());

            $member = $suspendedMember->make(
                $exception->screenName,
                (int) $memberId
            );
        } catch (\Exception $exception) {
            $member = new ExceptionalMember();
            $this->logger->critical($exception->getMessage());

            throw $exception;
        } finally {
            if (!isset($exception)) {
                return $existingMember;
            }

            if (!isset($exception->screenName)) {
                $this->logger->critical($exception->getMessage());

                throw $exception;
            }

            $existingMember = $this->memberRepository->findOneBy([
                'twitter_username' => $exception->screenName
            ]);
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
}
