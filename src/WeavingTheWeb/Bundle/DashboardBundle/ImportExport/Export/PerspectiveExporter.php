<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\QueryExecutionErrorException;

use WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event\ExportEvents;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PerspectiveExporter extends AbstractExporter
{
    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository
     */
    public $repository;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\DBAL\Connection
     */
    public $connection;

    /**
     * @var \WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event\ExportEventInterface
     */
    public $exportEvent;

    public function addExportable(ExportableInterface $perspective)
    {
        $this->exportableCollection[] = $perspective;
    }

    public function declareAsExported(ExportableInterface $perspective)
    {
        $this->exportedCollection[] = $perspective;
        $this->exportEvent->declareAsExported($perspective);
    }

    public function export()
    {
        parent::export();

        array_map(function (Perspective $perspective) {
            if ($perspective->isExportable()) {
                $destinationPath = sprintf(
                    '%s/%s_%s.json',
                    realpath($this->destinationDirectory),
                    $perspective->getUuid(),
                    time()
                );
                if (file_exists($destinationPath)) {
                    $this->logger->info(sprintf('A perspective has already been exported to "%s"', $destinationPath));
                    return;
                }

                try {
                    $results = $this->connection->query($perspective->getValue())->getResults();
                    $encryptedMessage = $this->crypto->encrypt(json_encode($results), $perspective->getName());
                    $jsonEncodedEncryptedMessage = json_encode($encryptedMessage);
                    file_put_contents($destinationPath, $jsonEncodedEncryptedMessage);

                    $relativeDestinationPath = str_replace(realpath($this->projectRootDir) . '/', '', $destinationPath);
                    $perspective->setExportDestination($relativeDestinationPath);
                    $perspective->setExportedAt(new \DateTime());

                    $this->declareAsExported($perspective);
                } catch (QueryExecutionErrorException $exception) {
                    $perspective->markAsHavingInvalidValue();
                }

                $this->entityManager->persist($perspective);
                $this->entityManager->flush();
            }
        }, $this->exportableCollection);

        // Empty exportable collection
        $this->exportableCollection = [];

        $this->eventDispatcher->dispatch(ExportEvents::POST_EXPORT_COLLECTION, $this->exportEvent);
    }
}
