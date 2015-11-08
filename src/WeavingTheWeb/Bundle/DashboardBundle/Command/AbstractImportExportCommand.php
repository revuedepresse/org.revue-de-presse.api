<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Input\InputOption;

use WeavingTheWeb\Bundle\ApiBundle\Command\JobAwareCommandInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractImportExportCommand extends ContainerAwareCommand implements JobAwareCommandInterface
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

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \WeavingTheWeb\Bundle\ApiBundle\Repository\JobRepository
     */
    public $jobRepository;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public $eventDispatcher;

    public function configure()
    {
        $this->addJobOption();
    }

    public function getJobRepository()
    {
        $this->jobRepository;
    }

    public function addJobOption()
    {
        $this->addOption(self::OPTION_JOB, [], InputOption::VALUE_OPTIONAL,
            'The of a job, which completion depends on the command execution.');
    }
}
