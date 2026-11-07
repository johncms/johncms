<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Auth\Authentication\AuthenticatorChain;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\PermissionResolver;
use Johncms\Auth\CurrentUser;
use Johncms\Files\FileStore;
use Johncms\Files\Infrastructure\Persistence\Repository\EloquentFileRepository;
use Johncms\Files\StoredFileDTO;
use Johncms\Http\Request;
use Johncms\Http\UploadedFileDTO;
use Johncms\Image\EditorImageSettings;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Image\StoreEditorImageUseCase;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionFieldUpload;
use Johncms\Modules\Collections\Application\Services\CollectionFilePolicy;
use Johncms\Modules\Collections\Application\Services\CollectionFileValues;
use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionFieldUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\CollectionFileRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Storage\FlysystemStorage;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use Johncms\Updates\SystemOperationContext;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use LogicException;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\FakeAuthenticator;
use Tests\Support\FakeRoleRepository;
use Tests\Support\FakeUserRepository;
use Tests\Support\Fixture;
use Tests\Support\IdentityFactory;
use Tests\Support\RunsMigrations;

final class CollectionItemFilesTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private CollectionItemFiles $files;
    private CollectionFieldUpload $fieldUploads;
    private CollectionFileValues $fileValues;
    private CollectionFileRepository $links;
    private FileStore $store;
    private StorageInterface $disk;
    private SystemOperationContext $system;
    private CollectionChangeDispatcher $changes;
    private int $collection;
    private int $item;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('system', 'initial_core_schema');
        $this->migrate('collections');
        CollectionItemWriterFactory::validator();
        $this->collection = Fixture::insert('collections', ['code' => 'files', 'name' => 'Files']);
        $this->item = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'name' => 'Item', 'code' => 'item']);
        $this->disk = new FlysystemStorage(filesystem: new Filesystem(new InMemoryFilesystemAdapter()), baseUrl: '/upload');
        $registry = $this->createStub(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($this->disk);
        $registry->method('defaultName')->willReturn('local');
        $this->store = new FileStore($registry, new EloquentFileRepository(), new NullLogger());
        $this->links = new CollectionFileRepository();
        $this->system = new SystemOperationContext();
        $access = $this->createStub(AccessCheckerInterface::class);
        $access->method('allows')->willReturn(true);
        $stack = new RequestStack();
        $stack->push(Request::create('/'));
        $user = new CurrentUser(new AuthenticatorChain([new FakeAuthenticator(IdentityFactory::user(id: 42))]), new PermissionResolver(new FakeRoleRepository()), $stack, new FakeUserRepository());
        $this->files = new CollectionItemFiles(
            $this->links, new CollectionTransaction(), new ContentCollectionRepository(),
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()),
            $this->store, new StoreEditorImageUseCase($this->store, $this->createStub(ImageProcessorInterface::class), new EditorImageSettings()),
            new EnsureCollectionContentAccessUseCase($access, new ContentCollectionRepository()), $user, $this->system
        );
        $this->fileValues = new CollectionFileValues(
            $this->links, new ContentCollectionFieldRepository(), new ContentCollectionItemValueRepository(),
            new CollectionTransaction(), $this->files, $this->store, new CollectionFilePolicy($this->store), $this->system
        );
        $this->fieldUploads = new CollectionFieldUpload(
            $this->files, $this->links, new ContentCollectionFieldRepository(), new ContentCollectionRepository(), new CollectionTransaction(),
            new CollectionFilePolicy($this->store), $this->store,
            new StoreEditorImageUseCase($this->store, $this->createStub(ImageProcessorInterface::class), new EditorImageSettings()),
            new EnsureCollectionContentAccessUseCase($access, new ContentCollectionRepository()), $user
        );
        $this->changes = new CollectionChangeDispatcher(handlers: [$this->files, $this->fileValues], transaction: new CollectionTransaction());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testFieldUploadUsesVerifiedExtensionAndDoesNotBecomeATextAttachment(): void
    {
        $field = $this->field(['allowed_mime_types' => ['text/plain'], 'max_file_bytes' => 64]);
        $token = $this->files->createContext($this->collection, $this->item);
        $file = $this->uploadField($token, 'plain content', 'dangerous.php', 'image/png');
        self::assertSame('dangerous.txt', $file->name);
        self::assertStringEndsWith('.txt', (new EloquentFileRepository())->findById($file->id)->path);
        self::assertSame($field, Capsule::table('collection_file_links')->where('file_id', $file->id)->value('field_id'));
        $form = new CollectionItemFormDTO($this->collection, null, 'item', 'Item', true, null, null, 100, null, null, ['file' => (string) $file->id], $token);
        $this->writer()->execute($this->item, $form, 42);
        self::assertSame(0, Capsule::table('collection_file_links')->where('item_id', $this->item)->count());
        self::assertNotNull($this->store->find($file->id));
        $this->writer()->execute($this->item, $this->fileForm(''));
        self::assertNull($this->store->find($file->id));
    }

    public function testUnusedFieldUploadsAreRemovedOnlyAfterCommitAndSurviveRollback(): void
    {
        $this->field();
        $token = $this->files->createContext($this->collection, $this->item);
        $file = $this->uploadField($token, 'unused content', 'unused.txt');
        $form = new CollectionItemFormDTO($this->collection, null, 'item', 'Item', true, null, null, 100, null, null, [], $token);
        Capsule::connection()->beginTransaction();
        $this->writer()->execute($this->item, $form, 42);
        self::assertNotNull($this->store->find($file->id));
        Capsule::connection()->rollBack();
        self::assertNotNull($this->links->context($token));
        self::assertNotNull($this->store->find($file->id));
        $this->writer()->execute($this->item, $form, 42);
        self::assertNull($this->store->find($file->id));
        self::assertNull($this->links->context($token));
    }

    public function testUploadChecksActualSizeAndMimeAndSchemaCannotInvalidateStoredFiles(): void
    {
        $field = $this->field(['allowed_mime_types' => ['text/plain'], 'max_file_bytes' => 32]);
        $token = $this->files->createContext($this->collection, $this->item);
        foreach ([str_repeat('x', 33), '<?php echo "unsafe";'] as $contents) {
            try {
                $this->uploadField($token, $contents, 'forged.txt', 'text/plain');
                self::fail('The content must satisfy the field policy.');
            } catch (CollectionItemFormException) {
                self::assertSame(0, Capsule::table('files')->count());
            }
        }
        $file = $this->uploadField($token, 'valid content', 'good.txt');
        $form = new CollectionItemFormDTO($this->collection, null, 'item', 'Item', true, null, null, 100, null, null, ['file' => (string) $file->id], $token);
        $this->writer()->execute($this->item, $form, 42);
        try {
            $this->field(['max_file_bytes' => 1], $field);
            self::fail('Existing file values must remain valid when limits change.');
        } catch (CollectionSchemaException) {
            self::assertSame(32, (new ContentCollectionFieldRepository())->findById($field)->settings['max_file_bytes']);
        }
        self::assertNotNull($this->store->find($file->id));
    }

    private function uploadField(string $token, string $contents, string $name, string $mime = 'application/octet-stream'): StoredFileDTO
    {
        $path = tempnam(sys_get_temp_dir(), 'collection-field-');
        file_put_contents($path, $contents);
        try {
            return $this->fieldUploads->upload($this->collection, 'file', $token, new UploadedFileDTO($name, $mime, 0, $path, UPLOAD_ERR_OK));
        } finally {
            unlink($path);
        }
    }

    public function testFileValuesKeepSharedBytesUntilTheLastReferenceAndRollbackRestoresThem(): void
    {
        $this->field();
        $source = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'source', 'name' => 'Source']);
        $file = $this->store->storeContents('file value', 'value.txt', 'collections');
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $source, [$file->id], true));
        $this->writer()->execute($this->item, $this->fileForm((string) $file->id));
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($source);
        self::assertNotNull($this->store->find($file->id));
        self::assertSame([$file->id], $this->files->registeredFileIds($this->collection, $this->item));
        Capsule::connection()->beginTransaction();
        $this->writer()->execute($this->item, $this->fileForm(''));
        self::assertNotNull($this->store->find($file->id));
        Capsule::connection()->rollBack();
        self::assertTrue($this->links->hasLinks($file->id, true));
        $this->writer()->execute($this->item, $this->fileForm(''));
        self::assertFalse($this->links->hasLinks($file->id));
        self::assertNull($this->store->find($file->id));
    }

    public function testUnrelatedRegistryEntriesCannotBeAssigned(): void
    {
        $file = $this->store->storeContents('unrelated file', 'other.txt', 'elsewhere');
        $this->field();
        $this->expectException(CollectionItemFormException::class);
        $this->writer()->execute($this->item, $this->fileForm((string) $file->id));
    }

    public function testCurrentUploadContextAuthorizesFilesOnlyForItsEditorAndItem(): void
    {
        $this->field();
        $token = $this->files->createContext($this->collection, $this->item);
        $file = $this->store->storeContents('context file', 'context.txt', 'collections');
        $this->links->register($file->id, true);
        $this->links->linkContext($token, $file->id);
        $form = new CollectionItemFormDTO($this->collection, null, 'item', 'Item', true, null, null, 100, null, null, ['file' => (string) $file->id], $token);
        try {
            $this->writer()->execute($this->item, $form, 43);
            self::fail('Another editor cannot claim the context.');
        } catch (CollectionItemFormException $exception) {
            self::assertArrayHasKey('upload_context', $exception->errors);
        }
        $this->writer()->execute($this->item, $form, 42);
        self::assertNull($this->links->context($token));
        self::assertSame((string) $file->id, CollectionItemWriterFactory::itemRepository()->findWithValues($this->item)->getValuesMap()['file']);
    }

    /** @param array<string, mixed> $settings */
    private function field(array $settings = [], ?int $id = null): int
    {
        return (new SaveCollectionFieldUseCase(
            repository: new ContentCollectionFieldRepository(), transaction: new CollectionTransaction(), collections: new ContentCollectionRepository(), items: CollectionItemWriterFactory::itemRepository(),
            changes: $this->changes, context: $this->system
        ))->execute($id, new CollectionFieldFormDTO(
            collectionId: $this->collection, code: 'file', name: 'File', type: FieldType::File, required: false, multiple: false, sort: 100, settings: $settings
        ));
    }

    private function writer(): SaveCollectionItemUseCase
    {
        return CollectionItemWriterFactory::create(context: $this->system, changes: $this->changes, fileValues: $this->fileValues, files: $this->files);
    }

    private function fileForm(?string $value = null): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, 'item', 'Item', true, null, null, 100, null, null, $value !== null ? ['file' => $value] : []);
    }

    public function testAPartialWriteLeavesFileValuesItDoesNotChangeAlone(): void
    {
        $this->field();
        $file = $this->store->storeContents('file value', 'value.txt', 'collections');
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id]));
        $this->writer()->execute($this->item, $this->fileForm((string) $file->id));
        $this->disk->delete((string) Capsule::table('files')->where('id', $file->id)->value('path'));
        $this->writer()->execute(id: $this->item, dto: $this->fileForm((string) $file->id), actorId: 42, valueFields: []);
        self::assertSame((string) $file->id, CollectionItemWriterFactory::itemRepository()->findWithValues($this->item)->getValuesMap()['file']);
        $this->expectException(CollectionItemFormException::class);
        $this->writer()->execute(id: $this->item, dto: $this->fileForm((string) $file->id), actorId: 42, valueFields: ['file']);
    }

    public function testDeletionRemovesBytesOnlyAfterTheOutermostCommit(): void
    {
        $file = $this->store->storeContents('owned image', 'photo.png', 'collections');
        $path = (new EloquentFileRepository())->findById($file->id)->path;
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id], true));
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->store->find($file->id));
        self::assertTrue($this->disk->exists($path));
        Capsule::connection()->commit();
        self::assertNull($this->store->find($file->id));
        self::assertFalse($this->disk->exists($path));
    }

    public function testRollbackRestoresReferencesAndDoesNotDeleteBytes(): void
    {
        $file = $this->store->storeContents('rollback image', 'photo.png', 'collections');
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id], true));
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertFalse($this->links->hasLinks($file->id));
        Capsule::connection()->rollBack();
        self::assertTrue($this->links->hasLinks($file->id));
        self::assertNotNull($this->store->find($file->id));
    }

    public function testAFileShownInTheTextOfAnotherRecordOutlivesTheRecordItWasUploadedFor(): void
    {
        $file = $this->store->storeContents('pasted image', 'photo.png', 'collections');
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id], true));
        $text = '<p><img src="' . $file->url . '"></p>';
        $reader = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'reader', 'name' => 'Reader', 'detail_text' => $text]);
        $other = Fixture::insert('collections', ['code' => 'other-files', 'name' => 'Other']);
        $preview = Fixture::insert('collection_items', ['collection_id' => $other, 'code' => 'preview', 'name' => 'Preview', 'preview_text' => $text]);
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->store->find($file->id));
        self::assertSame([$file->id], $this->links->itemFiles($reader));
        self::assertSame([$file->id], $this->links->itemFiles($preview));
        Capsule::table('collection_items')->where('id', $preview)->update(['preview_text' => null]);
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($preview);
        self::assertNotNull($this->store->find($file->id));
        Capsule::table('collection_items')->where('id', $reader)->update(['detail_text' => null]);
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($reader);
        self::assertNull($this->store->find($file->id));
    }

    public function testSharedFileSurvivesUntilItsLastItemIsDeleted(): void
    {
        $second = Fixture::insert('collection_items', ['collection_id' => $this->collection, 'code' => 'second', 'name' => 'Second']);
        $file = $this->store->storeContents('shared image', 'photo.png', 'collections');
        $this->system->runTrusted(function () use ($second, $file): void {
            $this->files->registerExisting($this->collection, $this->item, [$file->id], true);
            $this->files->registerExisting($this->collection, $second, [$file->id]);
        });
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->store->find($file->id));
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($second);
        self::assertNull($this->store->find($file->id));
    }

    public function testRegistrationDoesNotCopyBytesAndRollsBackWithTheImport(): void
    {
        $file = $this->store->storeContents('external image', 'photo.png', 'news');
        $path = (new EloquentFileRepository())->findById($file->id)->path;
        Capsule::connection()->beginTransaction();
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id]));
        self::assertTrue($this->links->hasLinks($file->id));
        self::assertSame('external image', $this->disk->read($path));
        Capsule::connection()->rollBack();
        self::assertFalse($this->links->hasLinks($file->id));
        self::assertNotNull($this->store->find($file->id));
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id]));
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->store->find($file->id));
        self::assertTrue($this->disk->exists($path));
    }

    public function testCancelledUploadsExpireWithoutDeletingLinkedFilesAndRollbackCancelsCleanup(): void
    {
        $token = $this->files->createContext($this->collection);
        $file = $this->store->storeContents('temporary image', 'photo.png', 'collections');
        $this->links->register($file->id, true);
        $this->links->linkContext($token, $file->id);
        Capsule::table('collection_upload_contexts')->where('token', $token)->update(['created_at' => '2000-01-01 00:00:00']);
        Capsule::connection()->beginTransaction();
        self::assertSame(1, $this->files->cleanup());
        self::assertNotNull($this->store->find($file->id));
        Capsule::connection()->rollBack();
        self::assertNotNull($this->links->context($token));
        self::assertSame(1, $this->files->cleanup());
        self::assertNull($this->store->find($file->id));
        self::assertSame(0, $this->files->cleanup());
    }

    public function testCleanupRetainsFilesLinkedToItemsAndLeavesFreshContextsAlone(): void
    {
        $expired = $this->files->createContext($this->collection);
        $fresh = $this->files->createContext($this->collection);
        $file = $this->store->storeContents('shared temporary image', 'photo.png', 'collections');
        $this->links->register($file->id, true);
        $this->links->linkContext($expired, $file->id);
        $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id]));
        Capsule::table('collection_upload_contexts')->where('token', $expired)->update(['created_at' => '2000-01-01 00:00:00']);
        self::assertSame(1, $this->files->cleanup());
        self::assertNotNull($this->store->find($file->id));
        self::assertNotNull($this->links->context($fresh));
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNull($this->store->find($file->id));
    }

    public function testInvalidImportRollsBackEveryFileRegistration(): void
    {
        $file = $this->store->storeContents('existing image', 'photo.png', 'news');
        try {
            $this->system->runTrusted(fn () => $this->files->registerExisting($this->collection, $this->item, [$file->id, 999999]));
            self::fail('Missing files must reject the entire registration.');
        } catch (CollectionItemFormException) {
            self::assertFalse($this->links->hasLinks($file->id));
            self::assertSame(0, Capsule::table('collection_file_assets')->count());
            self::assertNotNull($this->store->find($file->id));
        }
    }

    public function testFileRegistrationRequiresTrustedSystemContext(): void
    {
        $this->expectException(LogicException::class);
        $this->files->registerExisting($this->collection, $this->item, []);
    }
}
