<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Files;

use League\Flysystem\FilesystemException;
use RuntimeException;

class File
{
    protected string $sourceFile;
    protected string $storage = 'local';
    protected string $md5 = '';
    protected string $sha1 = '';
    protected string $fileName = '';
    protected FileInfo $fileInfo;
    protected string $parentDir = '';

    public function __construct(string $sourceFile)
    {
        if (! file_exists($sourceFile)) {
            throw new RuntimeException(sprintf('File "%s" not found.', $sourceFile));
        }
        $this->sourceFile = $sourceFile;
        $this->fileInfo = new FileInfo($sourceFile);
    }

    /**
     * Sets the storage where the file will be saved.
     *
     * @param string $storageName
     * @return $this
     */
    public function setStorage(string $storageName): self
    {
        $this->storage = $storageName;
        return $this;
    }

    /**
     * Sets the name of the file to be saved in the database.
     *
     * @param string $fileName
     * @return $this
     */
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     * Sets the parent directory for saving.
     *
     * @param string $parentDir
     * @return $this
     */
    public function setParentDir(string $parentDir): self
    {
        $this->parentDir = $parentDir;
        return $this;
    }

    /**
     * Getting the hash of the file.
     *
     * @return string
     */
    public function getHash(): string
    {
        if (! empty($this->md5)) {
            return $this->md5;
        }

        $this->md5 = $this->fileInfo->getMd5();

        return $this->md5;
    }

    /**
     * Gets the directory for saving the file.
     *
     * @return string
     */
    public function getStoragePath(): string
    {
        $hash = $this->getHash();

        $path = mb_substr($hash, 0, 2);
        $path .= '/' . mb_substr($hash, 2, 2);
        $path .= '/' . mb_substr($hash, 4, 2);

        return $path;
    }

    /**
     * Gets the path to the file to save
     *
     * @return string
     * @throws FilesystemException
     */
    public function getSavePath(): string
    {
        $filePathBase = ! empty($this->parentDir) ? $this->parentDir . '/' : '';

        $filePath = $filePathBase . $this->getStoragePath() . '/' . $this->getHash();

        $extension = mb_strtolower($this->fileInfo->getExtension());
        if (empty($extension)) {
            $extension = mb_strtolower(pathinfo($this->fileName, PATHINFO_EXTENSION));
        }

        if (! empty($extension)) {
            $filePath .= '.' . $extension;
        }

        $fileStorage = di(Filesystem::class)->storage($this->storage);

        $i = 1;
        while ($fileStorage->fileExists($filePath)) {
            $filePath = $filePathBase . $this->getStoragePath() . '/' . $this->getHash() . '_' . $i;
            if (! empty($extension)) {
                $filePath .= '.' . $extension;
            }
            $i++;
        }

        return $filePath;
    }

    /**
     * Saves the file and registers it in the database.
     *
     * @return Models\File
     * @throws FilesystemException
     * @psalm-suppress LessSpecificReturnStatement, MoreSpecificReturnType
     */
    public function save(): Models\File
    {
        $path = $this->getSavePath();

        $fileStorage = di(Filesystem::class)->storage($this->storage);
        $fileStorage->writeStream($path, fopen($this->sourceFile, 'rb'));

        return Models\File::query()->create(
            [
                'storage' => 'local',
                'name'    => $this->fileName ?: $this->fileInfo->getCleanName(),
                'path'    => $path,
                'size'    => $this->fileInfo->getSize(),
                'md5'     => $this->getHash(),
                'sha1'    => $this->fileInfo->getSha1(),
            ]
        );
    }
}
