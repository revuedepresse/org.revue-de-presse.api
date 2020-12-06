<?php
declare (strict_types=1);

namespace App\Domain\Subscription\Console;

use App\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMemberSubscriptionsCommand extends AbstractCommand
{
    public function __construct(
        $name = null,
        Access$accessor
    ) {
        parent::__construct($name);
    }
    public function configure(): void
    {
        $this->setName('press-review:list-member-subscriptions')
            ->setDescription('List the subscriptions of a member')
            ->addArgument(
                'screen_name',
                InputArgument::REQUIRED,
                'The screen name of a member'
            )->setAliases(['pr:lm']);
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        return self::RETURN_STATUS_SUCCESS;
    }
}