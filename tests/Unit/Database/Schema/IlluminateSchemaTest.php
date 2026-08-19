<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Schema;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\SchemaDefinitionException;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Database\Schema\TableDefinition;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

/**
 * The adapter against a real database, because a description that never reaches one proves
 * nothing. SQLite is the database the unit suite has; what MySQL makes of the same description is
 * decided by the same builder that created the tables of every existing installation.
 */
final class IlluminateSchemaTest extends TestCase
{
    use BootsInMemoryDatabase;

    private SchemaInterface $schema;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->schema = new IlluminateSchema(Capsule::schema());
    }

    protected function tearDown(): void
    {
        $this->shutdownDatabase();
    }

    public function testEveryColumnTypeReachesTheDatabase(): void
    {
        $this->schema->create('sample', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->bigInteger('big');
            $table->integer('regular');
            $table->smallInteger('small');
            $table->tinyInteger('tiny');
            $table->boolean('flag');
            $table->double('ratio');
            $table->decimal('price', 10, 4);
            $table->char('code', 3);
            $table->string('name', 120);
            $table->text('description');
            $table->mediumText('medium');
            $table->longText('long');
            $table->json('payload');
            $table->date('day');
            $table->dateTime('moment');
            $table->timestamp('recorded_at');
        });

        $expected = [
            'id', 'big', 'regular', 'small', 'tiny', 'flag', 'ratio', 'price', 'code',
            'name', 'description', 'medium', 'long', 'payload', 'day', 'moment', 'recorded_at',
        ];

        foreach ($expected as $column) {
            self::assertTrue($this->schema->hasColumn('sample', $column), $column . ' is missing');
        }
    }

    public function testModifiersReachTheDatabase(): void
    {
        $this->schema->create('members', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('nickname');
            $table->integer('karma')->default(7);
            $table->text('about')->nullable();
        });

        Capsule::table('members')->insert(['nickname' => 'Alex']);

        /** @var object{karma: int, about: string|null} $row */
        $row = Capsule::table('members')->first();

        self::assertSame(7, (int) $row->karma);
        self::assertNull($row->about);
    }

    public function testKeysAreCreated(): void
    {
        $this->schema->create('votes', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('user_id')->index();
            $table->string('slug')->unique();
            $table->integer('topic_id');
            $table->integer('position');
            $table->unique(['topic_id', 'position'], 'topic_position');
        });

        self::assertTrue($this->schema->hasIndex('votes', 'votes_user_id_index'));
        self::assertTrue($this->schema->hasIndex('votes', 'votes_slug_unique'));
        self::assertTrue($this->schema->hasIndex('votes', 'topic_position'));
    }

    public function testForeignKeyIsCreated(): void
    {
        $this->schema->create('articles', static function (TableDefinition $table): void {
            $table->increments('id');
        });

        $this->schema->create('article_comments', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('article_id')->unsigned();
            $table->foreign('article_id')
                ->references('id')
                ->on('articles')
                ->onUpdate(ReferentialAction::Cascade)
                ->onDelete(ReferentialAction::Cascade);
        });

        Capsule::table('articles')->insert(['id' => 1]);
        Capsule::table('article_comments')->insert(['article_id' => 1]);

        Capsule::table('articles')->where('id', '=', 1)->delete();

        self::assertSame(0, Capsule::table('article_comments')->count());
    }

    public function testAlterAddsAndDropsColumns(): void
    {
        $this->schema->create('files', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('name');
            $table->string('legacy_path');
        });

        $this->schema->alter('files', static function (TableDefinition $table): void {
            $table->string('slug')->nullable();
            $table->dropColumn('legacy_path');
        });

        self::assertTrue($this->schema->hasColumn('files', 'slug'));
        self::assertFalse($this->schema->hasColumn('files', 'legacy_path'));
    }

    public function testAlterRenamesAColumn(): void
    {
        $this->schema->create('settings', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('old_name');
        });

        $this->schema->alter('settings', static function (TableDefinition $table): void {
            $table->renameColumn('old_name', 'new_name');
        });

        self::assertFalse($this->schema->hasColumn('settings', 'old_name'));
        self::assertTrue($this->schema->hasColumn('settings', 'new_name'));
    }

    public function testAlterChangesAColumnType(): void
    {
        $this->schema->create('identities', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('avatar_url')->nullable();
        });

        self::assertStringContainsString('varchar', strtolower((string) $this->schema->getColumnType('identities', 'avatar_url')));

        $this->schema->alter('identities', static function (TableDefinition $table): void {
            $table->text('avatar_url')->nullable()->change();
        });

        self::assertStringContainsString('text', strtolower((string) $this->schema->getColumnType('identities', 'avatar_url')));
    }

    public function testAlterDropsAnIndex(): void
    {
        $this->schema->create('sessions', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->string('token')->index();
        });

        self::assertTrue($this->schema->hasIndex('sessions', 'sessions_token_index'));

        $this->schema->alter('sessions', static function (TableDefinition $table): void {
            $table->dropIndex('sessions_token_index');
        });

        self::assertFalse($this->schema->hasIndex('sessions', 'sessions_token_index'));
    }

    public function testTableIsRenamedDroppedAndAskedAbout(): void
    {
        self::assertFalse($this->schema->hasTable('before'));

        $this->schema->create('before', static function (TableDefinition $table): void {
            $table->increments('id');
        });

        self::assertTrue($this->schema->hasTable('before'));

        $this->schema->rename('before', 'after');

        self::assertFalse($this->schema->hasTable('before'));
        self::assertTrue($this->schema->hasTable('after'));

        $this->schema->drop('after');

        self::assertFalse($this->schema->hasTable('after'));

        // Asking for a table that is not there must not be an error.
        $this->schema->dropIfExists('after');
    }

    public function testTypeOfAMissingColumnIsNull(): void
    {
        self::assertNull($this->schema->getColumnType('nothing', 'id'));

        $this->schema->create('something', static function (TableDefinition $table): void {
            $table->increments('id');
        });

        self::assertNull($this->schema->getColumnType('something', 'missing'));
    }

    public function testAnIncompleteForeignKeyIsRefusedBeforeItReachesTheDatabase(): void
    {
        $this->expectException(SchemaDefinitionException::class);

        $this->schema->create('broken', static function (TableDefinition $table): void {
            $table->increments('id');
            $table->integer('owner_id');
            $table->foreign('owner_id')->references('id');
        });
    }
}
