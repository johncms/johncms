<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\Services;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Content\ContentRendererInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemHistoryDTO;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionSearchIndexer;
use Johncms\Modules\Collections\Application\Services\ItemContentFormatter;
use Johncms\Modules\Collections\Domain\Query\CollectionTextSearchDTO;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\CollectionSearchRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionFieldRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use Johncms\Updates\SystemOperationContext;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\RunsMigrations;

final class CollectionSearchIndexerTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private CollectionSearchIndexer $index;
    private CollectionChangeDispatcher $changes;
    private int $collection;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        $this->collection = Fixture::insert('collections', ['code' => 'search', 'name' => 'Search', 'active' => 1]);
        $renderer = $this->createStub(ContentRendererInterface::class);
        $renderer->method('toPlainText')->willReturnArgument(0);
        $this->index = new CollectionSearchIndexer(
            new CollectionSearchRepository(), new CollectionTransaction(), new ContentCollectionRepository(),
            new ContentCollectionItemRepository(new ContentCollectionFieldRepository(), new ContentCollectionItemQueryCompiler()),
            new ItemContentFormatter($renderer)
        );
        $this->changes = new CollectionChangeDispatcher([$this->index], transaction: new CollectionTransaction());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testIndexIsUpdatedOnlyAfterCommitAndRollbackDiscardsPendingChanges(): void
    {
        $api = CollectionItemWriterFactory::api($this->changes);
        Capsule::connection()->beginTransaction();
        $api->addItem($this->form('rolled-back'));
        self::assertSame(0, $this->documentCount());
        Capsule::connection()->rollBack();
        self::assertSame(0, $this->documentCount());
        Capsule::connection()->beginTransaction();
        $id = $api->addItem($this->form('committed'));
        self::assertSame(0, $this->documentCount());
        Capsule::connection()->commit();
        $query = new ContentCollectionItemQuery($this->collection, search: new CollectionTextSearchDTO('needle'));
        self::assertSame([$id], array_map(static fn ($item) => $item->id, $api->getItems($query)));
        Capsule::connection()->beginTransaction();
        $api->deleteItem($id);
        self::assertGreaterThan(0, $this->documentCount());
        Capsule::connection()->rollBack();
        self::assertSame(1, $api->countItems($query));
        $api->deleteItem($id);
        self::assertSame(0, $this->documentCount());
    }

    public function testHistoricalImportSkipsIndexAndCallerCanRebuildInsideTheDataTransaction(): void
    {
        $context = new SystemOperationContext();
        $writer = CollectionItemWriterFactory::create(context: $context, changes: $this->changes);
        $date = new \DateTimeImmutable('2001-01-01 00:00:00 UTC');
        $context->runTrusted(fn () => $writer->execute(null, $this->form('imported'), history: new CollectionItemHistoryDTO($date, $date)));
        self::assertSame(0, $this->documentCount());
        Capsule::connection()->beginTransaction();
        $this->index->rebuildCollection($this->collection);
        self::assertGreaterThan(0, $this->documentCount());
        Capsule::connection()->rollBack();
        self::assertSame(0, $this->documentCount());
        $this->index->rebuildAll();
        self::assertGreaterThan(0, $this->documentCount());
    }

    /** @phpstan-impure */
    private function documentCount(): int
    {
        return Capsule::table('collection_search_documents')->count();
    }

    private function form(string $code): CollectionItemFormDTO
    {
        return new CollectionItemFormDTO($this->collection, null, $code, 'Needle', true, null, null, 100, 'Preview needle', 'Body needle', []);
    }
}
