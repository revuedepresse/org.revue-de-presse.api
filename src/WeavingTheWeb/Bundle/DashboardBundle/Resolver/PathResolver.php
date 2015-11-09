<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Resolver;

use WeavingTheWeb\Bundle\DashboardBundle\Exception\NonExistingFileException;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PathResolver
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    public $logger;

    /**
     * @var string
     */
    public $logsDir;

    /**
     * @var string
     */
    public $projectRootDir;

    /**
     * Returns the relative path of a file or a symlink in a seamless way (relatively to the project root dir)
     *
     * @param $filePath
     * @return string
     */
    public function getRelativePath($filePath)
    {
        if (!file_exists($filePath)) {
            $this->raiseFileNotExistException($filePath);
        }
        $parentDirName = basename(dirname($filePath));

        $parentDir = dirname(dirname($filePath));

        $fileObject = new \SplFileObject($filePath);
        $filename = $fileObject->getFilename();

        $relativeGrandParentDir = strtr(
            realpath($parentDir), [
                realpath($this->projectRootDir) . '/' => '',
                // Removes logs directory parent path from file path.
                // Logs might be a shared directory containing the file,
                // which path has been passed as argument here
                // See also ":linked_dirs" in "config/deploy.rb" Capistrano script
                realpath($this->logsDir . '/../..') . '/' => ''
            ]
        );

        $relativePath = $relativeGrandParentDir . '/' . $parentDirName . '/' .$filename;
        $this->logger->info(sprintf('Returning "%s" as relative path for "%s"', $relativePath, $filePath));

        return $relativePath;
    }

    /**
     * @param $filePath
     * @return string
     */
    public function getAbsolutePath($filePath)
    {
        $absoluteFilePath = realpath($this->projectRootDir) . '/' . $filePath;
        if (!file_exists($absoluteFilePath)) {
            $this->raiseFileNotExistException($absoluteFilePath);
        }

        return $absoluteFilePath;
    }

    /**
     * @param $filePath
     * @throws NonExistingFileException
     */
    protected function raiseFileNotExistException($filePath)
    {
        throw new NonExistingFileException(
            sprintf('File "%s" does not exist', $filePath),
            NonExistingFileException::DEFAULT_CODE
        );
    }
}
