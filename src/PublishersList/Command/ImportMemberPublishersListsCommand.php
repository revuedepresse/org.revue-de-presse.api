<?php
declare(strict_types=1);

namespace App\PublishersList\Command;

use App\PublishersList\Repository\MemberAggregateSubscriptionRepository;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;
use App\Twitter\Infrastructure\DependencyInjection\Collection\OwnershipBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\PublishersListCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;
use App\Membership\Domain\Entity\AggregateSubscription;
use App\Membership\Infrastructure\Repository\AggregateSubscriptionRepository;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use App\Membership\Domain\Entity\MemberInterface;
use App\Twitter\Domain\Api\ApiAccessorInterface;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportMemberPublishersListsCommand extends AbstractCommand
{
    use OwnershipBatchCollectedEventRepositoryTrait;
    use PublishersListCollectedEventRepositoryTrait;

    private const OPTION_MEMBER_NAME = 'member-name';

    private const OPTION_LIST_RESTRICTION = 'list-restriction';

    public ApiAccessorInterface $accessor;

    public ?string $listRestriction = null;

    public AggregateSubscriptionRepository $aggregateSubscriptionRepository;

    public MemberAggregateSubscriptionRepository $memberAggregateSubscriptionRepository;

    public NetworkRepository $networkRepository;

    public MemberRepository $memberRepository;

    public LoggerInterface $logger;

    protected function configure(): void
    {
        $this->setName('import-aggregates')
             ->addOption(
                 self::OPTION_MEMBER_NAME,
                 'm',
                 InputOption::VALUE_REQUIRED,
                 'The name of a member'
             )
             ->addOption(
                 self::OPTION_LIST_RESTRICTION,
                 'lr',
                 InputOption::VALUE_OPTIONAL
             )
             ->setDescription('Import publishers list');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $member     = $this->accessor->ensureMemberHavingNameExists($memberName);

        $nextPage = -1;

        do {
            $eventRepository   = $this->ownershipBatchCollectedEventRepository;
            $listSubscriptions = $eventRepository->collectedOwnershipBatch(
                $this->accessor,
                [
                    $eventRepository::OPTION_SCREEN_NAME => $member->getTwitterUsername(),
                    $eventRepository::OPTION_NEXT_PAGE   => $nextPage
                ]
            );

            $this->traverseSubscriptions($listSubscriptions->toArray(), $member);

            $nextPage = $listSubscriptions->nextPage();
        } while ($listSubscriptions->isNotEmpty());

        $this->output->writeln(
            sprintf(
                'All list subscriptions have be saved for member with name "%s"',
                $memberName
            )
        );

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @param MemberIdentity $memberIdentity
     * @param array          $membersIndexedByTwitterId
     *
     * @return MemberInterface|object|null
     * @throws NotFoundMemberException
     * @throws ORMException
     * @throws OptimisticLockException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     */
    public function getMemberByTwitterId(
        MemberIdentity $memberIdentity,
        array $membersIndexedByTwitterId
    ): MemberInterface {
        if (!\array_key_exists($memberIdentity->id(), $membersIndexedByTwitterId)) {
            return $this->networkRepository->ensureMemberExists($memberIdentity->id());
        }

        return $membersIndexedByTwitterId[$memberIdentity->id()];
    }

    private function traverseSubscriptions(array $subscriptions, MemberInterface $member): void
    {
        array_walk(
            $subscriptions,
            function (PublishersList $list) use ($member) {
                $memberAggregateSubscription = $this->memberAggregateSubscriptionRepository
                    ->make(
                        $member,
                        $list->toArray()
                    );

                $this->output->writeln(sprintf(
                    'About to collect members of publishers list "%s"',
                    $memberAggregateSubscription->listName()
                ));

                if (
                    $this->input->hasOption(self::OPTION_LIST_RESTRICTION)
                    && $this->input->getOption(
                        self::OPTION_LIST_RESTRICTION
                    )
                ) {
                    $this->listRestriction = $this->input->getOption(self::OPTION_LIST_RESTRICTION);
                }

                if ($this->listRestriction !== null && ($list->name() !== $this->listRestriction)) {
                    return;
                }

                $eventRepository = $this->publishersListCollectedEventRepository;
                $memberPublishersList = $eventRepository->collectedPublishersList(
                    $this->accessor,
                    [
                        $eventRepository::OPTION_publishers_list_ID => $memberAggregateSubscription->listId(),
                        $eventRepository::OPTION_publishers_list_NAME => $memberAggregateSubscription->listName()
                    ]
                );

                $ids = array_map(
                    function (MemberIdentity $memberIdentity) {
                        return $memberIdentity->id();
                    },
                    $memberPublishersList->toArray()
                );

                $members = $this->memberRepository
                    ->createQueryBuilder('m')
                    ->andWhere('m.twitterID in (:ids)')
                    ->setParameter('ids', $ids)
                    ->getQuery()
                    ->getResult();

                $membersIndexedByTwitterId = [];
                array_walk(
                    $members,
                    function (MemberInterface $member) use (&$membersIndexedByTwitterId) {
                        $membersIndexedByTwitterId[$member->getTwitterID()] = $member;
                    }
                );

                $memberAggregateSubscriptions = [];

                try {
                    $memberAggregateSubscriptions = $this->aggregateSubscriptionRepository
                        ->createQueryBuilder('aggs')
                        ->andWhere(
                            'aggs.memberAggregateSubscription = :member_aggregate_subscription'
                        )
                        ->setParameter(
                            'member_aggregate_subscription',
                            $memberAggregateSubscription
                        )
                        ->andWhere(
                            'aggs.subscription in (:members)'
                        )
                        ->setParameter(
                            'members',
                            $members
                        )
                        ->getQuery()
                        ->getResult();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }

                $indexedMemberAggregateSubscriptions = [];
                $indexedMemberAggregateSubscriptions = array_reduce(
                    $memberAggregateSubscriptions,
                    function (
                        $indexedMemberAggregateSubscriptions,
                        AggregateSubscription $aggregateSubscription)
                    {
                        $index = sprintf(
                            '%s-%d',
                            $aggregateSubscription
                                ->getMemberAggregateSubscription()
                                ->getId(),
                            $aggregateSubscription
                                ->subscription
                                ->getId()
                        );
                        $indexedMemberAggregateSubscriptions[$index] = $aggregateSubscription;

                        return $indexedMemberAggregateSubscriptions;
                    },
                    $indexedMemberAggregateSubscriptions
                );

                $publishersLists = $memberPublishersList->toArray();
                array_walk(
                    $publishersLists,
                    function (
                        MemberIdentity $memberIdentity
                    ) use (
                        $memberAggregateSubscription,
                        $membersIndexedByTwitterId,
                        $indexedMemberAggregateSubscriptions
                    ) {
                        $member = $this->getMemberByTwitterId(
                            $memberIdentity,
                            $membersIndexedByTwitterId
                        );

                        if (!($member instanceof MemberInterface)) {
                            return;
                        }

                        $index = sprintf('%s-%d', $memberAggregateSubscription->getId(), $member->getId());
                        if (!\array_key_exists($index, $indexedMemberAggregateSubscriptions)) {
                            $this->aggregateSubscriptionRepository->make($memberAggregateSubscription, $member);
                        }
                    }
                );
            }
        );
    }
}
