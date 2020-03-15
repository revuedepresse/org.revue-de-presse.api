<?php
declare(strict_types=1);

namespace App\Aggregate\Command;

use App\Aggregate\Repository\MemberAggregateSubscriptionRepository;
use App\Console\AbstractCommand;
use App\Infrastructure\Repository\Membership\MemberRepository;
use App\Member\Entity\AggregateSubscription;
use App\Member\Repository\AggregateSubscriptionRepository;
use App\Member\Repository\NetworkRepository;
use App\Membership\Entity\MemberInterface;
use App\Twitter\Api\ApiAccessorInterface;
use App\Twitter\Exception\NotFoundMemberException;
use App\Twitter\Exception\ProtectedAccountException;
use App\Twitter\Exception\SuspendedAccountException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportMemberPublicationListsCommand extends AbstractCommand
{
    private const OPTION_MEMBER_NAME = 'member-name';

    private const OPTION_FIND_OWNERSHIP = 'find-ownerships';

    private const OPTION_LIST_RESTRICTION = 'list-restriction';

    public ApiAccessorInterface $accessor;

    public string $listRestriction;

    public AggregateSubscriptionRepository $aggregateSubscriptionRepository;

    public MemberAggregateSubscriptionRepository $memberAggregateSubscriptionRepository;

    public NetworkRepository $networkRepository;

    public MemberRepository $memberRepository;

    public LoggerInterface $logger;

    public function configure(): void
    {
        $this->setName('import-aggregates')
            ->addOption(
                self::OPTION_MEMBER_NAME,
                'm',
                InputOption::VALUE_REQUIRED,
                'The name of a member'
            )
            ->addOption(
                self::OPTION_FIND_OWNERSHIP,
                'o',
                InputOption::VALUE_NONE
            )
            ->addOption(
                self::OPTION_LIST_RESTRICTION,
                'lr',
                InputOption::VALUE_OPTIONAL
            )
            ->setDescription('Import lists of a member as aggregates');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $member = $this->accessor->ensureMemberHavingNameExists($memberName);

        $listSubscriptions = $this->findListSubscriptions($memberName);
        $subscriptions = $listSubscriptions->toArray();

        array_walk(
            $subscriptions,
            function (\stdClass $list) use ($member) {
                $memberAggregateSubscription = $this->memberAggregateSubscriptionRepository
                    ->make(
                        $member,
                        (array) $list
                    );

                if ($this->input->hasOption(self::OPTION_LIST_RESTRICTION) &&
                    $this->input->getOption(self::OPTION_LIST_RESTRICTION)) {
                    $this->listRestriction = $this->input->getOption(self::OPTION_LIST_RESTRICTION);
                }

                if ($this->listRestriction !== null && ($list->name !== $this->listRestriction)) {
                    return;
                }

                $memberPublicationList = $this->accessor->getListMembers(
                    (string) $memberAggregateSubscription->listId
                );

                $ids = array_map(
                    function (\stdClass $user) {
                        return $user->id_str;
                    },
                    $memberPublicationList->toArray()
                );

                $members = $this->memberRepository->createQueryBuilder('m')
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

                try {
                    $memberAggregateSubscriptions = $this->aggregateSubscriptionRepository->createQueryBuilder('aggs')
                        ->andWhere('aggs.memberAggregateSubscription = :member_aggregate_subscription')
                        ->setParameter('member_aggregate_subscription', $memberAggregateSubscription)
                        ->andWhere('aggs.subscription in (:members)')
                        ->setParameter('members', $members)
                        ->getQuery()
                        ->getResult();
                } catch (\Exception $exception) {
                    $this->logger->critical($exception->getMessage());
                }

                $indexedMemberAggregateSubscriptions = [];
                array_walk(
                    $memberAggregateSubscriptions,
                    function (AggregateSubscription $aggregateSubscription) use (&$indexedMemberAggregateSubscriptions) {
                        $index = sprintf('%s-%d',
                            $aggregateSubscription
                                ->getMemberAggregateSubscription()
                                ->getId(),
                            $aggregateSubscription
                                ->subscription
                                ->getId()
                        );
                        $indexedMemberAggregateSubscriptions[$index] = $aggregateSubscription;
                    }
                );

                $publicationLists = $memberPublicationList->toArray();
                array_walk(
                    $publicationLists,
                    function (
                        \stdClass $user
                    ) use (
                        $memberAggregateSubscription,
                        $membersIndexedByTwitterId,
                        $indexedMemberAggregateSubscriptions
                    ) {
                        $member = $this->getMemberByTwitterId($user, $membersIndexedByTwitterId);

                        $index = sprintf('%s-%d', $memberAggregateSubscription->getId(), $member->getId());
                        if (!\array_key_exists($index, $indexedMemberAggregateSubscriptions)) {
                            $this->aggregateSubscriptionRepository->make($memberAggregateSubscription, $member);
                        }
                    }
                );
            }
        );

        $this->output->writeln(sprintf(
            'All list subscriptions have be saved for member with name "%s"',
            $memberName
        ));

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @param \stdClass $user
     * @param           $membersIndexedByTwitterId
     *
     * @return MemberInterface|object|null
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function getMemberByTwitterId(\stdClass $user, $membersIndexedByTwitterId)
    {
        if (!\array_key_exists($user->id_str, $membersIndexedByTwitterId)) {
            return $this->networkRepository->ensureMemberExists($user->id);
        }

        return $membersIndexedByTwitterId[$user->id_str];
    }

    /**
     * @param $memberName
     *
     * @return mixed
     */
    private function findListSubscriptions($memberName)
    {
        if ($this->input->hasOption(self::OPTION_FIND_OWNERSHIP) &&
            $this->input->getOption(self::OPTION_FIND_OWNERSHIP)
        ) {
            return $this->accessor->getMemberOwnerships($memberName);
        }

        return $this->accessor->getMemberPublicationListSubscriptions($memberName);
    }
}
