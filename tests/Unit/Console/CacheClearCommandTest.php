<?php

declare(strict_types=1);

namespace Tests\Unit\Console;

use Johncms\Console\Commands\CacheClearCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

final class CacheClearCommandTest extends TestCase
{
    private string $cachePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cachePath = sys_get_temp_dir() . '/johncms-cache-clear-' . uniqid('', true);
        mkdir($this->cachePath, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cachePath)) {
            $this->removeDirectory($this->cachePath);
        }

        parent::tearDown();
    }

    public function testExecuteKeepsGitkeepAndClearsCacheFiles(): void
    {
        file_put_contents($this->cachePath . '/.gitkeep', '');
        file_put_contents($this->cachePath . '/container.php', 'container');

        mkdir($this->cachePath . '/schedule', 0777, true);
        file_put_contents($this->cachePath . '/schedule/task.lock', 'lock');

        $tester = new CommandTester(new CacheClearCommand($this->cachePath));

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('Cache cleared.', $tester->getDisplay());

        self::assertFileExists($this->cachePath . '/.gitkeep');
        self::assertFileDoesNotExist($this->cachePath . '/container.php');
        self::assertDirectoryDoesNotExist($this->cachePath . '/schedule');
    }

    public function testExecuteReturnsSuccessWhenCacheDirectoryDoesNotExist(): void
    {
        rmdir($this->cachePath);

        $tester = new CommandTester(new CacheClearCommand($this->cachePath));

        $exitCode = $tester->execute([]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('does not exist', $tester->getDisplay());
    }

    private function removeDirectory(string $path): void
    {
        $items = scandir($path);
        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $path . DIRECTORY_SEPARATOR . $item;
            if (is_dir($itemPath) && ! is_link($itemPath)) {
                $this->removeDirectory($itemPath);
            } else {
                unlink($itemPath);
            }
        }

        rmdir($path);
    }
}
