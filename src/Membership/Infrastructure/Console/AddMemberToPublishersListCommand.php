<?php

namespace App\Membership\Infrastructure\Console;

use App\Membership\Domain\Model\MemberInterface;
use App\Membership\Domain\Repository\MemberRepositoryInterface;
use App\Membership\Infrastructure\Entity\AggregateSubscription;
use App\Membership\Infrastructure\Repository\AggregateSubscriptionRepository;
use App\Twitter\Domain\Http\Client\MembersBatchAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\ListAwareHttpClientInterface;
use App\Twitter\Domain\Http\Client\TweetAwareHttpClientInterface;
use App\Twitter\Domain\Publication\Repository\PublishersListRepositoryInterface;
use App\Twitter\Infrastructure\Http\Selector\MemberOwnershipsBatchSelector;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\Http\Resource\PublishersList;
use LogicException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddMemberToPublishersListCommand extends AbstractCommand
{
    public const COMMAND_NAME = 'app:add-members-to-list';

    public const OPTION_PUBLISHERS_LIST_NAME = 'publishers_list_name';

    public const OPTION_LIST_NAME = 'list';

    public const OPTION_MEMBER_LIST = 'member_list';

    public const ARGUMENT_SCREEN_NAME = 'screen_name';

    private LoggerInterface $logger;

    private AggregateSubscriptionRepository $listSubscriptionRepository;

    private MembersBatchAwareHttpClientInterface $membersBatchHttpClient;

    private ListAwareHttpClientInterface $listAwareHttpClient;

    private MemberRepositoryInterface $memberRepository;

    private PublishersListRepositoryInterface $publishersListRepository;

    private TweetAwareHttpClientInterface $tweetAwareHttpClient;

    public function __construct(
        string                               $name,
        AggregateSubscriptionRepository      $aggregateSubscriptionRepository,
        MemberRepositoryInterface            $memberRepository,
        PublishersListRepositoryInterface    $publishersListRepository,
        MembersBatchAwareHttpClientInterface $membersListAccessor,
        ListAwareHttpClientInterface         $ownershipAccessor,
        TweetAwareHttpClientInterface        $statusAccessor,
        LoggerInterface                      $logger
    ) {
        $this->listSubscriptionRepository = $aggregateSubscriptionRepository;
        $this->memberRepository = $memberRepository;
        $this->publishersListRepository = $publishersListRepository;

        $this->membersBatchHttpClient = $membersListAccessor;
        $this->listAwareHttpClient = $ownershipAccessor;
        $this->tweetAwareHttpClient = $statusAccessor;

        $this->logger = $logger;

        parent::__construct($name);
    }

    protected function configure()
    {
        $this->setDescription('Add a member list to a publisher list.')
            ->addArgument(
                self::ARGUMENT_SCREEN_NAME,
                InputArgument::REQUIRED,
                'The screen name of a member owning lists'
            )
            ->addOption(
                self::OPTION_MEMBER_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'A comma-separated list of member screen names'
            )->addOption(
                self::OPTION_LIST_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'The optional name of a list containing members to be added to a Twitter list'
            )
            ->addOption(
                self::OPTION_PUBLISHERS_LIST_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'The name of a Twitter list'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        if ($this->providedWithInvalidOptions()) {
            $errorMessage = 'No list name, nor screen name has been provided.';
            $this->logger->critical($errorMessage);
            $this->output->writeln($errorMessage);

            return self::FAILURE;
        }

        try {
            $this->addMembersToList($this->findListToWhichMembersShouldBeAddedTo());
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->output->writeln($exception->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function findListToWhichMembersShouldBeAddedTo(): PublishersList
    {
        $screenName = $this->input->getArgument(self::ARGUMENT_SCREEN_NAME);
        $ownershipsLists = $this->listAwareHttpClient->getMemberOwnerships(
            new MemberOwnershipsBatchSelector($screenName)
        );

        $publishersListName = $this->input->getOption(self::OPTION_PUBLISHERS_LIST_NAME);

        $filteredLists = [];

        while (empty($filteredLists) && $ownershipsLists->nextPage() !== -1) {
            $filteredLists = array_filter(
                $ownershipsLists->toArray(),
                static function (PublishersList $list) use ($publishersListName) {
                    return $list->name() === $publishersListName;
                }
            );

            $ownershipsLists = $this->listAwareHttpClient->getMemberOwnerships(
                new MemberOwnershipsBatchSelector($screenName, $ownershipsLists->nextPage())
            );
        }

        if (count($filteredLists) !== 1) {
            throw new LogicException('There should be exactly one remaining list.');
        }

        return array_pop($filteredLists);
    }

    private function addMembersToList(PublishersList $targetList): void {
        $memberIds = $this->getListOfMembers();

        $this->membersBatchHttpClient->addMembersToList($memberIds, $targetList->id());
        $members = $this->ensureMembersExist($memberIds);

        array_walk(
            $members,
            fn (MemberInterface $member) => $this->publishersListRepository->addMemberToList($member, $targetList)
        );

        $this->output->writeln('All members have been successfully added to the Twitter list.');
    }

    /**
     * @param $memberList
     * @return array
     */
    private function ensureMembersExist($memberList): array
    {
        return array_map(
            function (string $memberIdentifier) {

                if (is_numeric($memberIdentifier)) {
                    $member = $this->memberRepository->findOneBy(['twitterID' => $memberIdentifier]);
                } else {
                    $member = $this->memberRepository->findOneBy(['twitter_username' => $memberIdentifier]);
                }

                if (!($member instanceof MemberInterface)) {
                    $member = $this->tweetAwareHttpClient->ensureMemberHavingNameExists($memberIdentifier);
                }

                return $member;
            },
            $memberList
        );
    }

    private function getListOfMembers(): array
    {
        if ($this->input->hasOption(self::OPTION_LIST_NAME) &&
            $this->input->getOption(self::OPTION_LIST_NAME)) {

            try {
                $subscriptions = $this->listSubscriptionRepository->findSubscriptionsByAggregateName(
                    $this->input->getOption(self::OPTION_LIST_NAME)
                );
            } catch (\Exception $exception) {
                $this->logger->critical($exception->getMessage());
                $this->output->writeln($exception->getMessage());

                return [];
            }

            $memberList = [];
            array_walk(
                $subscriptions,
                static function (AggregateSubscription $subscription) use (&$memberList) {
                    $memberId = $subscription->subscription->twitterId();
                    $memberList[] = $memberId;
                }
            );

            return $memberList;
        }

        return explode(',', $this->input->getOption(self::OPTION_MEMBER_LIST));
    }

    /**
     * @return bool
     */
    private function providedWithInvalidOptions(): bool
    {
        return (!$this->input->hasArgument(self::ARGUMENT_SCREEN_NAME) &&
                !$this->input->hasOption(self::OPTION_LIST_NAME)) ||
            (!$this->input->getOption(self::OPTION_LIST_NAME) &&
                !$this->input->getArgument(self::ARGUMENT_SCREEN_NAME));
    }
}
