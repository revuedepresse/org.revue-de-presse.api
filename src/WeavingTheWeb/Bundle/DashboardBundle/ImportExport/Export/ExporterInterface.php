<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface ExporterInterface
{
    public function addExportable(ExportableInterface $exportable);

    public function declareAsExported(ExportableInterface $exportable);

    public function export();
}
