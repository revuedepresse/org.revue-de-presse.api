<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ExportPerspectivesCommand extends AbstractImportExportCommand
{
    public function configure()
    {
        parent::configure();

        $this->setName('weaving-the-web:perspective:export')
            ->setDescription('Iterates over perspectives to export those which can be')
            ->addOption('all', null, InputOption::VALUE_NONE,
                'Run command for all exportable perspectives by default.')
            ->setAliases(['wtw:das:per:exp']);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $perspectiveExporter = $this->getContainer()
            ->get('weaving_the_web_dashboard.import_export.perspective_exporter');
        $this->mappingConfigurator->configurePerspectiveExporter($perspectiveExporter);
        $perspectives = $this->perspectiveRepository->findExportablePerspectives();
        $mapping = $this->getContainer()->get('weaving_the_web_mapping.mapping');

        try {
            $mapping->walk($perspectives);
            $message = $this->translator->trans('perspective.export.success', [], 'perspective');
            $returnCode = 0;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $message = $this->translator->trans('perspective.export.error', [], 'perspective');
            $returnCode = $exception->getCode();
        }

        $output->writeln($message);

        return $returnCode ;
    }
}
