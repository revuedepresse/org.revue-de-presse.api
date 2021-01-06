<?php
declare (strict_types=1);

namespace App;

use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AbstractCommand extends Command
{
    protected OutputInterface $output;
    protected InputInterface $input;

    protected function configure(): void
    {
        throw new Exception('Missing description');
    }

    public function setUpIo(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setUpIo($input, $output);

        return self::FAILURE;
    }
}