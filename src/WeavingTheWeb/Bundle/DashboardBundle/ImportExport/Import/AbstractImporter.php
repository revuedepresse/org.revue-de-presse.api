<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Import;

use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\AbstractImportExport;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
abstract class AbstractImporter extends AbstractImportExport implements ImporterInterface
{
    /**
     * @var array
     */
    protected $importableCollection = [];

    public $sourceDirectory;

    abstract public function addImportable(ImportableInterface $importable);

    public function import() {
        $this->validateSourceDirectory();
    }

    protected function validateSourceDirectory()
    {
        if (!file_exists($this->sourceDirectory) ||
            !is_writable($this->sourceDirectory) ||
            !is_dir($this->sourceDirectory)
        ) {
            throw new \Exception(sprintf('Invalid source directory ("%s")', $this->sourceDirectory));
        }
    }
}
