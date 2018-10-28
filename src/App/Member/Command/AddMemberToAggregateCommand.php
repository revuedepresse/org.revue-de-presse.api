<?php

namespace App\Member\Command;

use App\Console\CommandReturnCodeAwareInterface;
use App\Member\MemberInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\ApiBundle\Repository\AggregateRepository;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;
use WTW\UserBundle\Repository\UserRepository;

class AddMemberToAggregateCommand extends Command implements CommandReturnCodeAwareInterface
{
    const OPTION_MEMBER_LIST = 'member-list';

    const OPTION_AGGREGATE_NAME = 'aggregate-name';

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
     * @var AggregateRepository
     */
    public $aggregateRepository;

    /**
     * @var UserRepository
     */
    public $userRepository;

    public function configure()
    {
        $this->setName('add-members-to-aggregate')
            ->setDescription('Add a member list to an aggregate.')
            ->addOption(
                self::OPTION_MEMBER_LIST,
                null,
                InputOption::VALUE_REQUIRED,
                'A comma-separated list of member screen names'
            )->addOption(
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
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->addMembersToList($this->findListToWhichMembersShouldBeAddedTo());

        return self::RETURN_STATUS_SUCCESS;
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
     * @return \stdClass
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\ApiBundle\Exception\InvalidTokenException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function findListToWhichMembersShouldBeAddedTo(): \stdClass
    {
        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $ownershipsLists = $this->accessor->getUserOwnerships($memberName);

        $aggregateName = $this->input->getOption(self::OPTION_AGGREGATE_NAME);
        $filteredLists = array_filter(
            $ownershipsLists->lists,
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
     * @param \stdClass $targetList
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\SuspendedAccountException
     * @throws \WeavingTheWeb\Bundle\TwitterBundle\Exception\UnavailableResourceException
     */
    private function addMembersToList(
        \stdClass $targetList
    ): void
    {
        $memberList = explode(',', $this->input->getOption(self::OPTION_MEMBER_LIST));
        $this->accessor->addMembersToList($memberList, $targetList->id_str);

        $members = $this->ensureMembersExist($memberList);
        array_walk(
            $members,
            function (MemberInterface $member) use ($targetList) {
                $this->aggregateRepository->addMemberToList($member, $targetList);
            }
        );
    }
}
