<?php

namespace WeavingTheWeb\Bundle\ApiBundle\Command;

use FOS\ElasticaBundle\Command\PopulateCommand as BaseCommand;
use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption,
    Symfony\Component\Console\Output\OutputInterface;

/**
 * Populate the search index
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PopulateIndexCommand extends BaseCommand
{
    const OPTION_INDEX = 'index';

    const OPTION_TYPE = 'type';

    const OPTION_RESET = 'reset';

    const OPTION_BATCH_SIZE = 'batch-size';

    /**
     * @see Symfony\Component\Console\Command\Command::configure()
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('weaving-the-web:api:populate-index')
            ->addOption(self::OPTION_INDEX, null, InputOption::VALUE_OPTIONAL, 'The index to repopulate')
            ->addOption(self::OPTION_TYPE, null, InputOption::VALUE_OPTIONAL, 'The type to repopulate')
            ->addOption(self::OPTION_RESET, null, InputOption::VALUE_NONE, 'Reset index before populating')
            ->addOption(self::OPTION_BATCH_SIZE, null, InputOption::VALUE_REQUIRED, 'Index packet size (overrides provider config option)')
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

        $output->writeln($this->getNoLeftoversMessage());
    }

    /**
     * @return mixed
     */
    protected function getNoLeftoversMessage()
    {
        $translator = $this->getContainer()->get('translator');

        return $translator->trans('no_more_document_left', array(), 'command');
    }
}
