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

use Johncms\FileInfo;
use League\Flysystem\FilesystemException;

class File
{
    /** @var string */
    protected $source_file;

    /** @var string */
    protected $storage = 'local';

    /** @var string */
    protected $md5 = '';

    /** @var string */
    protected $sha1 = '';

    /** @var string */
    protected $file_name = '';

    /** @var FileInfo */
    protected $file_info;

    /** @var string */
    protected $parent_dir = '';

    public function __construct(string $source_file)
    {
        if (! file_exists($source_file)) {
            throw new \RuntimeException(sprintf('File "%s" not found.', $source_file));
        }
        $this->source_file = $source_file;
        $this->file_info = new FileInfo($source_file);
    }

    /**
     * Sets the storage where the file will be saved.
     *
     * @param string $storage_name
     * @return $this
     */
    public function setStorage(string $storage_name): self
    {
        $this->storage = $storage_name;
        return $this;
    }

    /**
     * Sets the name of the file to be saved in the database.
     *
     * @param string $file_name
     * @return $this
     */
    public function setFileName(string $file_name): self
    {
        $this->file_name = $file_name;
        return $this;
    }

    /**
     * Sets the parent directory for saving.
     *
     * @param string $parent_dir
     * @return $this
     */
    public function setParentDir(string $parent_dir): self
    {
        $this->parent_dir = $parent_dir;
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

        $this->md5 = $this->file_info->getMd5();

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

        $path = '';
        $path .= mb_substr($hash, 0, 2);
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
        $file_path_base = ! empty($this->parent_dir) ? $this->parent_dir . '/' : '';

        $file_path = $file_path_base . $this->getStoragePath() . '/' . $this->getHash();

        $extension = mb_strtolower($this->file_info->getExtension());
        if (empty($extension)) {
            $extension = mb_strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        }

        if (! empty($extension)) {
            $file_path .= '.' . $extension;
        }

        $file_storage = di(Filesystem::class)->storage($this->storage);

        $i = 1;
        while ($file_storage->fileExists($file_path)) {
            $file_path = $file_path_base . $this->getStoragePath() . '/' . $this->getHash() . '_' . $i;
            if (! empty($extension)) {
                $file_path .= '.' . $extension;
            }
            $i++;
        }

        return $file_path;
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

        $file_storage = di(Filesystem::class)->storage($this->storage);
        $file_storage->writeStream($path, fopen($this->source_file, 'rb'));

        return (new Models\File())->create(
            [
                'storage' => 'local',
                'name'    => $this->file_name ?: $this->file_info->getCleanName(),
                'path'    => $path,
                'size'    => $this->file_info->getSize(),
                'md5'     => $this->getHash(),
                'sha1'    => $this->file_info->getSha1(),
            ]
        );
    }
}
