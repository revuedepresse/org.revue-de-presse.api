<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Export;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface ExporterInterface
{
    public function addExportable(ExportableInterface $exportable);

    public function export();
}
