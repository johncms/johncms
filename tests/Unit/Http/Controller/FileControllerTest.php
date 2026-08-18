<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Controller;

use Johncms\Exceptions\PageNotFoundException;
use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Http\Controller\FileController;
use Johncms\Storage\FlysystemStorage;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Tests\Unit\Files\InMemoryFileRepository;

/**
 * The way into a disk that is not public. What matters here is that the file arrives with the
 * headers a browser needs and that a missing one is a 404 rather than an empty 200.
 */
final class FileControllerTest extends TestCase
{
    private InMemoryFileRepository $files;
    private StorageInterface $disk;
    private FileController $controller;

    protected function setUp(): void
    {
        $this->files = new InMemoryFileRepository();
        // No base URL: the disk is private, which is what puts the file on this route at all.
        $this->disk = new FlysystemStorage(new Filesystem(new InMemoryFilesystemAdapter()));

        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($this->disk);
        $registry->method('defaultName')->willReturn('attachments');

        $store = new FileStore($registry, $this->files, $this->createMock(LoggerInterface::class));
        $this->controller = new FileController($store);
    }

    public function testStreamsTheFileWithItsHeaders(): void
    {
        $this->disk->store('attachments/aa/bb/cc/hash.txt', 'file contents');
        $this->files->add(5, 'attachments/aa/bb/cc/hash.txt', 13);

        $response = $this->controller->download(5);

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame('text/plain', $response->headers->get('Content-Type'));
        self::assertSame('13', $response->headers->get('Content-Length'));
        self::assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
        self::assertStringContainsString('hash.txt', (string) $response->headers->get('Content-Disposition'));
        // A file behind a controller must not be kept by a cache the whole site shares.
        self::assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));

        ob_start();
        $response->sendContent();
        self::assertSame('file contents', (string) ob_get_clean());
    }

    public function testAnUnknownFileIsANotFound(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->controller->download(404);
    }

    /**
     * A row whose file the disk will not give up is a broken installation, not a wrong address:
     * answering 404 would send whoever reports it looking for a deleted attachment instead. The
     * kernel turns this into the error page and logs it.
     */
    public function testABrokenDiskIsNotReportedAsAMissingPage(): void
    {
        $this->files->add(6, 'attachments/aa/bb/cc/gone.txt');

        $this->expectException(FileStoreException::class);

        $this->controller->download(6);
    }
}
