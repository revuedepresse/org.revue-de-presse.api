<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\ImportExport\Import;

use WeavingTheWeb\Bundle\DashboardBundle\Entity\Perspective;

use WeavingTheWeb\Bundle\DashboardBundle\Exception\DecodeContentException,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\DecryptMessageException,
    WeavingTheWeb\Bundle\DashboardBundle\Exception\SaveContentException;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PerspectiveImporter extends AbstractImporter
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
     * @var string
     */
    public $uploadDir;

    public function addImportable(ImportableInterface $importable)
    {
        $this->importableCollection[] = $importable;
    }

    public function import()
    {
        parent::import();

        array_map(
            function (Perspective $perspective) {
                if ($perspective->isImportable()) {
                    $newFilename = $perspective->getNewFilename($this->sourceDirectory);
                    // Prefix original file name with "_"
                    $success = rename($perspective->getValue(), $newFilename);
                    if (!$success) {
                        $errorMessage = sprintf('Could not move file "%s" to "%s"',
                            $perspective->getJsonFilename(), $newFilename);
                        $this->logger->error($errorMessage);
                    } else {
                        $perspective->setValue($newFilename);
                        $this->decodeImportedPerspective($perspective);
                    }

                    $perspective->setImportedAt(new \DateTime());

                    $this->entityManager->persist($perspective);
                    $this->entityManager->flush();
                }
            },
            $this->importableCollection
        );

        // Empty exportable collection
        $this->importableCollection = [];
    }

    /**
     * @param Perspective $perspective
     * @throws DecodeContentException
     * @throws \Exception
     */
    protected function decodeImportedPerspective(Perspective $perspective)
    {
        $decodedContent = json_decode(file_get_contents($perspective->getValue()), $asAssociativeArray = true);
        $lastJsonError = json_last_error();
        if ($lastJsonError !== JSON_ERROR_NONE) {
            $errorMessage = sprintf('Could not json encoded file "%s', $perspective->getValue());
            $this->logger->error($errorMessage);
            throw new DecodeContentException($errorMessage, DecodeContentException::DEFAULT_CODE);
        } else {
            $this->saveEncryptedMessage($perspective, $decodedContent, $perspective->getValue());
        }
    }

    /**
     * @param Perspective $perspective
     * @param $decodedContent
     * @param $newFilename
     * @throws DecryptMessageException
     * @throws SaveContentException
     */
    protected function saveEncryptedMessage(Perspective $perspective, $decodedContent, $newFilename)
    {
        if (!array_key_exists('encrypted_message', $decodedContent)) {
            $errorMessage = sprintf('No encrypted message available for "%s', $newFilename);
            $this->logger->error($errorMessage);
        } else {
            $message = $this->decryptSafely($decodedContent['encrypted_message']);

            $destinationPath = $this->getPerspectiveDestinationPath($perspective);
            $success = file_put_contents($destinationPath, $message);
            if (!$success) {
                $errorMessage = sprintf('Could not save decrypted message to "%s"', $destinationPath);
                $this->logger->error($errorMessage);
                throw new SaveContentException($errorMessage, SaveContentException::DEFAULT_CODE);
            } else {
                $relativePath = strtr($this->getPerspectiveDestinationPath($perspective), [
                    realpath($this->projectRootDir) . '/' => '',
                ]);
                $perspective->setValue($relativePath);
            }
        }

        if (array_key_exists('encrypted_name', $decodedContent)) {
            if (strlen($decodedContent['encrypted_name']) > 0) {
                $decryptedName = $this->decryptSafely($decodedContent['encrypted_name']);
            } else {
                $decryptedName = '';
            }

            $perspective->setName($decryptedName);
        }
    }

    /**
     * @param Perspective $perspective
     * @return string
     * @throws \Exception
     */
    protected function getPerspectiveDestinationPath(Perspective $perspective)
    {
        return realpath($this->uploadDir) . '/' . substr($perspective->getJsonFilename(), 1);
    }

    /**
     * @param $encryptedMessage
     * @return mixed
     * @throws DecryptMessageException
     */
    protected function decryptSafely($encryptedMessage)
    {
        try {
            return $this->crypto->decrypt($encryptedMessage);
        } catch (\Exception $exception) {
            throw new DecryptMessageException(
                $exception->getMessage(),
                DecryptMessageException::DEFAULT_CODE,
                $exception
            );
        }
    }
}
