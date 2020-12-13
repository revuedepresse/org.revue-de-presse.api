<?php

namespace App\Membership\Infrastructure\Command;

use App\Twitter\Infrastructure\Console\CommandReturnCodeAwareInterface;
use App\Membership\Infrastructure\Repository\NetworkRepository;
use OldSound\RabbitMqBundle\RabbitMq\ProducerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ImportNetworkCommand extends Command implements CommandReturnCodeAwareInterface
{
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
     * @var NetworkRepository
     */
    public $networkRepository;

    /**
     * @var ProducerInterface
     */
    public $producer;

    public function configure()
    {
        $this->setName('import-network')
            ->setDescription('Import subscriptions and subscribees of each member in a member list.')
            ->addOption(
                self::OPTION_MEMBER_LIST,
                null,
                InputOption::VALUE_OPTIONAL,
                'A comma-separated list of member screen names'
            )
            ->addOption(
                self::OPTION_MEMBER_NAME,
                null,
                InputOption::VALUE_OPTIONAL,
                'The name of a member, which network should be imported'
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

        $memberList = $this->input->getOption(self::OPTION_MEMBER_LIST);
        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        
        $validMemberList = strlen(trim($memberList)) > 0;

        if (!$validMemberList && strlen(trim($memberName)) === 0) {
            throw new \LogicException(
                'There should be at least a non-empty member list or a member name passed as argument'
            );
        }

        if ($validMemberList) {
            $members = explode(',', $memberList);

            array_walk(
                $members,
                function (string $member) {
                    $messageBody = [$member];

                    $this->producer->setContentType('application/json');
                    $this->producer->publish(serialize(json_encode($messageBody)));
                }
            );

            return self::RETURN_STATUS_SUCCESS;
        }

        $this->networkRepository->saveNetwork([$memberName]);

        return self::RETURN_STATUS_SUCCESS;
    }

}
