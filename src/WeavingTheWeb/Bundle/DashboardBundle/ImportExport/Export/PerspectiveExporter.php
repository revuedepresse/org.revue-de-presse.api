<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\QueryExecutionErrorException;

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

    public function addExportable(ExportableInterface $perspective)
    {
        $this->exportableCollection[] = $perspective;
    }

    public function export()
    {
        parent::export();

        array_map(function (Perspective $perspective) {
            if ($perspective->isExportable()) {
                $destinationPath = $this->destinationDirectory . '/' . $perspective->getUuid() . '.json';
                if (file_exists($destinationPath)) {
                    $this->logger->info(sprintf('A perspective has already been exported to "%s"', $destinationPath));
                    return;
                }

                try {
                    $results = $this->connection->query($perspective->getValue())->getResults();
                    $encryptedMessage = $this->crypto->encrypt(json_encode($results), $perspective->getName());
                    $jsonEncodedEncryptedMessage = json_encode($encryptedMessage);
                    file_put_contents($destinationPath, $jsonEncodedEncryptedMessage);

                    $perspective->setExportedAt(new \DateTime());
                } catch (QueryExecutionErrorException $exception) {
                    $perspective->markAsHavingInvalidValue();
                }

                $this->entityManager->persist($perspective);
                $this->entityManager->flush();
            }
        }, $this->exportableCollection);

        // Empty exportable collection
        $this->exportableCollection = [];
    }
}
