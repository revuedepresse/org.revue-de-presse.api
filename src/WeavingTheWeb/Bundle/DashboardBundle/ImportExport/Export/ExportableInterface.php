<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export;

interface ExportableInterface
{
    public function isExportable();

    public function getExportedAt();

    public function setExportedAt(\DateTime $exportedAt);
}
