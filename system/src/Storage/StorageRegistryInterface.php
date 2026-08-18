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
 * The disks of the installation, by name.
 *
 * Reach for this only where the name is not known until runtime — a row of `files` says which
 * disk its file is on, and that is the case it exists for. Code that works with a disk decided
 * at wiring time takes StorageInterface instead: asking a registry for a fixed name is a
 * service locator wearing a different hat, and it makes the dependency invisible to whoever
 * reads the constructor.
 */
interface StorageRegistryInterface
{
    /**
     * @param string|null $name Name of the disk; null asks for the default one.
     * @throws UnknownStorageDiskException
     */
    public function disk(?string $name = null): StorageInterface;
}
