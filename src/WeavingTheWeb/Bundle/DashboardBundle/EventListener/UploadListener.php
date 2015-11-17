<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\EventListener;

use Oneup\UploaderBundle\Event\PostPersistEvent;

use Symfony\Component\HttpFoundation\File\Exception\UploadException;

use WeavingTheWeb\Bundle\DashboardBundle\Exception\CloseArchiveException,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\OpenArchiveException,
    WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository;

use WeavingTheWeb\Bundle\MappingBundle\Mapping;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class UploadListener
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    public $entityManager;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var PerspectiveRepository
     */
    public $perspectiveRepository;

    /**
     * @var Mapping
     */
    public $mapping;

    public $projectRootDir;

    public $uploadDir;

    public $importDir;

    public function onUpload(PostPersistEvent $event)
    {
        $errors = [];

        /**
         * @var \SplFileInfo $uploadedFile
         */
        $uploadedFile = $event->getFile();
        $fileName = $uploadedFile->getFilename();
        $filePath = realpath($uploadedFile->getPath() . '/' . $fileName);
        $destinationDirectory = realpath($this->uploadDir);
        $destinationFilePath = $destinationDirectory . '/' . $fileName;

        if (file_exists($filePath)) {
            $success = rename($filePath, $destinationFilePath);
            if (!$success) {
                $errorMessage = sprintf('Could not move uploaded file to "%s"', $destinationFilePath);
                $this->logger->error($errorMessage);
                $errors[] = $errorMessage;
            }
        } else {
            $errorMessage = sprintf('Could not find uploaded file with name %s', $fileName);
            $this->logger->error($errorMessage);
            $errors[] = $errorMessage;
        }

        if ($uploadedFile->getExtension() === 'zip') {
            try {
                $this->savePerspectivesInArchive($destinationFilePath);
            } catch (\Exception $exception) {
                throw new UploadException(
                    json_encode(['error' => $exception->getMessage()]),
                    $exception->getCode(),
                    $exception
                );
            }
        } else {
            if (count($errors) === 0) {
                $this->savePerspective($destinationFilePath);
            } else {
                throw new UploadException(json_encode($errors));
            }
        }

        return $event;
    }

    /**
     * @param $destinationFilePath
     */
    protected function savePerspective($destinationFilePath)
    {
        $relativePathStartsAt = strlen(realpath($this->projectRootDir)) + 1;
        $relativePath = substr($destinationFilePath, $relativePathStartsAt);
        $perspective = $this->perspectiveRepository->saveFilePerspective($relativePath, $this->mapping);

        $this->entityManager->persist($perspective);
        $this->entityManager->flush();
    }

    /**
     * @param $destinationFilePath
     * @throws CloseArchiveException
     * @throws OpenArchiveException
     */
    protected function savePerspectivesInArchive($destinationFilePath)
    {
        $archive = new \ZipArchive();

        $success = $archive->open($destinationFilePath, \ZipArchive::CHECKCONS);
        if ($success !== true) {
            $errorMessage = sprintf(
                'Could not open existing archive "%s" (error #%d)',
                $destinationFilePath,
                $success
            );
            $this->logger->error($errorMessage);
            throw new OpenArchiveException($errorMessage, OpenArchiveException::INVALID_ARCHIVE);
        }

        $perspectives = [];
        $destinationDirectory = realpath($this->importDir);
        for ($fileIndex = 0; $fileIndex < $archive->numFiles; $fileIndex++) {
            $fileStat = $archive->statIndex($fileIndex);
            $perspectives[] = $destinationDirectory . '/' . $fileStat['name'];
        }

        $archive->extractTo($destinationDirectory);
        $success = $archive->close();
        if ($success !== true) {
            $errorMessage = sprintf('Could not close the archive "%s".', $destinationFilePath);
            $this->logger->error($errorMessage);
            throw new CloseArchiveException($errorMessage, CloseArchiveException::DEFAULT_CODE);
        }
    }
}
