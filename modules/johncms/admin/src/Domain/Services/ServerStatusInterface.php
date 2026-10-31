<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Domain\Services;

/**
 * Facts about the machine the site runs on, for the system block of the dashboard.
 */
interface ServerStatusInterface
{
    /**
     * The database server by name and version: "MariaDB 11.8.5", "MySQL 8.4.2".
     */
    public function databaseVersion(): string;

    /**
     * The free and the total bytes of the disk the site lies on; null where the host does not tell.
     *
     * @return array{free: int, total: int}|null
     */
    public function disk(): ?array;

    /**
     * The cache of the site in bytes: what clearing it would free.
     */
    public function cacheSize(): int;

    public function logsSize(): int;
}
