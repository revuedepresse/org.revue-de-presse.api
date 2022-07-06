<?php

namespace App\Membership\Infrastructure\Repository;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\EditListMembersInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Infrastructure\Entity\MemberInList;
use App\Membership\Infrastructure\Entity\MemberSubscription;
use App\Subscription\Domain\Repository\ListSubscriptionRepositoryInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Subscription\Infrastructure\Entity\ListSubscription;
use App\Twitter\Infrastructure\Repository\Subscription\MemberSubscriptionRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Exception;
use Psr\Log\LoggerInterface;

class EditListMembers extends ServiceEntityRepository implements EditListMembersInterface
{
    public HttpClientInterface $httpClient;

    public ListSubscriptionRepositoryInterface $memberListSubscriptionRepository;

    public LoggerInterface $logger;

    public MemberSubscriptionRepositoryInterface $memberSubscriptionRepository;

    public NetworkRepositoryInterface $networkRepository;

    public function make(
        ListSubscription $list,
        MemberInterface  $memberInList
    ): MemberInList {
        $member = $this->findOneBy([
            'list' => $list,
            'memberInList' => $memberInList
        ]);

        if (!($member instanceof MemberInList)) {
            $member = new MemberInList($list, $memberInList);
        }

        return $this->saveMemberInList($member);
    }

    public function saveMemberInList(MemberInList $aggregateSubscription): MemberInList
    {
        $this->getEntityManager()->persist($aggregateSubscription);
        $this->getEntityManager()->flush();

        return $aggregateSubscription;
    }

    /**
     * @throws Exception
     */
    public function searchByName(string $listName): array
    {
        $listSubscription = $this->memberListSubscriptionRepository->findOneBy(
            ['listName' => $listName]
        );

        if (!($listSubscription instanceof ListSubscription)) {
            throw new Exception(
                sprintf(
                    'No member aggregate subscription could be found for name "%s"',
                    $listName
                )
            );
        }

        return $this->findBy(['listSubscription' => $listSubscription]);
    }


    /**
     * @throws \App\Twitter\Infrastructure\Exception\BadAuthenticationDataException
     * @throws \App\Twitter\Infrastructure\Exception\InconsistentTokenRepository
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\UnavailableResourceException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ApiAccessRateLimitException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\TweetNotFoundException
     * @throws \App\Twitter\Infrastructure\Http\Client\Exception\UnexpectedApiResponseException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \ReflectionException
     */
    public function addMemberToList(string $memberScreenName, string $listName): void
    {
        $member = $this->httpClient->ensureMemberHavingNameExists($memberScreenName);

        try {
            $memberInList = $this->searchByName($listName);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());

            return;
        }

        array_walk(
            $memberInList,
            function (MemberInList $memberInList) use ($member) {
                $existingMemberInList = $this->memberSubscriptionRepository->findOneBy([
                    'member' => $member,
                    'subscription' => $memberInList->memberInList
                ]);

                if ($existingMemberInList instanceof MemberSubscription) {
                    return;
                }

                $this->networkRepository->guardAgainstExceptionalMemberWhenLookingForOne(
                    function () use ($memberInList) {
                        $this->httpClient->followMember($memberInList);
                    },
                    $memberInList->memberInList->twitterId()
                );

                $this->memberSubscriptionRepository->saveMemberSubscription(
                    $member,
                    $memberInList->memberInList
                );
            }
        );
    }
}
