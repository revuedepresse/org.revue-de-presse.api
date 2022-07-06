<?php

namespace App\Membership\Infrastructure\Console;

use App\Membership\Domain\Repository\EditListMembersInterface;
use App\Twitter\Infrastructure\Console\AbstractCommand;
use App\Twitter\Infrastructure\Http\Client\Exception\ReadOnlyApplicationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AddSingleMemberToListCommand extends AbstractCommand
{
    const OPTION_LIST_NAME = 'list-name';

    const OPTION_MEMBER_NAME = 'member-name';

    public EditListMembersInterface $editListMembers;

    public LoggerInterface $logger;

    protected function configure()
    {
        $this->setName('app:add-single-member-to-list')
            ->setDescription('Add a single member to a list.')
            ->addOption(
                self::OPTION_LIST_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'A list name'
            )
            ->addOption(
                self::OPTION_MEMBER_NAME,
                null,
                InputOption::VALUE_REQUIRED,
                'The member name owning a list'
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $memberName = $this->input->getOption(self::OPTION_MEMBER_NAME);
        $listName = $this->input->getOption(self::OPTION_LIST_NAME);

        try {
            $this->editListMembers->addMemberToList(
                $memberName,
                $listName
            );
        } catch (ReadOnlyApplicationException $exception) {
            $this->logger->critical($exception->getMessage());
            $this->output->writeln($exception->getMessage());

            return self::FAILURE;
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage());

            return self::FAILURE;
        }

        $this->output->writeln(sprintf(
            'Member with name "%s" is not a subscriber of an aggregate with name "%s"',
            $memberName,
            $listName
        ));

        return self::SUCCESS;
    }
}
