<?php

namespace App\Membership\Infrastructure\Command;

use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ApiRateLimitingException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\NotFoundStatusException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\ReadOnlyApplicationException;
use App\Twitter\Infrastructure\Twitter\Api\Accessor\Exception\UnexpectedApiResponseException;
use App\Twitter\Infrastructure\Console\CommandReturnCodeAwareInterface;
use App\Membership\Domain\Entity\AggregateSubscription;
use App\Membership\Domain\Entity\MemberInterface;
use App\Membership\Infrastructure\Repository\AggregateSubscriptionRepository;
use App\Twitter\Infrastructure\Exception\BadAuthenticationDataException;
use App\Twitter\Infrastructure\Exception\InconsistentTokenRepository;
use App\Twitter\Infrastructure\Exception\NotFoundMemberException;
use App\Twitter\Infrastructure\Exception\ProtectedAccountException;
use App\Twitter\Infrastructure\Exception\SuspendedAccountException;
use App\Twitter\Infrastructure\Exception\UnavailableResourceException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\OptimisticLockException;
use Psr\Log\LoggerInterface;
use ReflectionException;
use stdClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use App\Twitter\Infrastructure\Api\Repository\PublishersListRepository;
use App\Twitter\Infrastructure\Api\Accessor;
use App\Twitter\Infrastructure\Repository\Membership\MemberRepository;

class AddMemberToAggregateCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_AGGREGATE_NAME = 'aggregate-name';

    const OPTION_LIST_NAME = 'list';

    const OPTION_MEMBER_LIST = 'member-list';

    const OPTION_MEMBER_NAME = 'member-name';

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var Accessor
     */
    public $accessor;

    /**
     * @var LoggerInterface
     */
    public $logger;

    /**
     * @var PublishersListRepository
     */
    public $aggregateRepository;

    /**
     * @var AggregateSubscriptionRepository
     */
    public $aggregateSubscriptionRepository;

    /**
     * @var MemberRepository
     */
    public $userRepository;

    public function configure()
    {
        $this->setName('add-members-to-aggregate')
            ->setDescription('Add a member list to an aggregate.')
            ->addOption(
                self::OPTION_MEMBER_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'A comma-separated list of member screen names'
            )->addOption(
                self::OPTION_LIST_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'The optional name of a list containing members to be added to an aggregate'
            )
            ->addOption(
                self::OPTION_AGGREGATE_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'The name of an aggregate'
            )->addOption(
                self::OPTION_MEMBER_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'The name of a member having lists'
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        if ($this->providedWithInvalidOptions()) {
            $errorMessage = 'No list name, nor member name has been provided.';
            $this->logger->critical($errorMessage);
            $this->output->writeln($errorMessage);

            return self::RETURN_STATUS_FAILURE;
        }


        try {
            $this->addMembersToList($this->findListToWhichMembersShouldBeAddedTo());
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $this->output->writeln($exception->getMessage());

            return self::RETURN_STATUS_FAILURE;
        }

        return self::RETURN_STATUS_SUCCESS;
    }

    /**
     * @return stdClass
     * @throws ApiRateLimitingException
     * @throws NotFoundStatusException
     * @throws ReadOnlyApplicationException
     * @throws UnexpectedApiResponseException
     * @throws BadAuthenticationDataException
     * @throws InconsistentTokenRepository
     * @throws NotFoundMemberException
     * @throws ProtectedAccountException
     * @throws SuspendedAccountException
     * @throws UnavailableResourceException
     * @throws NonUniqueResultException
     * @throws OptimisticLockException
     * @throws ReflectionException
     */
    private function findListToWhichMembersShouldBeAddedTo(): stdClass
    {
        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $ownershipsLists = $this->accessor->getMemberOwnerships($memberName);

        $aggregateName = $this->input->getOption(self::OPTION_AGGREGATE_NAME);
        $filteredLists = array_filter(
            $ownershipsLists->toArray(),
            function ($list) use ($aggregateName) {
                return $list->name === $aggregateName;
            }
        );

        if (count($filteredLists) !== 1) {
            throw new \LogicException('There should be exactly one remaining list');
        }

        return array_pop($filteredLists);
    }

    /**
     * @param stdClass $targetList
     * @throws OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function addMembersToList(
        stdClass $targetList
    ): void {
        $membersList = $this->getListOfMembers($targetList);

        $this->accessor->addMembersToList($membersList, $targetList->id_str);
        $members = $this->ensureMembersExist($membersList);

        array_walk(
            $members,
            function (MemberInterface $member) use ($targetList) {
                $this->aggregateRepository->addMemberToList($member, $targetList);
            }
        );
    }

    /**
     * @param $memberList
     * @return array
     */
    private function ensureMembersExist($memberList): array
    {
        return array_map(
            function (string $memberName) {
                $member = $this->userRepository->findOneBy(['twitter_username' => $memberName]);
                if (!($member instanceof MemberInterface)) {
                    $member = $this->accessor->ensureMemberHavingNameExists($memberName);
                }

                return $member;
            },
            $memberList
        );
    }

    /**
     * @param stdClass $targetList
     * @return array
     */
    private function getListOfMembers(stdClass $targetList): array
    {
        if ($this->input->hasOption(self::OPTION_LIST_NAME) &&
            $this->input->getOption(self::OPTION_LIST_NAME)) {
            try {
                $subscriptions = $this->aggregateSubscriptionRepository->findSubscriptionsByAggregateName(
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
                function (AggregateSubscription $subscription) use ($targetList, &$memberList) {
                    $memberName = $subscription->subscription->getTwitterUsername();
                    $memberList[] = $memberName;
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
        return (!$this->input->hasOption(self::OPTION_MEMBER_NAME) &&
                !$this->input->hasOption(self::OPTION_LIST_NAME)) ||
            (!$this->input->getOption(self::OPTION_LIST_NAME) &&
                !$this->input->getOption(self::OPTION_MEMBER_NAME));
    }
}
