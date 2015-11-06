<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Import;

interface ImportableInterface
{
    public function isImportable();

    public function getImportedAt();

    public function setImportedAt(\DateTime $importedAt);
}
