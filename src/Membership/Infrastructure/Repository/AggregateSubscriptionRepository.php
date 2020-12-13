<?php

namespace App\Membership\Infrastructure\Repository;

use App\PublishersList\Entity\MemberAggregateSubscription;
use App\PublishersList\Repository\MemberAggregateSubscriptionRepository;
use App\Twitter\Infrastructure\Api\Entity\Aggregate;
use App\Membership\Domain\Entity\AggregateSubscription;
use App\Membership\Domain\Entity\MemberSubscription;
use App\Membership\Domain\Entity\MemberInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;
use App\Twitter\Infrastructure\Api\Accessor;

class AggregateSubscriptionRepository extends ServiceEntityRepository
{
    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var MemberAggregateSubscriptionRepository
     */
    public $memberAggregateSubscriptionRepository;

    /**
     * @var MemberSubscriptionRepository
     */
    public $memberSubscriptionRepository;

    /**
     * @var NetworkRepository
     */
    public $networkRepository;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param string          $className
     */
    public function __construct(
        ManagerRegistry $managerRegistry,
        string $className
    )
    {
        parent::__construct($managerRegistry, $className);
    }

    /**
     * @param MemberAggregateSubscription $memberAggregateSubscription
     * @param MemberInterface             $subscription
     * @return AggregateSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function make(
        MemberAggregateSubscription $memberAggregateSubscription,
        MemberInterface $subscription
    ) {
        $aggregateSubscription = $this->findOneBy([
            'memberAggregateSubscription' => $memberAggregateSubscription,
            'subscription' => $subscription
        ]);

        if (!($aggregateSubscription instanceof AggregateSubscription)) {
            $aggregateSubscription = new AggregateSubscription($memberAggregateSubscription, $subscription);
        }

        return $this->saveAggregateSubscription($aggregateSubscription);
    }

    /**
     * @param AggregateSubscription $aggregateSubscription
     * @return AggregateSubscription
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function saveAggregateSubscription(AggregateSubscription $aggregateSubscription)
    {
        $this->getEntityManager()->persist($aggregateSubscription);
        $this->getEntityManager()->flush();

        return $aggregateSubscription;
    }

    /**
     * @param string $aggregateName
     * @return array
     * @throws \Exception
     */
    public function findSubscriptionsByAggregateName(string $aggregateName)
    {
        $memberAggregateSubscription = $this->memberAggregateSubscriptionRepository
            ->findOneBy(['listName' => $aggregateName])
        ;

        if (!($memberAggregateSubscription instanceof MemberAggregateSubscription)) {
            throw new \Exception(
                sprintf(
                    'No member aggregate subscription could be found for name "%s"',
                    $aggregateName
                )
            );
        }

        return $this->findBy(['memberAggregateSubscription' => $memberAggregateSubscription]);
    }


    /**
     * @param string $memberName
     * @param string $aggregateName
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function letMemberSubscribeToAggregate(string $memberName, string $aggregateName): void
    {
        $member = $this->accessor->ensureMemberHavingNameExists($memberName);

        try {
            $subscriptions = $this->findSubscriptionsByAggregateName($aggregateName);
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());

            return;
        }

        array_walk(
            $subscriptions,
            function (AggregateSubscription $subscription) use ($member) {
                $existingSubscription = $this->memberSubscriptionRepository->findOneBy([
                    'member' => $member,
                    'subscription' => $subscription->subscription
                ]);

                if ($existingSubscription instanceof MemberSubscription) {
                    return;
                }

                $this->networkRepository->guardAgainstExceptionalMemberWhenLookingForOne(
                    function () use ($subscription) {
                        $this->accessor->subscribeToMemberTimeline($subscription);
                    },
                    $subscription->subscription->getTwitterID()
                );

                $this->memberSubscriptionRepository->saveMemberSubscription(
                    $member,
                    $subscription->subscription
                );
            }
        );
    }
}
