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

/**
 * What PHP itself lets through, whatever the site has configured.
 *
 * A body larger than `post_max_size` is discarded before the script runs: `$_FILES` arrives
 * empty and nothing says why, which is how a visitor uploading a ten-megabyte photo used to get
 * "wrong data" back. The settings page reads the same limits to say when the value an
 * administrator typed is one PHP will never honour.
 */
final readonly class UploadLimits
{
    /** The largest upload PHP accepts, in bytes. Zero when neither limit is set. */
    public function maxUploadBytes(): int
    {
        $limits = array_filter([$this->bytes('upload_max_filesize'), $this->bytes('post_max_size')]);

        return $limits === [] ? 0 : min($limits);
    }

    public function uploadMaxFilesizeBytes(): int
    {
        return $this->bytes('upload_max_filesize');
    }

    public function postMaxSizeBytes(): int
    {
        return $this->bytes('post_max_size');
    }

    /**
     * Whether the request arrived with a body PHP threw away for being too large.
     *
     * Both marks have to be there: a POST that carried something (the browser sent a length)
     * and yet reached the script with neither fields nor files.
     */
    public function postMaxSizeExceeded(Request $request): bool
    {
        $limit = $this->postMaxSizeBytes();
        if ($limit <= 0 || ! $request->isMethod('POST')) {
            return false;
        }

        $length = (int) $request->server->get('CONTENT_LENGTH', 0);

        return $length > $limit && $request->request->count() === 0 && $request->files->count() === 0;
    }

    /**
     * A size of php.ini in bytes: the shorthand notation ("8M", "2G") the directives use.
     */
    private function bytes(string $directive): int
    {
        $value = trim((string) ini_get($directive));
        if ($value === '') {
            return 0;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
