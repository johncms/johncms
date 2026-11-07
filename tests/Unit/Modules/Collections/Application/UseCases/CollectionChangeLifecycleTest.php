<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use DateTimeImmutable;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\AfterCommitException;
use Johncms\Database\PdoConnection;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;
use Johncms\Modules\Collections\Application\Services\CollectionCacheInvalidator;
use Johncms\Modules\Collections\Application\Services\CollectionStructureMemo;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Application\Services\ReservedCodeCheckerInterface;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionUseCase;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Updates\SystemOperationContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\InMemoryCache;
use Tests\Support\RecordingCollectionChangeHandler;
use Tests\Support\RunsMigrations;

final class CollectionChangeLifecycleTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private int $collection;
    private RecordingCollectionChangeHandler $handler;
    private CollectionChangeDispatcher $changes;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        CollectionItemWriterFactory::validator();
        $this->collection = Fixture::insert('collections', ['code' => 'blog', 'name' => 'Blog']);
        $this->handler = new RecordingCollectionChangeHandler();
        $this->changes = new CollectionChangeDispatcher(handlers: [$this->handler], transaction: new CollectionTransaction());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    private function form(?int $section = null): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, $section, 'item', 'Item', true, null, null, 100, null, 'Body', []);
    }

    public function testApiSavePatchAndMoveProvideOldAndNewStates(): void
    {
        $api = CollectionItemWriterFactory::api($this->changes);
        $id = $api->addItem($this->form());
        $api->patchItem($id, new CollectionItemPatchDTO(attributes: ['active' => false]));
        $section = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'target', 'name' => 'Target']);
        $api->patchItem($id, new CollectionItemPatchDTO(attributes: ['sectionId' => $section]));
        self::assertCount(3, $this->handler->within);
        self::assertCount(3, $this->handler->committed);
        self::assertNull($this->handler->within[0]->before);
        self::assertTrue($this->handler->within[1]->before->active);
        self::assertFalse($this->handler->within[1]->after->active);
        self::assertSame('moved', $this->handler->within[2]->action);
        self::assertNull($this->handler->within[2]->before->containerId);
        self::assertSame($section, $this->handler->within[2]->after->containerId);
    }

    public function testSubtreeMoveEmitsItemMovesAndSectionDeletion(): void
    {
        $source = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'source', 'name' => 'Source']);
        $target = Fixture::insert('collection_sections', ['collection_id' => $this->collection, 'code' => 'target', 'name' => 'Target']);
        $id = CollectionItemWriterFactory::api()->addItem($this->form($source));
        CollectionItemWriterFactory::sectionsApi($this->changes)->deleteSection($this->collection, $source, SectionDeletionMode::MoveItems, $target);
        self::assertSame(['item:moved', 'section:deleted'], array_map(static fn ($change) => $change->entity . ':' . $change->action, $this->handler->within));
        self::assertSame($id, $this->handler->within[0]->id);
        self::assertSame($source, $this->handler->within[0]->before->containerId);
        self::assertSame($target, $this->handler->within[0]->after->containerId);
    }

    public function testRequiredRelatedCleanupRollsBackWithTheOriginalMutation(): void
    {
        Capsule::connection()->statement('CREATE TABLE collection_external (item_id INTEGER PRIMARY KEY)');
        $id = CollectionItemWriterFactory::api()->addItem($this->form());
        Capsule::table('collection_external')->insert(['item_id' => $id]);
        $related = new class implements CollectionChangeHandlerInterface {
            public function withinTransaction(CollectionChangeDTO $change): void
            {
                if ($change->entity === 'item' && $change->action === 'deleted') {
                    (new PdoConnection(Capsule::connection()->getPdo()))->execute('DELETE FROM collection_external WHERE item_id = ?', [$change->id]);
                }
            }

            public function afterCommit(CollectionChangeDTO $change): void
            {
            }
        };
        $this->handler->failWithin = true;
        $changes = new CollectionChangeDispatcher(handlers: [$related, $this->handler], transaction: new CollectionTransaction());
        try {
            CollectionItemWriterFactory::itemDeleter($changes)->execute($id);
            self::fail('The required handler failure must abort deletion.');
        } catch (RuntimeException $exception) {
            self::assertSame('Required collection handler failed.', $exception->getMessage());
        }
        self::assertNotNull(CollectionItemWriterFactory::api()->getItem($id));
        self::assertSame(1, Capsule::table('collection_external')->count());
        self::assertSame([], $this->handler->committed);
        $this->handler->failWithin = false;
        CollectionItemWriterFactory::itemDeleter($changes)->execute($id);
        self::assertNull(CollectionItemWriterFactory::api()->getItem($id));
        self::assertSame(0, Capsule::table('collection_external')->count());
    }

    public function testImportSuppressesDerivedHandlersButKeepsTransactionalHandlers(): void
    {
        $context = new SystemOperationContext();
        $context->runTrusted(fn () => CollectionItemWriterFactory::create(context: $context, changes: $this->changes)->execute(id: null, dto: $this->form(), history: new CollectionItemHistoryDTO(
            new DateTimeImmutable('2001-01-01Z'), new DateTimeImmutable('2002-01-01Z')
        )));
        $context->runTrusted(fn () => CollectionItemWriterFactory::sectionWriter($context, $this->changes)->execute(null,
            new CollectionSectionFormDTO($this->collection, null, 'section', 'Section', null, true, 100),
            new DateTimeImmutable('2001-01-01Z'), new DateTimeImmutable('2002-01-01Z')
        ));
        self::assertCount(2, $this->handler->within);
        self::assertTrue($this->handler->within[0]->imported);
        self::assertTrue($this->handler->within[1]->imported);
        self::assertSame([], $this->handler->committed);
    }

    public function testCodeMapDoesNotCacheUncommittedChanges(): void
    {
        $cache = InMemoryCache::create();
        $codes = new CollectionCodeCache($cache, new ContentCollectionRepository());
        self::assertSame(['blog' => $this->collection], $codes->map());
        $checker = new class implements ReservedCodeCheckerInterface {
            public function isReserved(string $code): bool
            {
                return false;
            }
        };
        $save = new SaveCollectionUseCase(new ContentCollectionRepository(), $codes, $checker, new CollectionTransaction());
        Capsule::connection()->beginTransaction();
        $save->execute($this->collection, new CollectionFormDTO('changed', 'Blog', null, true, true, 100, true, 20));
        self::assertSame(['changed' => $this->collection], $codes->map());
        Capsule::connection()->rollBack();
        self::assertSame(['blog' => $this->collection], $codes->map());
    }

    public function testOuterRollbackDoesNotRunDerivedCollectionHandlers(): void
    {
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::api($this->changes)->addItem($this->form());
        self::assertCount(1, $this->handler->within);
        self::assertSame([], $this->handler->committed);
        Capsule::connection()->rollBack();
        self::assertCount(0, $this->handler->committed);
        self::assertSame(0, Capsule::table('collection_items')->count());
    }

    public function testDerivedFailureIsReportedAfterDataWasCommitted(): void
    {
        $this->handler->failAfter = true;
        try {
            CollectionItemWriterFactory::api($this->changes)->addItem($this->form());
            self::fail('The derived failure must be reported.');
        } catch (AfterCommitException $exception) {
            self::assertStringContainsString('Derived collection handler failed.', $exception->getMessage());
        }
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertFalse(Capsule::connection()->getPdo()->inTransaction());
    }

    public function testInvalidationIsTargetedAndWaitsForCommit(): void
    {
        $cache = InMemoryCache::create();
        $codes = new CollectionCodeCache($cache, new ContentCollectionRepository());
        $invalidator = new CollectionCacheInvalidator($cache, $codes, new CollectionStructureMemo());
        $changes = new CollectionChangeDispatcher(handlers: [$invalidator], transaction: new CollectionTransaction());
        $cache->rememberForever('affected', static fn () => 'old', [CollectionCacheInvalidator::tag($this->collection)]);
        $cache->rememberForever('other', static fn () => 'unrelated', [CollectionCacheInvalidator::tag($this->collection + 1)]);
        Capsule::connection()->beginTransaction();
        CollectionItemWriterFactory::api($changes)->addItem($this->form());
        self::assertSame('old', $cache->get('affected'));
        Capsule::connection()->rollBack();
        self::assertTrue($cache->has('affected'));
        CollectionItemWriterFactory::api($changes)->addItem($this->form());
        self::assertNull($cache->get('affected'));
        self::assertSame('unrelated', $cache->get('other'));
    }
}
