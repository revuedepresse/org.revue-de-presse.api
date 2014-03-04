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
            ->addOption('max_offset', 'm', InputOption::VALUE_OPTIONAL, 'Max offset to be reached while processing items', 100)
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'Offset of first item to process first', 0)
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
        $emailHeadersProperties = $this->getContainer()
            ->get('weaving_the_web_mapping.analyzer.email_headers')->analyze($options);

        $translator = $this->getContainer()->get('translator');
        $output->writeLn($translator->trans(
            'mapping.mail.headers.success',
            ['{{ headers_count }}' => count($emailHeadersProperties)],
            'command'));
    }

    /**
     * @param InputInterface $input
     * @return array
     */
    protected function validateInput(InputInterface $input)
    {
        if ($input->getOption('limit') && is_numeric($input->getOption('limit'))) {
            $itemsCountPerPage = intval($input->getOption('limit'));
        } else {
            $itemsCountPerPage = 1000;
        }

        if ($input->getOption('offset') && is_numeric($input->getOption('offset'))) {
            $offset = intval($input->getOption('offset'));
        } else {
            $offset = 0;
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
            'items_per_page' => $itemsCountPerPage,
            'offset' => $offset,
            'max_offset' => $maxOffset
        );
    }
}
