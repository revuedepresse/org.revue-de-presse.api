<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event;

use Symfony\Component\EventDispatcher\Event;

use WeavingTheWeb\Bundle\ApiBundle\Event\AbstractJobAwareEvent;

use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export\ExportableInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ExportPerspectivesEvent extends AbstractJobAwareEvent implements ExportEventInterface
{
    /**
     * @var array
     */
    protected $exportedCollection = [];

    /**
     * @param ExportableInterface $perspective
     * @return int
     */
    public function declareAsExported(ExportableInterface $perspective)
    {
        if (!array_key_exists($perspective->getExportDestination(), $this->exportedCollection)) {
            $this->exportedCollection[$perspective->getExportDestination()] = $perspective;
        }

        return count($this->exportedCollection);
    }

    /**
     * @return array
     */
    public function getExportedCollection()
    {
        return $this->exportedCollection;
    }
}
