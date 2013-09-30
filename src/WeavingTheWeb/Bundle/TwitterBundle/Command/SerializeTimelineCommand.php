<?php

namespace WeavingTheWeb\Bundle\TwitterBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * Class SerializeTimelineCommand
 * @package WeavingTheWeb\Bundle\TwitterBundle\Command
 * @author Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class SerializeTimelineCommand extends ContainerAwareCommand
{
    /**
     * Configures executable commands
     */
    protected function configure()
    {
        parent::configure();

        $this->setName('tw:api:twitter:timeline:serialize')
            ->addOption(
            'user',
            null,
            InputOption::VALUE_REQUIRED,
            'A user handle is required'
        )
            ->setDescription('Serialize response returned when accessing timeline endpoint from twitter api')
            ->setAliases(array('wtw:api:tw:timeline'));
    }

    /**
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $miner     = $container->get('weaving_the_web_data_mining.miner');
        $miner->setQueryString('&u=' . $input->getOption('user'));

        $output->writeln($miner->getFeed());
    }
}
