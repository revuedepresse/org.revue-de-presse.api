<?php

namespace App\Aggregate\Command;

use App\Aggregate\Repository\MemberAggregateSubscriptionRepository;
use App\Console\AbstractCommand;
use App\Member\Entity\AggregateSubscription;
use App\Member\MemberInterface;
use App\Member\Repository\AggregateSubscriptionRepository;
use App\Member\Repository\NetworkRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WTW\UserBundle\Repository\UserRepository;

class ImportMemberAggregatesCommand extends AbstractCommand
{
    const OPTION_MEMBER_NAME = 'member-name';

    const OPTION_FIND_OWNERSHIP = 'find-ownerships';

    const OPTION_LIST_RESTRICTION = 'list-restriction';

    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var string
     */
    public $listRestriction;

    /**
     * @var AggregateSubscriptionRepository
     */
    public $aggregateSubscriptionRepository;

    /**
     * @var MemberAggregateSubscriptionRepository
     */
    public $memberAggregateSubscriptionRepository;

    /**
     * @var NetworkRepository
     */
    public $networkRepository;

    /**
     * @var UserRepository
     */
    public $memberRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    public function configure()
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
     * @return int|null|void
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $member = $this->accessor->ensureMemberHavingNameExists($memberName);

        $listSubscriptions = $this->findListSubscriptions($memberName);

        array_walk(
            $listSubscriptions->lists,
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

                if (!is_null($this->listRestriction) && ($list->name !== $this->listRestriction)) {
                    return;
                }

                $list = $this->accessor->getListMembers($memberAggregateSubscription->listId);

                $ids = array_map(
                    function (\stdClass $user) {
                        return $user->id_str;
                    },
                    $list->users
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

                array_walk(
                    $list->users,
                    function (
                        \stdClass $user
                    ) use (
                        $memberAggregateSubscription,
                        $membersIndexedByTwitterId,
                        $indexedMemberAggregateSubscriptions
                    ) {
                        $member = $this->getMemberByTwitterId($user, $membersIndexedByTwitterId);

                        $index = sprintf('%s-%d', $memberAggregateSubscription->getId(), $member->getId());
                        if (!array_key_exists($index, $indexedMemberAggregateSubscriptions)) {
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
    }

    /**
     * @param \stdClass $user
     * @param           $membersIndexedByTwitterId
     * @return \App\Member\Entity\ExceptionalMember|MemberInterface|null|object
     */
    public function getMemberByTwitterId(\stdClass $user, $membersIndexedByTwitterId)
    {
        if (!array_key_exists($user->id_str, $membersIndexedByTwitterId)) {
            return $this->networkRepository->ensureMemberExists($user->id);
        }

        return $membersIndexedByTwitterId[$user->id_str];
    }

    /**
     * @param $memberName
     * @return \API|mixed|object|\stdClass
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function findListSubscriptions($memberName)
    {
        if ($this->input->hasOption(self::OPTION_FIND_OWNERSHIP) &&
            $this->input->getOption(self::OPTION_FIND_OWNERSHIP)
        ) {
            return $this->accessor->getUserOwnerships($memberName);
        }

        return $this->accessor->getUserListSubscriptions($memberName);
    }
}
