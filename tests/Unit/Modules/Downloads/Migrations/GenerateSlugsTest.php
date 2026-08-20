<?php

declare(strict_types=1);

namespace Tests\Unit\Modules\Downloads\Migrations;

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

        foreach (['download__category', 'download__files'] as $table) {
            $this->schema->create($table, static function (TableDefinition $definition): void {
                $definition->increments('id');
                $definition->integer('refid')->unsigned()->default(0);
                $definition->string('rus_name')->default('');
            });
        }
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testTheColumnsAndTheKeysAreAdded(): void
    {
        $this->migrate('downloads', 'generate_slugs');

        self::assertTrue($this->schema->hasColumn('download__category', 'slug'));
        self::assertTrue($this->schema->hasColumn('download__files', 'slug'));
        self::assertTrue($this->schema->hasIndex('download__category', 'download__category_refid_slug_unique'));
        self::assertTrue($this->schema->hasIndex('download__files', 'download__files_refid_slug_unique'));
    }

    public function testEveryRowGetsASlugMadeOfItsName(): void
    {
        $this->row('download__category', 1, 0, 'Mobile Games');
        $this->row('download__files', 1, 1, 'The Manual');

        $this->migrate('downloads', 'generate_slugs');

        self::assertSame('mobile-games', $this->slugOf('download__category', 1));
        self::assertSame('the-manual', $this->slugOf('download__files', 1));
    }

    public function testTheSameNameIsMadeUniqueWithinItsCategory(): void
    {
        $this->row('download__files', 1, 7, 'Readme');
        $this->row('download__files', 2, 7, 'Readme');
        $this->row('download__files', 3, 8, 'Readme');

        $this->migrate('downloads', 'generate_slugs');

        self::assertSame('readme', $this->slugOf('download__files', 1));
        self::assertSame('readme-2', $this->slugOf('download__files', 2));
        self::assertSame('readme', $this->slugOf('download__files', 3));
    }

    /**
     * A category named "Search" would otherwise sit at the address of the search page itself.
     */
    public function testACategoryNamedAfterAPageOfTheModuleStepsAside(): void
    {
        $this->row('download__category', 1, 0, 'Search');
        $this->row('download__files', 1, 0, 'Search');

        $this->migrate('downloads', 'generate_slugs');

        self::assertSame('search-section', $this->slugOf('download__category', 1));
        // A file is not addressed the way a category is, so its name is left as it is.
        self::assertSame('search', $this->slugOf('download__files', 1));
    }

    public function testANameNothingCanBeMadeOfFallsBackToTheRowItself(): void
    {
        $this->row('download__category', 4, 0, '!!!');
        $this->row('download__files', 9, 0, '???');

        $this->migrate('downloads', 'generate_slugs');

        self::assertSame('section-4', $this->slugOf('download__category', 4));
        self::assertSame('file-9', $this->slugOf('download__files', 9));
    }

    public function testRunningItAgainChangesNothing(): void
    {
        $this->row('download__category', 1, 0, 'Mobile Games');
        $this->migrate('downloads', 'generate_slugs');

        $before = $this->slugOf('download__category', 1);
        $this->migrate('downloads', 'generate_slugs');

        self::assertSame($before, $this->slugOf('download__category', 1));
    }

    private function row(string $table, int $id, int $parent, string $name): void
    {
        Capsule::table($table)->insert(['id' => $id, 'refid' => $parent, 'rus_name' => $name]);
    }

    private function slugOf(string $table, int $id): ?string
    {
        $slug = Capsule::table($table)->where('id', $id)->value('slug');

        return $slug === null ? null : (string) $slug;
    }
}
