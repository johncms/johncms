<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Collections\Install;

use Gettext\Translator;
use Gettext\TranslatorFunctions;
use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Modules\Collections\Install\Installer;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

final class InstallerTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private const TABLES = [
        'collections',
        'collection_fields',
        'collection_sections',
        'collection_items',
        'collection_item_values',
    ];

    protected function setUp(): void
    {
        $this->bootDatabase();

        // installDemoData() references MODULES_PATH and d__(); provide both.
        if (! defined('MODULES_PATH')) {
            define('MODULES_PATH', dirname(__DIR__, 5) . '/modules/');
        }
        TranslatorFunctions::register(new Translator());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testInstallCreatesAllTables(): void
    {
        $this->migrate('collections');

        $schema = Capsule::schema();
        foreach (self::TABLES as $table) {
            self::assertTrue($schema->hasTable($table), "Table {$table} should be created");
        }
    }

    public function testInstalledTablesHaveExpectedColumns(): void
    {
        $this->migrate('collections');

        $schema = Capsule::schema();

        self::assertTrue($schema->hasColumns('collections', ['code', 'name', 'settings', 'sort', 'active']));
        self::assertTrue($schema->hasColumns('collection_fields', ['collection_id', 'code', 'type', 'required', 'multiple']));
        self::assertTrue($schema->hasColumns('collection_sections', ['collection_id', 'parent', 'code', 'active']));
        self::assertTrue($schema->hasColumns('collection_items', ['collection_id', 'section_id', 'code', 'active', 'active_from', 'active_to', 'sort']));
        self::assertTrue($schema->hasColumns('collection_item_values', ['item_id', 'field_id', 'value_string', 'value_int', 'value_double', 'value_date', 'value_text', 'sort']));
    }

    public function testInstallDemoDataSeedsBlogCollection(): void
    {
        $this->migrate('collections');
        (new Installer('collections'))->installDemoData();

        $collection = Capsule::table('collections')->where('code', 'blog')->first();
        self::assertNotNull($collection);

        self::assertSame(2, Capsule::table('collection_fields')->where('collection_id', $collection->id)->count());
        self::assertSame(1, Capsule::table('collection_sections')->where('collection_id', $collection->id)->count());
        self::assertSame(1, Capsule::table('collection_items')->where('collection_id', $collection->id)->count());
        self::assertSame(2, Capsule::table('collection_item_values')->count());
    }

    public function testInstallDemoDataWritesValuesIntoTypedColumns(): void
    {
        $this->migrate('collections');
        (new Installer('collections'))->installDemoData();

        $authorFieldId = Capsule::table('collection_fields')->where('code', 'author')->value('id');
        $ratingFieldId = Capsule::table('collection_fields')->where('code', 'rating')->value('id');

        $authorValue = Capsule::table('collection_item_values')->where('field_id', $authorFieldId)->first();
        $ratingValue = Capsule::table('collection_item_values')->where('field_id', $ratingFieldId)->first();

        // string field -> value_string, integer field -> value_int (see FieldType::valueColumn()).
        self::assertSame('John', $authorValue->value_string);
        self::assertNull($authorValue->value_int);
        self::assertSame(5, (int) $ratingValue->value_int);
        self::assertNull($ratingValue->value_string);
    }

    public function testUninstallDropsAllTables(): void
    {
        $this->migrate('collections');
        (new Installer('collections'))->uninstall();

        $schema = Capsule::schema();
        foreach (self::TABLES as $table) {
            self::assertFalse($schema->hasTable($table), "Table {$table} should be dropped");
        }
    }
}
