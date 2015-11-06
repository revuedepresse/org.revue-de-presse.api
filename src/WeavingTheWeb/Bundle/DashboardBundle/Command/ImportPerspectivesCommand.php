<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ImportPerspectivesCommand extends AbstractImportExportCommand
{
    public function configure()
    {
        $this->setName('weaving-the-web:perspective:import')
            ->setDescription('Iterates over perspectives to import those available')
            ->addOption('all', null, InputOption::VALUE_NONE,
                'Run command for all available perspectives by default.')
            ->setAliases(['wtw:das:per:imp']);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $perspectiveImporter = $this->getContainer()
            ->get('weaving_the_web_dashboard.import_export.perspective_importer');
        $this->mappingConfigurator->configurePerspectiveImporter($perspectiveImporter);
        $perspectives = $this->perspectiveRepository->findImportableJsonPerspectives();
        $mapping = $this->getContainer()->get('weaving_the_web_mapping.mapping');

        try {
            $mapping->walk($perspectives);
            $message = $this->translator->trans('perspective.import.success', [], 'perspective');
            $returnCode = 0;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
            $message = $this->translator->trans('perspective.import.error', [], 'perspective');
            $returnCode = $exception->getCode();
        }

        $output->writeln($message);

        return $returnCode ;
    }
}
