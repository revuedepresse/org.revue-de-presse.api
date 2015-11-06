<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ExportPerspectivesCommand extends ContainerAwareCommand
{
    /**
     * @var \WeavingTheWeb\Bundle\MappingBundle\Configurator\MappingConfigurator
     */
    public $mappingConfigurator;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository
     */
    public $perspectiveRepository;

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    public $translator;

    public function configure()
    {
        $this->setName('weaving-the-web:perspective:export')
            ->setDescription('Iterates over perspectives to export those which can be')
            ->addOption('all', null, InputOption::VALUE_NONE,
                'Run command for all exportable perspectives by default.')
            ->setAliases(['wtw:das:per:exp']);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $perspectiveExporter = $this->getContainer()->get('weaving_the_web_dashboard.export.perspective_exporter');
        $this->mappingConfigurator->configurePerspectiveExporter($perspectiveExporter);
        $perspectives = $this->perspectiveRepository->findExportablePerspectives();
        $mapping = $this->getContainer()->get('weaving_the_web_mapping.mapping');
        $mapping->walk($perspectives);
        $successMessage = $this->translator->trans('perspective.export.success', [], 'perspective');
        $output->writeln($successMessage);
    }
}
