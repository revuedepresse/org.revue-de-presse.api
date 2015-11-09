<?php

namespace WeavingTheWeb\Bundle\DashboardBundle\Resolver;

/**
 * @author  Thierry Marianne <thierry.marianne@weaving-the-web.org>
 */
class PathResolver
{
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

        $relativeGrandParentDir = str_replace(realpath($this->projectRootDir) . '/', '', realpath($parentDir));

        return $relativeGrandParentDir . '/' . $parentDirName . '/' .$filename;
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
     */
    protected function raiseFileNotExistException($filePath)
    {
        throw new \InvalidArgumentException(sprintf('File "%s" does not exist', $filePath));
    }
}
