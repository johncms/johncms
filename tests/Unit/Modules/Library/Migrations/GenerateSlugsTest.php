<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Library\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\Schema\TableDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\RunsMigrations;

/**
 * The conversion a site upgrading from 9.9 goes through: its tables have no slug column, and the
 * addresses of the module are built from one.
 *
 * The tables are made here the way that site has them — without the column — because that is the
 * only state in which this migration has anything to do.
 */
final class GenerateSlugsTest extends TestCase
{
    use BootsInMemoryDatabase;
    use RunsMigrations;

    private SchemaInterface $schema;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->schema = new IlluminateSchema(Capsule::schema());

        $this->schema->create('library_cats', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('parent')->unsigned()->default(0);
            $table->string('name')->default('');
        });

        $this->schema->create('library_texts', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('cat_id')->unsigned()->default(0);
            $table->string('name')->default('');
        });
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheColumnAndTheKeyAreAdded(): void
    {
        self::assertFalse($this->schema->hasColumn('library_cats', 'slug'));

        $this->migrate('library', 'generate_slugs');

        self::assertTrue($this->schema->hasColumn('library_cats', 'slug'));
        self::assertTrue($this->schema->hasColumn('library_texts', 'slug'));
        self::assertTrue($this->schema->hasIndex('library_cats', 'library_cats_parent_slug_unique'));
    }

    public function testEverySectionGetsASlugMadeOfItsName(): void
    {
        $this->section(1, 0, 'Programming Books');
        $this->section(2, 0, 'Poetry');

        $this->migrate('library', 'generate_slugs');

        self::assertSame('programming-books', $this->slugOf('library_cats', 1));
        self::assertSame('poetry', $this->slugOf('library_cats', 2));
    }

    /**
     * Two sections of one parent cannot share an address; two sections of different parents can.
     */
    public function testTheSameNameIsMadeUniqueWithinItsParent(): void
    {
        $this->section(1, 0, 'Guides');
        $this->section(2, 0, 'Guides');
        $this->section(3, 5, 'Guides');

        $this->migrate('library', 'generate_slugs');

        self::assertSame('guides', $this->slugOf('library_cats', 1));
        self::assertSame('guides-2', $this->slugOf('library_cats', 2));
        self::assertSame('guides', $this->slugOf('library_cats', 3));
    }

    public function testANameNothingCanBeMadeOfFallsBackToAWord(): void
    {
        $this->section(1, 0, '!!!');
        $this->article(1, 0, '???');

        $this->migrate('library', 'generate_slugs');

        self::assertSame('section', $this->slugOf('library_cats', 1));
        self::assertSame('article', $this->slugOf('library_texts', 1));
    }

    /**
     * A slug somebody set by hand is what the address of that section already is, so it is kept
     * and the ones generated around it go out of its way.
     */
    public function testASlugThatIsAlreadyThereIsKeptAndReserved(): void
    {
        $this->section(1, 0, 'Guides');
        $this->section(2, 0, 'Guides');
        $this->migrate('library', 'generate_slugs');

        Capsule::table('library_cats')->where('id', 1)->update(['slug' => 'the-guides']);
        Capsule::table('library_cats')->where('id', 2)->update(['slug' => null]);

        $this->migrate('library', 'generate_slugs');

        self::assertSame('the-guides', $this->slugOf('library_cats', 1));
        self::assertSame('guides', $this->slugOf('library_cats', 2));
    }

    public function testRunningItAgainChangesNothing(): void
    {
        $this->section(1, 0, 'Guides');
        $this->article(1, 1, 'A Long Read');

        $this->migrate('library', 'generate_slugs');
        $before = [$this->slugOf('library_cats', 1), $this->slugOf('library_texts', 1)];

        $this->migrate('library', 'generate_slugs');

        self::assertSame($before, [$this->slugOf('library_cats', 1), $this->slugOf('library_texts', 1)]);
    }

    private function section(int $id, int $parent, string $name): void
    {
        Capsule::table('library_cats')->insert(['id' => $id, 'parent' => $parent, 'name' => $name]);
    }

    private function article(int $id, int $categoryId, string $name): void
    {
        Capsule::table('library_texts')->insert(['id' => $id, 'cat_id' => $categoryId, 'name' => $name]);
    }

    private function slugOf(string $table, int $id): ?string
    {
        $slug = Capsule::table($table)->where('id', $id)->value('slug');

        return $slug === null ? null : (string) $slug;
    }
}
