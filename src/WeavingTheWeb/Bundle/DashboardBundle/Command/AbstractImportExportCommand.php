<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractImportExportCommand extends ContainerAwareCommand
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
}
