<?php

declare(strict_types=1);

namespace Johncms\Files\Infrastructure;

use Johncms\Files\FilePathLockInterface;
use RuntimeException;

/**
 * A lock of the local filesystem: it holds the processes of one server, not of several behind a
 * balancer.
 *
 * Paths share a fixed set of lock files picked by their hash, so the files do not pile up with
 * the uploads; two paths meeting on one lock only wait for each other. The lock files live
 * outside CACHE_PATH, which cache:clear empties while a lock may be held.
 */
final class LocalFilePathLock implements FilePathLockInterface
{
    private const int SLOTS = 256;

    /** @var array<string, int> How deep this process already holds each slot. */
    private array $held = [];

    public function __construct(private readonly string $directory = DATA_PATH . 'locks' . DS . 'files')
    {
    }

    public function synchronized(string $storage, string $path, callable $action): mixed
    {
        $slot = sprintf('%02x', hexdec(substr(hash('sha256', $storage . "\0" . $path), 0, 4)) % self::SLOTS);
        // A lock of flock() belongs to the opened file, so taking the slot again in the same
        // process would wait for itself.
        if (isset($this->held[$slot])) {
            ++$this->held[$slot];
            try {
                return $action();
            } finally {
                --$this->held[$slot];
            }
        }

        $handle = $this->open($slot);
        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException(sprintf('Could not lock the file path slot %s.', $slot));
            }
            $this->held[$slot] = 1;
            try {
                return $action();
            } finally {
                unset($this->held[$slot]);
                flock($handle, LOCK_UN);
            }
        } finally {
            fclose($handle);
        }
    }

    /** @return resource */
    private function open(string $slot): mixed
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0777, true) && ! is_dir($this->directory)) {
            throw new RuntimeException(sprintf('Could not create the lock directory %s.', $this->directory));
        }
        $handle = fopen($this->directory . DS . $slot . '.lock', 'c');
        if ($handle === false) {
            throw new RuntimeException(sprintf('Could not open the lock file of the slot %s.', $slot));
        }

        return $handle;
    }
}
