<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Http;

use Illuminate\Support\Str;

/**
 * Maps a filesystem path of a web-accessible file to the URL it is served at.
 */
final class PublicUrlResolver
{
    /**
     * Returns the URL of the file, or an empty string if the file does not
     * exist or is located outside of the base path.
     *
     * @param string $path Filesystem path of the file.
     * @param string|null $basePath Document root the URL is relative to. Defaults to PUBLIC_PATH.
     */
    public function fromPath(string $path, ?string $basePath = null): string
    {
        $realPath = realpath($path);
        $realBase = realpath($basePath ?? PUBLIC_PATH);

        if ($realPath === false || $realBase === false) {
            return '';
        }

        $realBase = rtrim($realBase, DIRECTORY_SEPARATOR);

        if (! str_starts_with($realPath, $realBase . DIRECTORY_SEPARATOR)) {
            return '';
        }

        return str_replace(DIRECTORY_SEPARATOR, '/', Str::after($realPath, $realBase));
    }
}
