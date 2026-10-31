<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Infrastructure\Server;

use FilesystemIterator;
use Johncms\Modules\Admin\Domain\Services\ServerStatusInterface;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

final readonly class LocalServerStatus implements ServerStatusInterface
{
    public function __construct(
        private PDO $db,
    ) {
    }

    /**
     * MariaDB answers through the driver of MySQL and tells itself apart only in the version string
     * ("11.8.5-MariaDB-ubu2404"); the build suffix after the number says nothing to an administrator.
     * Older MariaDB put "5.5.5-" in front for the clients that expected MySQL 5, and that is not
     * its version.
     */
    public function databaseVersion(): string
    {
        $reported = (string) preg_replace('/^5\.5\.5-/', '', (string) $this->db->getAttribute(PDO::ATTR_SERVER_VERSION));
        $version = preg_match('/\d+(?:\.\d+)*/', $reported, $match) === 1 ? $match[0] : $reported;

        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $server = match (true) {
            stripos($reported, 'mariadb') !== false => 'MariaDB',
            $driver === 'mysql' => 'MySQL',
            $driver === 'sqlite' => 'SQLite',
            default => $driver,
        };

        return $server . ' ' . $version;
    }

    public function disk(): ?array
    {
        $free = @disk_free_space(ROOT_PATH);
        $total = @disk_total_space(ROOT_PATH);

        if ($free === false || $total === false || $total <= 0) {
            return null;
        }

        return ['free' => (int) $free, 'total' => (int) $total];
    }

    public function cacheSize(): int
    {
        return $this->directorySize(CACHE_PATH);
    }

    public function logsSize(): int
    {
        return $this->directorySize(LOG_PATH);
    }

    private function directorySize(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $size = 0;

        try {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS));
            /** @var SplFileInfo $file */
            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }
        } catch (UnexpectedValueException) {
            // A directory the web server may not read: what was counted until then is the answer.
        }

        return $size;
    }
}
