<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\News\Application\Collections;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Files\FileStore;
use Johncms\Modules\Collections\Application\Api\CollectionFileReferencesApiInterface;
use Johncms\Modules\Collections\Application\Api\CollectionItemRelationsApi;
use Johncms\Modules\Collections\Application\Api\CollectionLookupApiInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Modules\News\Application\Collections\NewsCollectionChangeHandler;
use Johncms\Modules\News\Application\Collections\NewsDiscussionFiles;
use Johncms\Modules\News\Infrastructure\Persistence\Repository\EloquentNewsDiscussionRepository;
use Johncms\Storage\FlysystemStorage;
use Johncms\Storage\StorageRegistryInterface;
use League\Flysystem\Filesystem;
use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\RunsMigrations;
use Tests\Unit\Files\InMemoryFileRepository;

final class NewsDiscussionLifecycleTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private FileStore $files;
    private EloquentNewsDiscussionRepository $discussion;
    private CollectionChangeDispatcher $changes;
    private int $collection;
    private int $item;
    private bool $referencedByCollection = false;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        $this->migrate('news');
        $this->collection = Fixture::insert('collections', ['name' => 'News', 'code' => 'news']);
        $this->item = CollectionItemWriterFactory::create()->execute(null, $this->form('entry'));
        $disk = new FlysystemStorage(new Filesystem(new InMemoryFilesystemAdapter()), baseUrl: '/upload');
        $registry = $this->createStub(StorageRegistryInterface::class);
        $registry->method('disk')->willReturn($disk);
        $registry->method('defaultName')->willReturn('local');
        $this->files = new FileStore($registry, new InMemoryFileRepository(), new NullLogger());
        $this->discussion = new EloquentNewsDiscussionRepository();
        $references = $this->createStub(CollectionFileReferencesApiInterface::class);
        $references->method('isReferenced')->willReturnCallback(fn (): bool => $this->referencedByCollection);
        $transaction = new CollectionTransaction();
        $relations = new CollectionItemRelationsApi($transaction, new ContentCollectionRepository(), new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()));
        $cleanup = new NewsDiscussionFiles($this->files, $this->discussion, $references, $relations);
        $lookup = $this->createStub(CollectionLookupApiInterface::class);
        $lookup->method('findDeclared')->willReturn(new CollectionRecordDTO($this->collection, 'news', 'News', null, true, false, 100, [], null, null, 'johncms/news', 'articles'));
        $handler = new NewsCollectionChangeHandler($lookup, $this->discussion, $transaction, $cleanup);
        $this->changes = new CollectionChangeDispatcher([$handler], transaction: $transaction);
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testDiscussionRollsBackWithItemAndFilesAreDeletedOnlyAfterCommit(): void
    {
        $file = $this->files->storeContents('comment image', 'image.png', 'news_comments');
        $id = $this->discussion->addComment($this->item, 11, 'Text', [], [$file->id]);
        $this->discussion->vote($this->item, 11, 1);
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertSame(0, $this->discussion->countComments($this->item));
        self::assertNotNull($this->files->find($file->id));
        Capsule::connection()->rollBack();
        self::assertNotNull($this->discussion->findComment($id));
        self::assertSame(1, $this->discussion->stats([$this->item], 11)[$this->item]->rating);
        self::assertNotNull($this->files->find($file->id));
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->files->find($file->id));
        Capsule::connection()->commit();
        self::assertNull($this->files->find($file->id));
        self::assertNull($this->discussion->findComment($id));
        self::assertSame(0, $this->discussion->stats([$this->item], 11)[$this->item]->rating);
    }

    public function testSharedCommentFileSurvivesUntilItsLastReferenceIsRemoved(): void
    {
        $second = CollectionItemWriterFactory::create()->execute(null, $this->form('second'));
        $file = $this->files->storeContents('shared image', 'image.png', 'news_comments');
        $this->discussion->addComment($this->item, 11, 'Text', [], [$file->id]);
        $this->discussion->addComment($second, 11, 'Text', [], [$file->id]);
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->files->find($file->id));
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($second);
        self::assertNull($this->files->find($file->id));
    }

    public function testFilesReferencedByCollectionsOrOwnedByOtherModulesArePreserved(): void
    {
        $this->referencedByCollection = true;
        $shared = $this->files->storeContents('shared image', 'image.png', 'news_comments');
        $foreign = $this->files->storeContents('external image', 'image.png', 'guestbook');
        $this->discussion->addComment($this->item, 11, 'Text', [], [$shared->id, $foreign->id]);
        CollectionItemWriterFactory::itemDeleter($this->changes)->execute($this->item);
        self::assertNotNull($this->files->find($shared->id));
        self::assertNotNull($this->files->find($foreign->id));
    }

    private function form(string $code): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, $code, $code, true, null, null, 100, null, null, []);
    }
}
