<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Storage;

/**
 * What one disk is built from, resolved out of config once per process by
 * StorageSettingsFactory.
 *
 * A value object rather than an array: a test builds one instead of rewriting the
 * configuration, and a typo in a key fails where it is written instead of where it is read.
 */
final readonly class DiskSettings
{
    /**
     * @param string        $name        Key of the disk in the configuration; also what is
     *                                   stored in `files.storage`.
     * @param StorageDriver $driver      What the disk is built on.
     * @param string        $root        Root directory of a local disk.
     * @param string        $url         Base URL the files are served at, empty for a disk that
     *                                   is not public.
     * @param string        $visibility  Visibility given to what is written: `public` or
     *                                   `private`. It is a property of the disk, not of a single
     *                                   file — one less thing every call site has to decide.
     * @param array<string, array<string, int>> $permissions Permission map of a local disk, in
     *                                   the shape PortableVisibilityConverter takes.
     * @param array<string, mixed> $options Driver-specific settings (credentials, region, …).
     */
    public function __construct(
        public string $name,
        public StorageDriver $driver,
        public string $root = '',
        public string $url = '',
        public string $visibility = 'public',
        public array $permissions = [],
        public array $options = [],
    ) {
    }
}
