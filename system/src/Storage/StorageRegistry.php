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

final class StorageRegistry implements StorageRegistryInterface
{
    /**
     * Disks are built once and kept: a local one costs little, but a driver that opens a
     * connection would pay for it on every file of a page listing twenty attachments.
     *
     * @var array<string, StorageInterface>
     */
    private array $disks = [];

    public function __construct(
        private readonly StorageSettings $settings,
        private readonly StorageFactory $factory,
    ) {
    }

    public function disk(?string $name = null): StorageInterface
    {
        $name ??= $this->settings->default;

        return $this->disks[$name] ??= $this->factory->create($this->settings->disk($name));
    }

    public function defaultName(): string
    {
        return $this->settings->default;
    }
}
