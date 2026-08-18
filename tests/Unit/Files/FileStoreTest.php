<?php

declare(strict_types=1);

namespace Tests\Unit\Files;

use Johncms\Files\FileStore;
use Johncms\Files\FileStoreException;
use Johncms\Http\UploadedFileDTO;
use Johncms\Storage\FlysystemStorage;
use Johncms\Storage\StorageException;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * What FileStore is for is keeping the disk and the registry in step, so these tests are mostly
 * about the halfway states: a file written whose row cannot be inserted, a row deleted whose
 * file will not go.
 */
final class FileStoreTest extends TestCase
{
    private InMemoryFileRepository $files;
    private StorageInterface $disk;
    private LoggerInterface&MockObject $logger;
    private FileStore $store;
    private string $directory;

    protected function setUp(): void
    {
        $this->files = new InMemoryFileRepository();
        $this->disk = new FlysystemStorage(
            filesystem: new Filesystem(new InMemoryFilesystemAdapter()),
            baseUrl: '/upload',
        );
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->store = new FileStore($this->registry($this->disk), $this->files, $this->logger);

        $this->directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'johncms-store-' . bin2hex(random_bytes(6));
        mkdir($this->directory, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . DIRECTORY_SEPARATOR . '*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);
    }

    public function testStoresTheFileAndRegistersIt(): void
    {
        $source = $this->localFile('photo.jpg', 'picture bytes');

        $stored = $this->store->storeLocalFile($source, 'guestbook');

        $row = $this->files->findById($stored->id);
        self::assertNotNull($row);
        self::assertSame('photo.jpg', $stored->name);
        self::assertSame(13, $stored->size);
        self::assertSame(md5('picture bytes'), $row->md5);
        self::assertSame(sha1('picture bytes'), $row->sha1);
        self::assertTrue($this->disk->exists($row->path));
        self::assertSame('picture bytes', $this->disk->read($row->path));
    }

    public function testThePathIsBuiltFromTheHashUnderTheGivenDirectory(): void
    {
        $hash = md5('picture bytes');
        $source = $this->localFile('photo.jpg', 'picture bytes');

        $stored = $this->store->storeLocalFile($source, 'guestbook');

        $expected = sprintf(
            'guestbook/%s/%s/%s/%s.jpg',
            substr($hash, 0, 2),
            substr($hash, 2, 2),
            substr($hash, 4, 2),
            $hash
        );
        self::assertSame($expected, $this->files->findById($stored->id)?->path);
    }

    public function testTheUrlComesFromTheDisk(): void
    {
        $stored = $this->store->storeLocalFile($this->localFile('photo.jpg', 'picture bytes'), 'guestbook');

        self::assertSame('/upload/' . $this->files->findById($stored->id)?->path, $stored->url);
    }

    public function testTheDiskTheFileWentToIsRecorded(): void
    {
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($this->disk);
        $registry->method('defaultName')->willReturn('archive');
        $store = new FileStore($registry, $this->files, $this->logger);

        $stored = $store->storeLocalFile($this->localFile('photo.jpg', 'picture bytes'), 'guestbook');

        // The previous implementation wrote a hard-coded 'local' here, so a second disk would
        // have left every row pointing at the wrong one.
        self::assertSame('archive', $this->files->findById($stored->id)?->storage);
    }

    /**
     * The path is the hash of the contents, so the same file uploaded twice is the same path.
     * It gets a row of its own — two posts attach it independently — but the bytes are written
     * once.
     */
    public function testTheSameContentsAreStoredOnceAndRegisteredTwice(): void
    {
        $first = $this->store->storeLocalFile($this->localFile('a.jpg', 'same bytes'), 'guestbook');
        $second = $this->store->storeLocalFile($this->localFile('b.jpg', 'same bytes'), 'guestbook');

        self::assertNotSame($first->id, $second->id);
        self::assertSame(
            $this->files->findById($first->id)?->path,
            $this->files->findById($second->id)?->path
        );
    }

    public function testAFailedRegistrationRemovesTheFileItJustWrote(): void
    {
        $this->files->failOnCreate = new RuntimeException('the database is down');

        try {
            $this->store->storeLocalFile($this->localFile('photo.jpg', 'picture bytes'), 'guestbook');
            self::fail('Storing must not report success when the row could not be written.');
        } catch (FileStoreException $exception) {
            self::assertStringContainsString('the database is down', $exception->getMessage());
        }

        $hash = md5('picture bytes');
        $path = sprintf('guestbook/%s/%s/%s/%s.jpg', substr($hash, 0, 2), substr($hash, 2, 2), substr($hash, 4, 2), $hash);
        self::assertFalse($this->disk->exists($path), 'The orphaned file was left on the disk.');
    }

    /**
     * The compensation must not reach further than the call: an identical file that was already
     * there belongs to the rows registered before this one.
     */
    public function testAFailedRegistrationKeepsAFileThatWasAlreadyThere(): void
    {
        $first = $this->store->storeLocalFile($this->localFile('a.jpg', 'same bytes'), 'guestbook');
        $path = (string) $this->files->findById($first->id)?->path;

        $this->files->failOnCreate = new RuntimeException('the database is down');

        try {
            $this->store->storeLocalFile($this->localFile('b.jpg', 'same bytes'), 'guestbook');
        } catch (FileStoreException) {
        }

        self::assertTrue($this->disk->exists($path));
    }

    public function testStoringAnUploadThatFailedIsRefused(): void
    {
        $upload = new UploadedFileDTO(
            clientName: 'photo.jpg',
            mimeType: 'image/jpeg',
            size: 0,
            tmpPath: '',
            error: UPLOAD_ERR_INI_SIZE,
        );

        $this->expectException(FileStoreException::class);

        $this->store->storeUpload($upload, 'guestbook');
    }

    public function testUploadsAreStoredUnderTheNameTheyWereUploadedWith(): void
    {
        $upload = $this->upload('holiday photo.JPG', 'picture bytes');

        $stored = $this->store->storeUpload($upload, 'guestbook');

        self::assertSame('holiday photo.JPG', $stored->name);
        // The extension ends up in a path, so it is reduced to letters and digits and lowercased.
        self::assertStringEndsWith('.jpg', (string) $this->files->findById($stored->id)?->path);
    }

    public function testUploadsTheBrowserFailedToSendAreSkipped(): void
    {
        $stored = $this->store->storeUploads(
            [
                $this->upload('good.jpg', 'picture bytes'),
                new UploadedFileDTO('bad.jpg', 'image/jpeg', 0, '', UPLOAD_ERR_NO_FILE),
            ],
            'guestbook'
        );

        self::assertCount(1, $stored);
        self::assertSame('good.jpg', $stored[0]->name);
    }

    public function testDeleteRemovesTheRowAndTheFile(): void
    {
        $stored = $this->store->storeLocalFile($this->localFile('photo.jpg', 'picture bytes'), 'guestbook');
        $path = (string) $this->files->findById($stored->id)?->path;

        $this->store->delete($stored->id);

        self::assertNull($this->files->findById($stored->id));
        self::assertFalse($this->disk->exists($path));
    }

    public function testDeletingSomethingThatIsNotRegisteredIsSilent(): void
    {
        $this->store->delete(404);

        self::assertSame([], $this->files->ids());
    }

    public function testDeleteManyIgnoresWhatIsNotAnIdentifier(): void
    {
        $stored = $this->store->storeLocalFile($this->localFile('photo.jpg', 'picture bytes'), 'guestbook');

        // Attachments come out of a JSON column, so the array may hold anything.
        $this->store->deleteMany([$stored->id, 'not an id', null, '']);

        self::assertSame([], $this->files->ids());
    }

    /**
     * The row is gone by then, so the site sees the file as deleted; what is left is a file
     * nobody points at, and that belongs in the log rather than in the face of the visitor.
     */
    public function testADiskThatCannotDeleteIsLoggedRatherThanThrown(): void
    {
        $failing = $this->createMock(StorageInterface::class);
        $failing->method('delete')->willThrowException(new StorageException('permission denied'));
        $this->logger->expects(self::once())->method('error');

        $store = new FileStore($this->registry($failing), $this->files, $this->logger);
        $this->files->add(7, 'guestbook/aa/bb/cc/file.jpg');

        $store->delete(7);

        self::assertNull($this->files->findById(7));
    }

    public function testFilterIdsInDirectoryKeepsOnlyTheFilesOfThatDirectory(): void
    {
        $this->files->add(1, 'forum_files/aa/bb/cc/mine.jpg');
        $this->files->add(2, 'news/aa/bb/cc/someone-elses.jpg');

        self::assertSame([1], $this->store->filterIdsInDirectory([1, 2, '3'], 'forum_files'));
    }

    private function registry(StorageInterface $disk): StorageRegistryInterface&MockObject
    {
        $registry = $this->createMock(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($disk);
        $registry->method('defaultName')->willReturn('local');

        return $registry;
    }

    private function localFile(string $name, string $contents): string
    {
        $path = $this->directory . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, $contents);

        return $path;
    }

    private function upload(string $clientName, string $contents): UploadedFileDTO
    {
        // A genuine upload has no extension on its temporary path, which is why the extension is
        // taken from the client name.
        $path = $this->directory . DIRECTORY_SEPARATOR . uniqid('php', true);
        file_put_contents($path, $contents);

        return new UploadedFileDTO(
            clientName: $clientName,
            mimeType: 'image/jpeg',
            size: strlen($contents),
            tmpPath: $path,
            error: UPLOAD_ERR_OK,
        );
    }
}
