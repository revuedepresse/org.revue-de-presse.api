<?php

namespace App\Aggregate\Command;

use App\Aggregate\Repository\MemberAggregateSubscriptionRepository;
use App\Console\AbstractCommand;
use App\Member\Repository\AggregateSubscriptionRepository;
use App\Member\Repository\NetworkRepository;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use WeavingTheWeb\Bundle\TwitterBundle\Api\Accessor;

class ImportMemberAggregatesCommand extends AbstractCommand
{
    const OPTION_MEMBER_NAME = 'member-name';

    /**
     * @var Accessor
     */
    public $accessor;

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

    public function configure()
    {
        $this->setName('import-aggregates')
            ->addOption(
                self::OPTION_MEMBER_NAME,
                'm',
                InputOption::VALUE_REQUIRED,
                'The name of a member'
            )
            ->setDescription('Import lists of a member as aggregates');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $member = $this->accessor->ensureMemberHavingNameExists($memberName);
        $listSubscriptions = $this->accessor->getUserListSubscriptions($memberName);

        array_walk(
            $listSubscriptions->lists,
            function (\stdClass $list) use ($member) {
                $memberAggregateSubscription = $this->memberAggregateSubscriptionRepository
                    ->make(
                        $member,
                        (array) $list
                    );

                $list = $this->accessor->getListMembers($memberAggregateSubscription->listId);

                array_walk(
                    $list->users,
                    function (\stdClass $user) use ($memberAggregateSubscription) {
                        $member = $this->networkRepository->ensureMemberExists($user->id);
                        $this->aggregateSubscriptionRepository->make($memberAggregateSubscription, $member);
                    }
                );
            }
        );

        $this->output->writeln(sprintf(
            'All list subscriptions have be saved for member with name "%s"',
            $memberName
        ));
    }
}
