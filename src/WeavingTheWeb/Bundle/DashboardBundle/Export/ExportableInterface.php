<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Export;

interface ExportableInterface
{
    public function isExportable();

    public function getExportedAt();

    public function setExportedAt(\DateTime $exportedAt);
}
