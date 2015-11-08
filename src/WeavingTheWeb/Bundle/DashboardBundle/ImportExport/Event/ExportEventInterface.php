<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event;

use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export\ExportableInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
interface ExportEventInterface
{
    /**
     * @param ExportableInterface $exportable
     * @return mixed
     */
    public function declareAsExported(ExportableInterface $exportable);

    public function getExportedCollection();
}
