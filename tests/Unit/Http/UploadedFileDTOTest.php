<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\UploadedFileDTO;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Tests for the framework-agnostic uploaded-file DTO.
 */
final class UploadedFileDTOTest extends TestCase
{
    /** @var list<string> */
    private array $tmpFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tmpFiles as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    public function testIsValidReflectsErrorCode(): void
    {
        self::assertTrue($this->makeDto(error: UPLOAD_ERR_OK)->isValid());
        self::assertFalse($this->makeDto(error: UPLOAD_ERR_INI_SIZE)->isValid());
    }

    public function testMoveToRelocatesTheTemporaryFile(): void
    {
        $source = $this->makeTmpFile('payload');
        $target = $this->reserveTmpPath();

        $this->makeDto(tmpPath: $source)->moveTo($target);

        self::assertFileDoesNotExist($source);
        self::assertFileExists($target);
        self::assertSame('payload', file_get_contents($target));
    }

    public function testMoveToThrowsWhenSourceIsMissing(): void
    {
        $dto = $this->makeDto(tmpPath: $this->reserveTmpPath());

        $this->expectException(RuntimeException::class);
        $dto->moveTo($this->reserveTmpPath());
    }

    public function testExposesMetadata(): void
    {
        $dto = new UploadedFileDTO('photo.jpg', 'image/jpeg', 2048, '/tmp/abc', UPLOAD_ERR_OK);

        self::assertSame('photo.jpg', $dto->clientName);
        self::assertSame('image/jpeg', $dto->mimeType);
        self::assertSame(2048, $dto->size);
        self::assertSame('/tmp/abc', $dto->tmpPath);
        self::assertSame(UPLOAD_ERR_OK, $dto->error);
    }

    private function makeDto(
        ?string $clientName = 'file.txt',
        ?string $mimeType = 'text/plain',
        ?int $size = 10,
        string $tmpPath = '/tmp/none',
        int $error = UPLOAD_ERR_OK,
    ): UploadedFileDTO {
        return new UploadedFileDTO($clientName, $mimeType, $size, $tmpPath, $error);
    }

    private function makeTmpFile(string $contents): string
    {
        $path = $this->reserveTmpPath();
        file_put_contents($path, $contents);

        return $path;
    }

    private function reserveTmpPath(): string
    {
        $path = sys_get_temp_dir() . '/johncms_upload_test_' . bin2hex(random_bytes(6));
        $this->tmpFiles[] = $path;

        return $path;
    }
}
