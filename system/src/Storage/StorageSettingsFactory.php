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

use ValueError;

/**
 * Reads the disks out of config/autoload/filesystem.*.php.
 *
 * A factory rather than constructor arguments wired in services.php, for the reason
 * CacheSettingsFactory gives: the container is compiled and cached, so anything resolved while
 * it is built would be frozen into that cache.
 */
final class StorageSettingsFactory
{
    public function __invoke(): StorageSettings
    {
        $defaults = new StorageSettings();
        $disks = [];

        /** @var array<string, array<string, mixed>> $configured */
        $configured = (array) config('filesystem.disks', []);
        foreach ($configured as $name => $settings) {
            $disks[(string) $name] = $this->disk((string) $name, (array) $settings);
        }

        return new StorageSettings(
            default: (string) config('filesystem.default', $defaults->default),
            disks: $disks,
        );
    }

    /**
     * @param array<string, mixed> $settings
     */
    private function disk(string $name, array $settings): DiskSettings
    {
        return new DiskSettings(
            name: $name,
            driver: $this->driver($name, $settings['driver'] ?? null),
            root: (string) ($settings['root'] ?? ''),
            url: (string) ($settings['url'] ?? ''),
            visibility: (string) ($settings['visibility'] ?? 'public'),
            permissions: (array) ($settings['permissions'] ?? []),
            options: (array) ($settings['options'] ?? []),
        );
    }

    private function driver(string $disk, mixed $configured): StorageDriver
    {
        if ($configured === null) {
            throw new UnsupportedStorageDriverException(
                sprintf('The "%s" disk has no driver. Supported drivers: %s.', $disk, $this->supported())
            );
        }

        try {
            return StorageDriver::from((string) $configured);
        } catch (ValueError) {
            throw new UnsupportedStorageDriverException(
                sprintf(
                    'Unknown storage driver "%s" configured for the "%s" disk. Supported drivers: %s.',
                    (string) $configured,
                    $disk,
                    $this->supported()
                )
            );
        }
    }

    private function supported(): string
    {
        return implode(', ', array_column(StorageDriver::cases(), 'value'));
    }
}
