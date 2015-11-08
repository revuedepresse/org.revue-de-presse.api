<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

interface ExportEventSubscriberInterface extends EventSubscriberInterface
{
    public function onPostExportCollection(ExportEventInterface $event);

    public function onCreateArchive(ExportEventInterface $event);
}
