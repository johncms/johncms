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

use Illuminate\Support\Str;
use SplFileInfo;

use function mb_strtolower;
use function mb_substr;

class FileInfo extends SplFileInfo
{
    /**
     * File name without extension
     */
    public function getNameWithoutExtension(): string
    {
        return $this->getBasename('.' . $this->getExtension());
    }

    /**
     * Get cleared file name
     */
    public function getCleanName(): string
    {
        $name = $this->sanitizeName($this->getNameWithoutExtension());
        $name = mb_substr($name, 0, 150);

        $extension = mb_strtolower($this->getExtension());
        if (! empty($extension)) {
            $name .= '.' . $extension;
        }

        return $name;
    }

    /**
     * Get public file path
     */
    public function getPublicPath(): string
    {
        return pathToUrl($this->getRealPath());
    }

    /**
     * Removing special characters from a string
     */
    public function sanitizeName(string $name): string
    {
        $name = Str::ascii($name);
        $name = preg_replace('~[^-a-zA-Z0-9_]+~u', '_', $name);
        $name = trim($name, '_');
        return preg_replace('/-{2,}/', '_', $name);
    }

    /**
     * Removing special characters from a path
     */
    public function getCleanPath(): string
    {
        $path_array = explode('/', $this->getPath());
        $path_array = array_map(
            fn($segment) => $this->sanitizeName($segment),
            $path_array
        );

        $path = implode('/', $path_array);
        $path .= ! empty($path) ? '/' : '';
        $path .= $this->sanitizeName($this->getNameWithoutExtension());

        if (! empty($this->getExtension())) {
            $path .= '.' . $this->getExtension();
        }

        return $path;
    }

    /**
     * Is this file an image or not?
     */
    public function isImage(): bool
    {
        $picture_extensions = [
            'gif',
            'jpg',
            'jpeg',
            'png',
        ];
        $file_extension = strtolower($this->getExtension());
        return in_array($file_extension, $picture_extensions);
    }

    /**
     * Getting the md5 hash of the file.
     */
    public function getMd5(): string
    {
        return md5_file($this->getRealPath());
    }

    /**
     * Getting the sha1 hash of the file.
     */
    public function getSha1(): string
    {
        return sha1_file($this->getRealPath());
    }
}
