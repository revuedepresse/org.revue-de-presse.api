<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export;

use WeavingTheWeb\Bundle\DashboardBundle\Event\ExportEventInterface;
use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\AbstractImportExport;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractExporter extends AbstractImportExport implements ExporterInterface
{
    /**
     * @var array
     */
    protected $exportableCollection = [];

    /**
     * @var array
     */
    protected $exportedCollection = [];

    /**
     * @var string
     */
    public $destinationDirectory;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcher
     */
    public $eventDispatcher;

    abstract public function addExportable(ExportableInterface $exportable);

    abstract public function declareAsExported(ExportableInterface $exportable);

    public function export() {
        $this->validateDestinationDirectory();
    }

    protected function validateDestinationDirectory()
    {
        if (!file_exists($this->destinationDirectory) ||
            !is_writable($this->destinationDirectory) ||
            !is_dir($this->destinationDirectory)
        ) {
            throw new \Exception(sprintf('Invalid destination directory ("%s")', $this->destinationDirectory));
        }
    }
}
