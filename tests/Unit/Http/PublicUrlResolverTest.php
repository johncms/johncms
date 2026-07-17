<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\PublicUrlResolver;
use PHPUnit\Framework\TestCase;

final class PublicUrlResolverTest extends TestCase
{
    private string $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir() . '/johncms-public-url-resolver-' . uniqid('', true);
        mkdir($this->baseDir . '/upload/users', 0777, true);
        touch($this->baseDir . '/upload/users/avatar.png');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->baseDir);
    }

    public function testReturnsUrlRelativeToTheBasePath(): void
    {
        $resolver = new PublicUrlResolver();

        self::assertSame(
            '/upload/users/avatar.png',
            $resolver->fromPath($this->baseDir . '/upload/users/avatar.png', $this->baseDir)
        );
    }

    public function testKeepsDirectoriesRepeatingASegmentOfTheBasePath(): void
    {
        mkdir($this->baseDir . '/upload/' . basename($this->baseDir));
        touch($this->baseDir . '/upload/' . basename($this->baseDir) . '/photo.jpg');

        $resolver = new PublicUrlResolver();

        self::assertSame(
            '/upload/' . basename($this->baseDir) . '/photo.jpg',
            $resolver->fromPath(
                $this->baseDir . '/upload/' . basename($this->baseDir) . '/photo.jpg',
                $this->baseDir
            )
        );
    }

    public function testResolvesRelativeSegmentsOfThePath(): void
    {
        $resolver = new PublicUrlResolver();

        self::assertSame(
            '/upload/users/avatar.png',
            $resolver->fromPath($this->baseDir . '/upload/../upload/users/avatar.png', $this->baseDir)
        );
    }

    public function testReturnsEmptyStringForAPathOutsideOfTheBasePath(): void
    {
        $resolver = new PublicUrlResolver();

        self::assertSame('', $resolver->fromPath($this->baseDir . '/upload/../..', $this->baseDir));
    }

    public function testReturnsEmptyStringForASiblingDirectorySharingTheBasePathPrefix(): void
    {
        $sibling = $this->baseDir . '-sibling';
        mkdir($sibling);
        touch($sibling . '/secret.txt');

        $resolver = new PublicUrlResolver();

        $result = $resolver->fromPath($sibling . '/secret.txt', $this->baseDir);

        $this->removeDirectory($sibling);

        self::assertSame('', $result);
    }

    public function testReturnsEmptyStringForAMissingFile(): void
    {
        $resolver = new PublicUrlResolver();

        self::assertSame('', $resolver->fromPath($this->baseDir . '/upload/missing.png', $this->baseDir));
    }

    public function testAcceptsABasePathWithATrailingSeparator(): void
    {
        $resolver = new PublicUrlResolver();

        self::assertSame(
            '/upload/users/avatar.png',
            $resolver->fromPath($this->baseDir . '/upload/users/avatar.png', $this->baseDir . '/')
        );
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($directory);
    }
}
