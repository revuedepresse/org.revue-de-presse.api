<?php

namespace WeavingTheWeb\Bundle\MappingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand,
    Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Output\OutputInterface,
    Symfony\Component\Console\Input\InputOption;

/**
 * @package WeavingTheWeb\Bundle\MappingBundle\Tests\Command
 */
class SaveEmailHeadersCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this->setName('weaving_the_web:mapping:mail:headers')
            ->setDescription('Save emails headers as properties')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Number of item per processing page', 1000)
            ->addOption(
                'max_offset',
                'mp',
                InputOption::VALUE_OPTIONAL,
                'Max offset to be reached while processing items',
                100
            )
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset of first item to process first', 0)
            ->addOption('memory_limit', 'ml', InputOption::VALUE_OPTIONAL, 'Memory limit not to be exceeded', $this->getDefaultMemoryLimit())
            ->addOption('save_headers_names', 'hn', InputOption::VALUE_NONE, 'Save headers names as properties')
            ->setAliases(['wtw:m:m:h']);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $options = $this->validateInput($input);
        /**
         * @var \WeavingTheWeb\Bundle\MappingBundle\Analyzer\EmailHeadersAnalyzer $emailHeadersAnalyzer
         */
        $emailHeadersAnalyzer = $this->getContainer()->get('weaving_the_web_mapping.analyzer.email_headers');
        $affectedItems = $emailHeadersAnalyzer->aggregateEmailHeadersProperties($options);

        $translator = $this->getContainer()->get('translator');

        if ($options['save_headers_names']) {
            $successMessage = $translator->trans(
                'mapping.mail.headers.success',
                ['{{ headers_count }}' => count($affectedItems)],
                'command'
            );
        } else {
            $successMessage = $translator->trans(
                'mapping.mail.update.success',
                ['{{ emails_count }}' => $affectedItems],
                'command'
            );
        }
        $output->writeLn($successMessage);
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function validateInput(InputInterface $input)
    {
        if ($input->hasOption('limit') && is_numeric($input->getOption('limit'))) {
            $itemsCountPerPage = intval($input->getOption('limit'));
        } else {
            $itemsCountPerPage = 1000;
        }

        if ($input->hasOption('offset') && is_numeric($input->getOption('offset'))) {
            $offset = intval($input->getOption('offset'));
        } else {
            $offset = 0;
        }

        if ($input->hasOption('memory_limit') && is_numeric($input->getOption('memory_limit'))) {
            $memoryLimit = intval($input->getOption('memory_limit'));
        } else {
            $memoryLimit = $this->getDefaultMemoryLimit();
        }

        if ($input->hasOption('save_headers_names') && $input->getOption('save_headers_names')) {
            $saveHeadersNames = $input->getOption('save_headers_names');
        } else {
            $saveHeadersNames = false;
        }

        if ($input->getOption('max_offset') && is_numeric($input->getOption('max_offset'))) {
            $maxOffset = intval($input->getOption('max_offset'));
        } else {
            $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');
            /** @var \WeavingTheWeb\Bundle\Legacy\ProviderBundle\Repository\WeavingHeaderRepository $headerRepository */
            $headerRepository = $entityManager->getRepository('WeavingTheWebLegacyProviderBundle:WeavingHeader');
            $headersCount = $headerRepository->count();
            $maxOffset = floor($headersCount / $itemsCountPerPage);
        }

        return array(
            'save_headers_names' => $saveHeadersNames,
            'items_per_page' => $itemsCountPerPage,
            'offset' => $offset,
            'memory_limit' => $memoryLimit,
            'max_offset' => $maxOffset,
        );
    }

    /**
     * @return float
     */
    protected function getDefaultMemoryLimit()
    {
        return intval(ini_get('memory_limit')) * 1 / 3;
    }
}
