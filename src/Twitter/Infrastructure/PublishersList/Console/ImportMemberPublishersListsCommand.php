<?php

declare(strict_types=1);

namespace App\Twitter\Infrastructure\PublishersList\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\EditListMembersInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Domain\Repository\NetworkRepositoryInterface;
use App\Membership\Infrastructure\Entity\MemberInList;
use App\Membership\Infrastructure\Repository\EditListMembers;
use App\Membership\Infrastructure\Repository\MemberRepository;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use App\Subscription\Domain\Repository\ListSubscriptionRepositoryInterface;
use App\Twitter\Domain\Http\Client\HttpClientInterface;
use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\ListBatchCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\DependencyInjection\Curation\Events\PublishersListCollectedEventRepositoryTrait;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Http\Resource\MemberIdentity;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use App\Twitter\Infrastructure\Http\Selector\ListsBatchSelector;
use App\Twitter\Infrastructure\Operation\Correlation\CorrelationId;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ImportMemberPublishersListsCommand extends AbstractCommand
{
    use ListBatchCollectedEventRepositoryTrait;
    use PublishersListCollectedEventRepositoryTrait;

    private HttpClientInterface $httpClient;

    private ListAwareHttpClientInterface $listAwareHttpClient;

    private EditListMembers $listRepository;

    private LoggerInterface $logger;

    private ListSubscriptionRepositoryInterface $listSubscriptionRepository;

    private MemberRepository $memberRepository;

    private NetworkRepository $networkRepository;

    private const ARGUMENT_SCREEN_NAME = 'screen-name';

    private const OPTION_SINGLE_LIST_FILTER = 'single-list-filter';

    public const COMMAND_NAME = 'app:synchronize-member-lists';

    public ?string $singleListFilter = null;

    public function __construct(
        string                              $name,
        HttpClientInterface                 $httpClient,
        ListAwareHttpClientInterface        $listAwareHttpClient,
        EditListMembersInterface            $publishersListSubscriptionRepository,
        ListSubscriptionRepositoryInterface $memberPublishersListSubscriptionRepository,
        NetworkRepositoryInterface          $networkRepository,
        MemberRepositoryInterface           $memberRepository,
        LoggerInterface                     $logger
    ) {
        $this->httpClient = $httpClient;
        $this->listAwareHttpClient = $listAwareHttpClient;

        $this->memberRepository = $memberRepository;
        $this->listSubscriptionRepository = $memberPublishersListSubscriptionRepository;
        $this->networkRepository = $networkRepository;
        $this->listRepository = $publishersListSubscriptionRepository;

        $this->logger = $logger;

        parent::__construct($name);
    }

    public function mayApplySingleListFilter(): void
    {
        try {
            $this->singleListFilter = $this->input->getOption(self::OPTION_SINGLE_LIST_FILTER);
        } catch (InvalidArgumentException) {
            // do nothing
        }
    }

    function isListPreservedAfterApplyingFilteringByListName(PublishersList $list): bool
    {
        if (!$this->isSingleListFilterActive()) {
            return false;
        }

        $isListPreserved = $list->name() === $this->singleListFilter;

        if ($isListPreserved) {
            $this->logger->info('filtering by list name', ['list_name' => $list->name()]);

            $isListPreserved = true;
        }

        return $isListPreserved;
    }

    /**
     * @return bool
     */
    function isSingleListFilterActive(): bool
    {
        return $this->singleListFilter !== null;
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
                self::OPTION_SINGLE_LIST_FILTER,
                'r',
                InputOption::VALUE_OPTIONAL,
                'Restrict list import to single list'
            )
            ->setDescription('Import Twitter list');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $correlationId = CorrelationId::generate();

        parent::execute($input, $output);

        $this->mayApplySingleListFilter();

        $memberName = $this->input->getArgument(self::ARGUMENT_SCREEN_NAME);

        $member     = $this->httpClient->ensureMemberHavingNameExists($memberName);

        $nextPage = -1;

        do {
            $listsBatch = $this->listsBatchCollectedEventRepository->collectedListsBatch(
                $this->listAwareHttpClient,
                new ListsBatchSelector(
                    $member->twitterScreenName(),
                    (string) $nextPage,
                    $correlationId
                )
            );

            try {
                $this->traverseListsBatch($listsBatch->toArray(), $member);
            } catch (\Exception $e) {
                $this->logger->error(
                    $e->getMessage(),
                    ['exception' => $e->getTrace()]
                );

                $this->output->writeln(
                    sprintf(
                        'Could not save all lists for member having screen name "%s"',
                        $memberName
                    )
                );

                return self::FAILURE;
            }

            $nextPage = $listsBatch->nextPage();
        } while ($listsBatch->isNotEmpty());

        $this->output->writeln(
            sprintf(
                'All lists have be saved for member having screen name "%s"',
                $memberName
            )
        );

        return self::SUCCESS;
    }

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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

    /**
     * @throws \App\Twitter\Infrastructure\Exception\NotFoundMemberException
     * @throws \App\Twitter\Infrastructure\Exception\ProtectedAccountException
     * @throws \App\Twitter\Infrastructure\Exception\SuspendedAccountException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function traverseListsBatch(array $listsBatch, MemberInterface $member): void
    {
        $listsBatch = array_filter(
            $listsBatch,
            fn ($list) => $this->isListPreservedAfterApplyingFilteringByListName($list)
        );

        array_walk(
            $listsBatch,
            function (PublishersList $list) use ($member) {
                $listSubscription = $this->listSubscriptionRepository
                    ->make($member, $list->toArray());

                $this->output->writeln(sprintf(
                    'About to collect members of Twitter list "%s"',
                    $listSubscription->listName()
                ));

                $eventRepository = $this->publishersListCollectedEventRepository;
                $memberPublishersList = $eventRepository->collectedListOwnedByMember(
                    $this->httpClient,
                    [
                        $eventRepository::OPTION_PUBLISHERS_LIST_ID => $listSubscription->listId(),
                        $eventRepository::OPTION_PUBLISHERS_LIST_NAME => $listSubscription->listName()
                    ]
                );

                $ids = array_map(
                    fn (MemberIdentity $memberIdentity) => $memberIdentity->id(),
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
                        $this->output->writeln(
                            sprintf(
                                'Adding member to indexed members list (member has handle: "%s")',
                                $member->twitterScreenName()
                            )
                        );

                        $membersIndexedByTwitterId[$member->twitterId()] = $member;
                    }
                );

                $listsOwnedByMember = [];

                try {
                    $listsOwnedByMember = $this->listRepository
                        ->createQueryBuilder('member_in_list')
                        ->andWhere('member_in_list.list = :subscription')
                        ->setParameter('subscription', $listSubscription)
                        ->andWhere('member_in_list.memberInList in (:membersAddedToList)')
                        ->setParameter('membersAddedToList', $members)
                        ->getQuery()
                        ->getResult();
                } catch (\Exception $exception) {
                    $this->logger->critical(
                        sprintf(
                            'Could not process list %s',
                            $listSubscription->listName()
                        ),
                        [
                            'exception' => $exception->getMessage(),
                            'trace' => $exception->getTrace()
                        ]
                    );
                }

                $indexedListSubscriptions = [];
                $indexedListSubscriptions = array_reduce(
                    $listsOwnedByMember,
                    function (
                        array             $indexedListSubscriptions,
                        MemberInList $listOwnedByMember
                    ) {
                        $index = sprintf(
                            '%s-%d',
                            $listOwnedByMember->getList()->getId(),
                            $listOwnedByMember->memberInList->getId()
                        );

                        $indexedListSubscriptions[$index] = $listOwnedByMember;

                        return $indexedListSubscriptions;
                    },
                    $indexedListSubscriptions
                );

                $publishersLists = $memberPublishersList->toArray();

                array_walk(
                    $publishersLists,
                    function (
                        MemberIdentity $memberIdentity
                    ) use (
                        $listSubscription,
                        $membersIndexedByTwitterId,
                        $indexedListSubscriptions
                    ) {
                        $member = $this->getMemberByTwitterId(
                            $memberIdentity,
                            $membersIndexedByTwitterId
                        );

                        $index = sprintf('%s-%d', $listSubscription->getId(), $member->getId());
                        if (!\array_key_exists($index, $indexedListSubscriptions)) {
                            $this->listRepository->make($listSubscription, $member);
                        }
                    }
                );
            }
        );
    }
}
