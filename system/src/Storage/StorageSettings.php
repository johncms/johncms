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
 * The disks of the installation and which of them is the default one.
 */
final readonly class StorageSettings
{
    /**
     * @param string $default Name of the disk used when a caller names none.
     * @param array<string, DiskSettings> $disks Disks by name.
     */
    public function __construct(
        public string $default = 'local',
        public array $disks = [],
    ) {
    }

    /**
     * @throws UnknownStorageDiskException
     */
    public function disk(string $name): DiskSettings
    {
        return $this->disks[$name] ?? throw new UnknownStorageDiskException(
            sprintf(
                'The "%s" disk is not configured. Configured disks: %s.',
                $name,
                $this->disks === [] ? 'none' : implode(', ', array_keys($this->disks))
            )
        );
    }
}
