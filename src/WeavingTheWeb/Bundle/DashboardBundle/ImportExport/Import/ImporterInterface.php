<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Import;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface ImporterInterface
{
    public function addImportable(ImportableInterface $importable);

    public function import();
}
