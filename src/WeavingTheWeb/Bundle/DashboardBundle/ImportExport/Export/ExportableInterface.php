<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export;

interface ExportableInterface
{
    public function isExportable();

    public function getExportedAt();

    /**
     * @param \DateTime $exportedAt
     * @return mixed
     */
    public function setExportedAt(\DateTime $exportedAt);

    /**
     * @param $destination
     * @return mixed
     */
    public function setExportDestination($destination);

    public function getExportDestination();
}
