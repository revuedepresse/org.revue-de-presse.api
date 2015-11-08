<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Event;

use Ramsey\Uuid\Uuid;

use WeavingTheWeb\Bundle\ApiBundle\Event\JobAwareEventInterface;

use WeavingTheWeb\Bundle\DashboardBundle\Exception\CloseArchiveException,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\OpenArchiveException,
    WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export\ExportableInterface;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class ExportSubscriber implements ExportEventSubscriberInterface
{
    /**
     * @var array
     */
    private $exportedCollection = [];

    /**
     * @var string
     */
    public $projectRootDir;

    /**
     * @var string
     */
    public $archiveDir;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var \Symfony\Component\Routing\Router
     */
    public $router;

    public static function getSubscribedEvents()
    {
        return [
            ExportEvents::POST_EXPORT_COLLECTION => [
                ['onPostExportCollection', 10]
            ],
            ExportEvents::CREATE_ARCHIVE =>  [
                ['onCreateArchive', 10]
            ]
        ];
    }

    /**
     * @param ExportEventInterface $event
     */
    public function onPostExportCollection(ExportEventInterface $event)
    {
        $exportedCollection = $event->getExportedCollection();

        /**
         * @var \WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Export\ExportableInterface $exportable
         */
        foreach ($exportedCollection as $exportable) {
            if (
                ($exportable instanceof ExportableInterface) &&
                !array_key_exists($exportable->getExportDestination(), $this->exportedCollection)
            ) {
                $this->exportedCollection[$exportable->getExportDestination()] = $exportable;
            }
        }
    }

    public function onCreateArchive(ExportEventInterface $event)
    {
        $archiveName = (string)Uuid::uuid1();

        $projectRootDir = realpath($this->projectRootDir);
        $archivePath = realpath($this->archiveDir) . '/' . $archiveName . '.zip';

        $archive = $this->openArchive($archivePath);

        foreach ($this->exportedCollection as $exportable) {
            if ($exportable instanceof ExportableInterface) {
                $exportPath = $projectRootDir . '/' . $exportable->getExportDestination();
                $exportFile = new \SplFileObject($exportPath);
                $archive->addFile($exportPath, $exportFile->getFilename());
            }
        }

        $this->closeArchive($archive, $archivePath);

        if ($event instanceof JobAwareEventInterface) {
            /**
             * @var \WeavingTheWeb\Bundle\ApiBundle\Entity\Job $job
             */
            $job = $event->getJob();
            $archiveFile = new \SplFileObject($archivePath);
            $filename = str_replace('.zip', '', $archiveFile->getFilename());

            $router = $this->router;
            $getArchiveUrl = $this->router->generate(
                'weaving_the_web_api_get_archive',
                ['filename' => $filename], $router::ABSOLUTE_URL
            );
            $job->setOutput($getArchiveUrl);
        }

        $this->exportedCollection = [];
    }

    /**
     * @param $archivePath
     * @return mixed
     * @throws OpenArchiveException
     */
    protected function openArchive($archivePath)
    {
        $archive = new \ZipArchive();
        $success = $archive->open($archivePath, \ZipArchive::CREATE);
        if ($success !== true) {
            $errorMessage = sprintf('Could not open a new archive "%s" (error #%d)', $archivePath, $success);
            $this->logger->error($errorMessage);
            throw new OpenArchiveException($errorMessage);
        }

        return $archive;
    }

    /**
     * @param \ZipArchive $archive
     * @param $archivePath
     * @throws CloseArchiveException
     */
    protected function closeArchive(\ZipArchive $archive, $archivePath)
    {
        $success = $archive->close();
        if ($success !== true) {
            $errorMessage = sprintf('Could not close the archive "%s".', $archivePath);
            $this->logger->error($errorMessage);
            throw new CloseArchiveException($errorMessage);
        }
    }
}
