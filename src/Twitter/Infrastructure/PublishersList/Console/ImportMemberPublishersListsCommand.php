<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\PublishersList\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberPublishersListSubscriptionRepositoryInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Domain\Repository\PublishersListSubscriptionRepositoryInterface;
use App\Membership\Infrastructure\Entity\AggregateSubscription;
use App\Membership\Infrastructure\Repository\AggregateSubscriptionRepository;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use App\Twitter\Domain\Api\Accessor\ApiAccessorInterface;
use App\Twitter\Domain\Api\Accessor\OwnershipAccessorInterface;
use App\Twitter\Domain\Membership\Repository\MemberRepositoryInterface;
use App\Twitter\Domain\Resource\MemberIdentity;
use App\Twitter\Domain\Resource\PublishersList;
use App\Twitter\Infrastructure\Api\Selector\MemberOwnershipsBatchSelector;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\DependencyInjection\Collection\OwnershipBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Collection\PublishersListCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Membership\Repository\MemberRepository;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use App\Twitter\Infrastructure\PublishersList\Repository\MemberAggregateSubscriptionRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportMemberPublishersListsCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'app:import-publishers-lists';

    use OwnershipBatchCollectedEventRepositoryTrait;
    use PublishersListCollectedEventRepositoryTrait;

    private const ARGUMENT_SCREEN_NAME = 'screen_name';

    private const OPTION_LIST_RESTRICTION = 'list-restriction';

    private ApiAccessorInterface $accessor;

    private OwnershipAccessorInterface $ownershipAccessor;

    public ?string $listRestriction = null;

    private AggregateSubscriptionRepository $publishersListSubscriptionRepository;

    private MemberAggregateSubscriptionRepository $memberPublishersListSubscriptionRepository;

    private NetworkRepository $networkRepository;

    private MemberRepository $memberRepository;

    private LoggerInterface $logger;

    public function __construct(
        string $name,
        ApiAccessorInterface $accessor,
        OwnershipAccessorInterface $ownershipAccessor,
        PublishersListSubscriptionRepositoryInterface $publishersListSubscriptionRepository,
        MemberPublishersListSubscriptionRepositoryInterface  $memberPublishersListSubscriptionRepository,
        NetworkRepositoryInterface $networkRepository,
        MemberRepositoryInterface $memberRepository,
        LoggerInterface $logger
    ) {
        $this->accessor = $accessor;
        $this->ownershipAccessor = $ownershipAccessor;

        $this->memberRepository = $memberRepository;
        $this->memberPublishersListSubscriptionRepository = $memberPublishersListSubscriptionRepository;
        $this->networkRepository = $networkRepository;
        $this->publishersListSubscriptionRepository = $publishersListSubscriptionRepository;

        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
             ->addArgument(
                 self::ARGUMENT_SCREEN_NAME,
                 InputArgument::REQUIRED,
                 'The screen name of a Twitter member'
             )
             ->addOption(
                 self::OPTION_LIST_RESTRICTION,
                 'r',
                 InputOption::VALUE_OPTIONAL,
                 'Restrict list import to single list'
             )
             ->setDescription('Import Twitter list');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $memberName = $this->input->getArgument(self::ARGUMENT_SCREEN_NAME);
        $member     = $this->accessor->ensureMemberHavingNameExists($memberName);

        $correlationId = CorrelationId::generate();

        $nextPage = -1;

        do {
            $eventRepository   = $this->ownershipBatchCollectedEventRepository;
            $listSubscriptions = $eventRepository->collectedOwnershipBatch(
                $this->ownershipAccessor,
                new MemberOwnershipsBatchSelector(
                    $member->twitterScreenName(),
                    (string) $nextPage,
                    $correlationId
                )
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

        return self::SUCCESS;
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
                $memberAggregateSubscription = $this->memberPublishersListSubscriptionRepository
                    ->make(
                        $member,
                        $list->toArray()
                    );

                $this->output->writeln(sprintf(
                    'About to collect members of Twitter list "%s"',
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
                        $eventRepository::OPTION_PUBLISHERS_LIST_ID => $memberAggregateSubscription->listId(),
                        $eventRepository::OPTION_PUBLISHERS_LIST_NAME => $memberAggregateSubscription->listName()
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
                        $membersIndexedByTwitterId[$member->twitterId()] = $member;
                    }
                );

                $memberAggregateSubscriptions = [];

                try {
                    $memberAggregateSubscriptions = $this->publishersListSubscriptionRepository
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
                            $this->publishersListSubscriptionRepository->make($memberAggregateSubscription, $member);
                        }
                    }
                );
            }
        );
    }
}
