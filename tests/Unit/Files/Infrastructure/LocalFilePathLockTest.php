<?php

declare(strict_types=1);

namespace Tests\Unit\Files\Infrastructure;

use Johncms\Files\Infrastructure\LocalFilePathLock;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class LocalFilePathLockTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'file-path-lock-' . bin2hex(random_bytes(6));
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->directory . DIRECTORY_SEPARATOR . '*.lock') ?: []);
        if (is_dir($this->directory)) {
            rmdir($this->directory);
        }
    }

    public function testThePathIsHeldWhileTheActionRuns(): void
    {
        $lock = new LocalFilePathLock($this->directory);
        $result = $lock->synchronized('public', 'collections/1/ab/cd/ef/hash.png', function (): string {
            $files = glob($this->directory . DIRECTORY_SEPARATOR . '*.lock') ?: [];
            self::assertCount(1, $files);
            $other = fopen($files[0], 'c');
            self::assertFalse(flock($other, LOCK_EX | LOCK_NB), 'Another process must wait for the path.');
            fclose($other);
            return 'done';
        });
        self::assertSame('done', $result);
        $other = fopen((glob($this->directory . DIRECTORY_SEPARATOR . '*.lock') ?: [])[0], 'c');
        self::assertTrue(flock($other, LOCK_EX | LOCK_NB), 'The path is released after the action.');
        fclose($other);
    }

    public function testTheSameProcessMayTakeThePathAgain(): void
    {
        $lock = new LocalFilePathLock($this->directory);
        $result = $lock->synchronized('public', 'a.png', fn (): string => $lock->synchronized('public', 'a.png', static fn (): string => 'nested'));
        self::assertSame('nested', $result);
    }

    public function testAFailingActionReleasesThePath(): void
    {
        $lock = new LocalFilePathLock($this->directory);
        $failure = null;
        try {
            $lock->synchronized('public', 'a.png', static fn () => throw new RuntimeException('failed'));
        } catch (RuntimeException $exception) {
            $failure = $exception->getMessage();
        }
        self::assertSame('failed', $failure, 'The failure of the action must reach the caller.');
        $other = fopen((glob($this->directory . DIRECTORY_SEPARATOR . '*.lock') ?: [])[0], 'c');
        self::assertTrue(flock($other, LOCK_EX | LOCK_NB));
        fclose($other);
    }

    public function testLockFilesDoNotGrowWithThePaths(): void
    {
        $lock = new LocalFilePathLock($this->directory);
        for ($i = 0; $i < 2000; $i++) {
            $lock->synchronized('public', 'path-' . $i, static fn (): null => null);
        }
        self::assertLessThanOrEqual(256, count(glob($this->directory . DIRECTORY_SEPARATOR . '*.lock') ?: []));
    }
}
