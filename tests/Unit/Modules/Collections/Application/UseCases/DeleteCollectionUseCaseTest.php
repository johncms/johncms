<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCache;
use Johncms\Modules\Collections\Infrastructure\Persistence\Repository\ContentCollectionRepository;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\InMemoryCache;
use Tests\Support\RunsMigrations;

final class DeleteCollectionUseCaseTest extends TestCase
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

    public function testExplicitlyDeletesTheContentsAndInvalidatesThePublicCodeMap(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'blog', 'name' => 'Blog', 'active' => 1, 'public' => 1]);
        $section = Fixture::insert('collection_sections', ['collection_id' => $collection, 'code' => 'section', 'name' => 'Section']);
        $item = Fixture::insert('collection_items', ['collection_id' => $collection, 'section_id' => $section, 'code' => 'item', 'name' => 'Item']);
        $field = Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'text', 'name' => 'Text', 'type' => 'string']);
        Fixture::insert('collection_item_values', ['item_id' => $item, 'field_id' => $field, 'value_string' => 'Value']);
        $cache = new CollectionCodeCache(InMemoryCache::create(), new ContentCollectionRepository());
        self::assertSame(['blog' => $collection], $cache->map());
        Capsule::connection()->getPdo()->exec('PRAGMA foreign_keys = OFF');
        CollectionItemWriterFactory::collectionDeleter($cache)->execute($collection);
        foreach (['collections', 'collection_items', 'collection_item_values', 'collection_fields', 'collection_sections'] as $table) {
            self::assertSame(0, Capsule::table($table)->count(), $table);
        }
        self::assertSame([], $cache->map());
    }
}
