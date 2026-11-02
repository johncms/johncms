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
use Johncms\Http\Request;
use Johncms\Image\EditorImageSettings;
use Johncms\Image\ImageProcessorInterface;
use Johncms\Image\StoreEditorImageUseCase;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionItemFiles;
use Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\CollectionFileRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Storage\FlysystemStorage;
use Johncms\Storage\StorageInterface;
use Johncms\Storage\StorageRegistryInterface;
use Johncms\Updates\SystemOperationContext;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
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
        $this->disk = new FlysystemStorage(new Filesystem(new InMemoryFilesystemAdapter()), baseUrl: '/upload');
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
        $this->changes = new CollectionChangeDispatcher([$this->files], transaction: new CollectionTransaction());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
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
        $this->expectException(\LogicException::class);
        $this->files->registerExisting($this->collection, $this->item, []);
    }
}
