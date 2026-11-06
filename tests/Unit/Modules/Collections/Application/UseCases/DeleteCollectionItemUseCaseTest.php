<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\CollectionTransaction;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionItemValueRepository;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\RunsMigrations;

final class DeleteCollectionItemUseCaseTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->migrate('collections');
        CollectionItemWriterFactory::validator();
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testDeletesValuesExplicitlyWithoutForeignKeyCascades(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'blog', 'name' => 'Blog']);
        $item = Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'item', 'name' => 'Item']);
        $field = Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'text', 'name' => 'Text', 'type' => 'string']);
        Fixture::insert('collection_item_values', ['item_id' => $item, 'field_id' => $field, 'value_string' => 'Value']);
        Capsule::connection()->getPdo()->exec('PRAGMA foreign_keys = OFF');
        CollectionItemWriterFactory::itemDeleter()->execute($item, $collection);
        self::assertSame(0, Capsule::table('collection_items')->count());
        self::assertSame(0, Capsule::table('collection_item_values')->count());
        self::assertSame(1, Capsule::table('collection_fields')->count());
    }

    public function testFailureAfterDeletingValuesRollsBackTheEntireOperation(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'blog', 'name' => 'Blog']);
        $id = Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'item', 'name' => 'Item']);
        $field = Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'text', 'name' => 'Text', 'type' => 'string']);
        Fixture::insert('collection_item_values', ['item_id' => $id, 'field_id' => $field, 'value_string' => 'Value']);
        $items = $this->createMock(ContentCollectionItemRepositoryInterface::class);
        $items->method('findForUpdate')->willReturn(ContentCollectionItem::query()->find($id));
        $items->method('delete')->willThrowException(new RuntimeException('Simulated deletion failure'));
        $delete = new DeleteCollectionItemUseCase(
            $items, new ContentCollectionItemValueRepository(),
            new ContentCollectionRepository(),
            new CollectionTransaction(), CollectionItemWriterFactory::relationDeletion()
        );
        try {
            $delete->execute($id, $collection);
            self::fail('The deletion failure must propagate.');
        } catch (RuntimeException $exception) {
            self::assertSame('Simulated deletion failure', $exception->getMessage());
        }
        self::assertSame(1, Capsule::table('collection_items')->count());
        self::assertSame('Value', Capsule::table('collection_item_values')->value('value_string'));
    }
}
