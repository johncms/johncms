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

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\UnixVisibility\PortableVisibilityConverter;

/**
 * Builds a disk out of its settings.
 *
 * Adding a driver means adding a case here and to StorageDriver — the vendor packages behind
 * them stay optional, so a site that never touches an object store does not carry its SDK.
 */
final readonly class StorageFactory
{
    /**
     * Permissions used when the configuration names none. What the CMS has always written:
     * files the web server can read, directories it can enter.
     *
     * @var array<string, array<string, int>>
     */
    private const array DEFAULT_PERMISSIONS = [
        'file' => [
            'public'  => 0644,
            'private' => 0600,
        ],
        'dir'  => [
            'public'  => 0755,
            'private' => 0700,
        ],
    ];

    public function create(DiskSettings $settings): StorageInterface
    {
        return match ($settings->driver) {
            StorageDriver::Local => $this->createLocal($settings),
        };
    }

    private function createLocal(DiskSettings $settings): StorageInterface
    {
        if ($settings->root === '') {
            throw new UnsupportedStorageDriverException(
                sprintf('The local disk "%s" has no root directory. Set "root" in the configuration.', $settings->name)
            );
        }

        $adapter = new LocalFilesystemAdapter(
            $settings->root,
            PortableVisibilityConverter::fromArray(
                $settings->permissions === [] ? self::DEFAULT_PERMISSIONS : $settings->permissions,
                $settings->visibility
            )
        );

        return new FlysystemStorage(
            filesystem: new Filesystem($adapter),
            visibility: $settings->visibility,
            baseUrl: $settings->url,
            localRoot: rtrim($settings->root, '/\\'),
        );
    }
}
