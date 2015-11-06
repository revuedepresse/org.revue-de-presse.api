<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Export;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var array
     */
    protected $exportableCollection = [];

    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    public $entityManager;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Security\CryptoInterface
     */
    public $crypto;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    public $destinationDirectory;

    abstract public function addExportable(ExportableInterface $exportable);

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
