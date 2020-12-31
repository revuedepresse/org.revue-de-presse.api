<?php
declare(strict_types=1);

namespace App\Twitter\Infrastructure\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command implements CommandReturnCodeAwareInterface
{
    protected InputInterface $input;

    protected OutputInterface $output;

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Enforce implementation of this method
        return self::RETURN_STATUS_FAILURE;
    }
}
