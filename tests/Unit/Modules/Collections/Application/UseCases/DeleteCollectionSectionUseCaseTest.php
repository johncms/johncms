<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Application\UseCases;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\CollectionItemWriterFactory;
use Tests\Support\Fixture;
use Tests\Support\RunsMigrations;

final class DeleteCollectionSectionUseCaseTest extends TestCase
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

    public function testDeletingSubtreeExplicitlyRemovesItsItemsAndValues(): void
    {
        $collection = Fixture::insert('collections', ['code' => 'blog', 'name' => 'Blog']);
        $section = Fixture::insert('collection_sections', ['collection_id' => $collection, 'code' => 'section', 'name' => 'Section']);
        $child = Fixture::insert('collection_sections', ['collection_id' => $collection, 'parent' => $section, 'code' => 'child', 'name' => 'Child']);
        $item = Fixture::insert('collection_items', ['collection_id' => $collection, 'section_id' => $child, 'code' => 'item', 'name' => 'Item']);
        Fixture::insert('collection_items', ['collection_id' => $collection, 'code' => 'kept', 'name' => 'Kept']);
        $field = Fixture::insert('collection_fields', ['collection_id' => $collection, 'code' => 'text', 'name' => 'Text', 'type' => 'string']);
        Fixture::insert('collection_item_values', ['item_id' => $item, 'field_id' => $field, 'value_string' => 'Value']);
        Capsule::connection()->getPdo()->exec('PRAGMA foreign_keys = OFF');
        CollectionItemWriterFactory::sectionsApi()->deleteSection($collection, $section, SectionDeletionMode::DeleteItems);
        self::assertSame(0, Capsule::table('collection_sections')->count());
        self::assertSame(0, Capsule::table('collection_item_values')->count());
        self::assertSame(['kept'], Capsule::table('collection_items')->pluck('code')->all());
    }
}
