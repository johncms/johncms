<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms;

use Johncms\System\Legacy\Tools;
use SplFileInfo;

class FileInfo extends SplFileInfo
{

    /**
     * File name without extension
     *
     * @return string
     */
    public function getNameWithoutExtension(): string
    {
        return $this->getBasename('.' . $this->getExtension());
    }

    /**
     * Get cleared file name
     *
     * @return string|string[]|null
     */
    public function getCleanName()
    {
        $name = $this->sanitizeName($this->getNameWithoutExtension());
        $name = mb_substr($name, 0, 150) . '.' . $this->getExtension();

        return $name;
    }

    /**
     * Get public file path
     *
     * @return string
     */
    public function getPublicPath(): string
    {
        return pathToUrl($this->getRealPath());
    }

    /**
     * Removing special characters from a string
     *
     * @param string $name
     * @return string
     */
    public function sanitizeName(string $name): string
    {
        /** @var Tools $tools */
        $tools = di(Tools::class);
        $name = $tools->rusLat($name, false);

        $name = preg_replace('~[^-a-zA-Z0-9_]+~u', '_', $name);
        $name = trim($name, '_');
        $name = preg_replace('/-{2,}/', '_', $name);
        return $name;
    }

    /**
     * Removing special characters from a path
     *
     * @return string
     */
    public function getCleanPath(): string
    {
        $path_array = explode('/', $this->getPath());
        $path_array = array_map(
            function ($segment) {
                return $this->sanitizeName($segment);
            },
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
     *
     * @return bool
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
}
