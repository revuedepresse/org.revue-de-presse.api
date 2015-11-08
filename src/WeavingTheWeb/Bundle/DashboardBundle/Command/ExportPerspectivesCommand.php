<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Component\Console\Input\InputInterface,
    Symfony\Component\Console\Input\InputOption;

use Symfony\Component\Console\Output\OutputInterface;

use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event\ExportEvents,
    WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event\ExportPerspectivesEvent;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ExportPerspectivesCommand extends AbstractImportExportCommand
{
    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public $eventDispatcher;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    public $entityManager;

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

            if ($this->hasJobOption($input)) {
                $this->createJobArchive($input);
            }

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

    /**
     * @param InputInterface $input
     * @return bool
     */
    protected function hasJobOption(InputInterface $input)
    {
        return $input->hasOption(self::OPTION_JOB) && is_numeric($input->getOption(self::OPTION_JOB));
    }

    /**
     * @param InputInterface $input
     */
    protected function createJobArchive(InputInterface $input)
    {
        $jobId = $input->getOption(self::OPTION_JOB);
        $job = $this->jobRepository->findStartedCommandJobById($jobId);

        if (is_null($job)) {
            $this->logger->info(sprintf('No job with #%d is started.', $jobId));
        } else {
            $exportEvent = new ExportPerspectivesEvent();
            $exportEvent->setJob($job);

            $this->eventDispatcher->dispatch(ExportEvents::CREATE_ARCHIVE, $exportEvent);

            $this->entityManager->persist($job);
            $this->entityManager->flush();
        }
    }
}
