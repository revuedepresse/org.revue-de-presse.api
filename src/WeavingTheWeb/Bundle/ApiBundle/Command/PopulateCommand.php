<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use FOS\ElasticaBundle\Command\PopulateCommand as BaseCommand;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Populate the search index
 */
class PopulateCommand extends BaseCommand
{
    /**
     * @see Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        $this
            ->setName('wtw:elastica:populate')
            ->addOption('index', null, InputOption::VALUE_OPTIONAL, 'The index to repopulate')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'The type to repopulate')
            ->addOption('reset', null, InputOption::VALUE_NONE, 'Reset index before populating')
            ->setDescription('Populates search indexes from providers')
        ;
    }

    /**
     * @see Symfony\Component\Console\Command\Command::execute()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('reset')) {
            $noReset = false;
        } else {
            $noReset = true;
        }
        $input->setOption('no-reset', $noReset);

        parent::execute($input, $output);
    }
}
