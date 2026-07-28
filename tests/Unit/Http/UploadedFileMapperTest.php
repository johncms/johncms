<?php

declare(strict_types=1);

namespace Tests\Unit\Http;

use Johncms\Http\UploadedFileMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Tests for UploadedFileMapper: HttpFoundation uploaded file -> UploadedFileDTO.
 */
final class UploadedFileMapperTest extends TestCase
{
    private string $tmpFile = '';

    protected function tearDown(): void
    {
        if ($this->tmpFile !== '' && is_file($this->tmpFile)) {
            @unlink($this->tmpFile);
        }

        parent::tearDown();
    }

    public function testMapsMetadataAndTemporaryPath(): void
    {
        $this->tmpFile = tempnam(sys_get_temp_dir(), 'johncms_map_');
        file_put_contents($this->tmpFile, 'content');

        // test: true skips the is_uploaded_file() check so the file can be read outside a real upload.
        $file = new UploadedFile($this->tmpFile, 'photo.jpg', 'image/jpeg', null, true);

        $dto = (new UploadedFileMapper())->fromUploadedFile($file);

        self::assertSame('photo.jpg', $dto->clientName);
        self::assertSame('image/jpeg', $dto->mimeType);
        self::assertSame(7, $dto->size);
        self::assertSame($this->tmpFile, $dto->tmpPath);
        self::assertSame(UPLOAD_ERR_OK, $dto->error);
    }
}
