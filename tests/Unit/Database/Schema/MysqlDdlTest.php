<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Schema;

use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\Blueprint;
use Johncms\Database\Schema\Adapters\BlueprintCompiler;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Database\Schema\ReferentialAction;
use Johncms\Database\Schema\TableDefinition;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The exact MySQL a table description turns into.
 *
 * Nails down the column types, because they are the one thing that must not drift: every existing
 * installation was built by this grammar, and a baseline migration that produced a different type
 * would leave a fresh site and an upgraded one with different tables — silently, since the
 * baseline skips what already exists. The same statements are the reference to check a future
 * adapter against.
 *
 * No server is involved: the grammar only asks the connection which flavour it is talking to, and
 * the double answers for MySQL without opening anything.
 */
final class MysqlDdlTest extends TestCase
{
    public function testCreateTableStatementIsTheOneTheCmsHasAlwaysUsed(): void
    {
        $description = new TableDefinition('probe');
        $description->increments('id');
        $description->tinyInteger('type')->unsigned()->default(0);
        $description->smallInteger('small');
        $description->integer('count')->unsigned()->nullable();
        $description->bigInteger('huge');
        $description->boolean('flag')->default(true);
        $description->double('ratio');
        $description->decimal('price', 10, 4);
        $description->char('code', 3);
        $description->string('name');
        $description->string('slug', 120);
        $description->text('body')->nullable();
        $description->mediumText('medium');
        $description->longText('long');
        $description->json('payload');
        $description->date('day');
        $description->dateTime('moment');
        $description->timestamp('recorded_at');
        $description->timestamps();
        $description->softDeletes();
        $description->string('note')->comment('A note');

        $expected = 'create table `probe` ('
            . '`id` int unsigned not null auto_increment primary key, '
            . '`type` tinyint unsigned not null default \'0\', '
            . '`small` smallint not null, '
            . '`count` int unsigned null, '
            . '`huge` bigint not null, '
            . '`flag` tinyint(1) not null default \'1\', '
            . '`ratio` double not null, '
            . '`price` decimal(10, 4) not null, '
            . '`code` char(3) not null, '
            . '`name` varchar(255) not null, '
            . '`slug` varchar(120) not null, '
            . '`body` text null, '
            . '`medium` mediumtext not null, '
            . '`long` longtext not null, '
            . '`payload` json not null, '
            . '`day` date not null, '
            . '`moment` datetime not null, '
            . '`recorded_at` timestamp not null, '
            . '`created_at` timestamp null, '
            . '`updated_at` timestamp null, '
            . '`deleted_at` timestamp null, '
            . '`note` varchar(255) not null comment \'A note\')'
            . ' default character set utf8mb4 collate \'utf8mb4_unicode_ci\' engine = InnoDB ROW_FORMAT=DYNAMIC';

        self::assertSame([$expected], $this->createStatements($description));
    }

    public function testKeysAreDeclaredAfterTheTable(): void
    {
        $description = new TableDefinition('probe');
        $description->increments('id');
        $description->integer('type')->unsigned();
        $description->string('slug')->unique();
        $description->integer('owner_id')->unsigned();
        $description->index('type');
        $description->foreign('owner_id')
            ->references('id')
            ->on('users')
            ->onUpdate(ReferentialAction::Cascade)
            ->onDelete(ReferentialAction::SetNull);

        $statements = $this->createStatements($description);

        self::assertSame('alter table `probe` add unique `probe_slug_unique`(`slug`)', $statements[1]);
        self::assertSame('alter table `probe` add index `probe_type_index`(`type`)', $statements[2]);
        self::assertSame(
            'alter table `probe` add constraint `probe_owner_id_foreign` foreign key (`owner_id`)'
            . ' references `users` (`id`) on delete set null on update cascade',
            $statements[3]
        );
    }

    public function testAlterStatementsKeepTheirOrder(): void
    {
        $description = new TableDefinition('probe');
        $description->string('added')->nullable();
        $description->renameColumn('note', 'remark');
        $description->dropIndex('probe_type_index');
        $description->dropColumn('medium');

        self::assertSame(
            [
                'alter table `probe` rename column `note` to `remark`',
                'alter table `probe` add `added` varchar(255) null',
                'alter table `probe` drop index `probe_type_index`',
                'alter table `probe` drop `medium`',
            ],
            $this->alterStatements($description)
        );
    }

    public function testAWordIndexBecomesAFullTextOne(): void
    {
        $description = new TableDefinition('probe');
        $description->increments('id');
        $description->longText('text');
        $description->fullText('text', 'text');

        self::assertSame(
            'alter table `probe` add fulltext `text`(`text`)',
            $this->createStatements($description)[1]
        );
    }

    /**
     * An auto-incrementing column is unsigned by definition, and asking for it again used to
     * produce "int unsigned unsigned" on some grammars.
     */
    public function testUnsignedIsNotRepeatedOnAnAutoIncrementingColumn(): void
    {
        $description = new TableDefinition('probe');
        $description->increments('id')->unsigned();

        self::assertStringContainsString('`id` int unsigned not null auto_increment primary key', $this->createStatements($description)[0]);
    }

    public function testAStringWithoutALengthGetsTheDefaultOne(): void
    {
        $description = new TableDefinition('probe');
        $description->string('with_default');
        $description->string('explicit', 64);

        $statement = $this->createStatements($description)[0];

        self::assertStringContainsString('`with_default` varchar(255)', $statement);
        self::assertStringContainsString('`explicit` varchar(64)', $statement);
    }

    /**
     * The cap an installation on MySQL below 5.7 needs, where a 255-character utf8mb4 column
     * cannot be indexed. Restored right after, because the setting is global to the process.
     */
    public function testTheDefaultStringLengthCanBeCapped(): void
    {
        $schema = new IlluminateSchema($this->mysql()->getSchemaBuilder());

        $description = new TableDefinition('probe');
        $description->string('short');
        $description->string('explicit', 40);

        $schema->setDefaultStringLength(191);

        try {
            $statement = $this->createStatements($description)[0];
        } finally {
            $schema->setDefaultStringLength(255);
        }

        self::assertStringContainsString('`short` varchar(191)', $statement);
        self::assertStringContainsString('`explicit` varchar(40)', $statement);
    }

    /**
     * @return list<string>
     */
    private function createStatements(TableDefinition $description): array
    {
        $blueprint = new Blueprint($this->mysql(), $description->name);
        $blueprint->create();

        return $this->compile($description, $blueprint);
    }

    /**
     * @return list<string>
     */
    private function alterStatements(TableDefinition $description): array
    {
        return $this->compile($description, new Blueprint($this->mysql(), $description->name));
    }

    /**
     * @return list<string>
     */
    private function compile(TableDefinition $description, Blueprint $blueprint): array
    {
        (new BlueprintCompiler())->compile($description, $blueprint);

        return array_values($blueprint->toSql());
    }

    /**
     * A connection that can describe MySQL but cannot reach one: the closure standing in for the
     * PDO throws, so a grammar that tried to query the server would fail the test instead of
     * quietly needing a database.
     */
    private function mysql(): MySqlConnection
    {
        $connection = new class (
            static fn () => throw new RuntimeException('The schema grammar must not need a server.'),
            'johncms',
            '',
            [
                'driver'    => 'mysql',
                'charset'   => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'engine'    => 'InnoDB ROW_FORMAT=DYNAMIC',
            ]
        ) extends MySqlConnection {
            public function isMaria(): bool
            {
                return false;
            }

            public function getServerVersion(): string
            {
                return '8.0.36';
            }
        };

        $connection->useDefaultSchemaGrammar();

        return $connection;
    }
}
