<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\EventListener;

use Oneup\UploaderBundle\Event\PostPersistEvent;

use Symfony\Component\HttpFoundation\File\Exception\UploadException;

use WeavingTheWeb\Bundle\DashboardBundle\Repository\PerspectiveRepository;

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

    public function onUpload(PostPersistEvent $event)
    {
        $errors = [];

        /**
         * @var \SplFileInfo $uploadedFile
         */
        $uploadedFile = $event->getFile();
        $fileName = $uploadedFile->getFilename();
        $filePath = realpath($uploadedFile->getPath() . '/' . $fileName);
        $destinationDirectory = realpath(__DIR__ . '/../Resources/perspectives');
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

        if (count($errors) === 0) {
            $relativePathStartsAt = strlen(realpath($this->projectRootDir)) + 1;
            $relativePath = substr($destinationFilePath, $relativePathStartsAt);
            $perspective = $this->perspectiveRepository->saveFilePerspective($relativePath, $this->mapping);

            $this->entityManager->persist($perspective);
            $this->entityManager->flush();
        } else {
            throw new UploadException(json_encode($errors));
        }

        return $event;
    }
}