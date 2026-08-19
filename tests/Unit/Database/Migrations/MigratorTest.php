<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\ConnectionInterface;
use Johncms\Database\Migrations\DatabaseMigrationRepository;
use Johncms\Database\Migrations\Exceptions\InvalidMigrationFileException;
use Johncms\Database\Migrations\Exceptions\IrreversibleMigrationException;
use Johncms\Database\Migrations\Exceptions\MigrationFailedException;
use Johncms\Database\Migrations\Exceptions\MigrationsLockedException;
use Johncms\Database\Migrations\MigrationDirection;
use Johncms\Database\Migrations\MigrationFile;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\MigrationReporterInterface;
use Johncms\Database\Migrations\MigrationSource;
use Johncms\Database\Migrations\MigrationSourceProviderInterface;
use Johncms\Database\Migrations\Migrator;
use Johncms\Database\PdoConnection;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Database\Schema\SchemaInterface;
use Johncms\Scheduler\ScheduleMutexInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;
use Throwable;

final class MigratorTest extends TestCase
{
    use BootsInMemoryDatabase;

    private string $root;

    private SchemaInterface $schema;

    private ConnectionInterface $connection;

    private DatabaseMigrationRepository $repository;

    protected function setUp(): void
    {
        $this->bootDatabase();

        $this->root = sys_get_temp_dir() . '/johncms-migrator-' . bin2hex(random_bytes(4));
        $this->schema = new IlluminateSchema(Capsule::schema());
        $this->connection = new PdoConnection(Capsule::connection()->getPdo());
        $this->repository = new DatabaseMigrationRepository($this->schema, $this->connection);
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
        $this->shutdownDatabase();
    }

    public function testPendingMigrationsAreAppliedInOrderAndJournalled(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->createTableMigration('system', '2026_08_01_090000_create_files', 'files');
        $this->createTableMigration('forum', '2026_10_14_093000_create_topics', 'forum_topics');

        $applied = $this->migrator()->run();

        self::assertSame(
            ['system:20260801090000', 'system:20260901120000', 'forum:20261014093000'],
            array_map(static fn (MigrationFile $file): string => $file->id(), $applied)
        );

        self::assertTrue($this->schema->hasTable('users'));
        self::assertTrue($this->schema->hasTable('files'));
        self::assertTrue($this->schema->hasTable('forum_topics'));

        self::assertCount(3, $this->repository->all());
    }

    public function testASecondRunHasNothingToDo(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');

        $migrator = $this->migrator();
        $migrator->run();

        self::assertSame([], $migrator->pending());
        self::assertSame([], $migrator->run());
        self::assertCount(1, $this->repository->all());
    }

    public function testEachRunIsABatchOfItsOwn(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->migrator()->run();

        $this->createTableMigration('system', '2026_09_02_120000_create_files', 'files');
        $this->migrator()->run();

        $batches = array_map(static fn ($applied): int => $applied->batch, $this->repository->all());

        self::assertSame([1, 2], $batches);
    }

    public function testOneSourceCanBeRunOnItsOwn(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->createTableMigration('forum', '2026_10_14_093000_create_topics', 'forum_topics');

        $this->migrator()->run('forum');

        self::assertFalse($this->schema->hasTable('users'));
        self::assertTrue($this->schema->hasTable('forum_topics'));
        self::assertCount(1, $this->repository->all());
    }

    /**
     * Everything before the failure stays applied and journalled; the one that threw is not
     * recorded, so the next run starts from it.
     */
    public function testAFailureStopsTheRunAndIsNotJournalled(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->migration('system', '2026_09_02_120000_break', <<<'BODY'
            public function up(): void
            {
                throw new \RuntimeException('boom');
            }
            BODY);
        $this->createTableMigration('system', '2026_09_03_120000_create_files', 'files');

        $migrator = $this->migrator();
        $failure = null;

        try {
            $migrator->run();
        } catch (MigrationFailedException $exception) {
            $failure = $exception;
        }

        self::assertNotNull($failure);
        self::assertSame('break', $failure->migration->name);
        self::assertSame(MigrationDirection::Up, $failure->direction);
        self::assertSame('boom', $failure->getPrevious()?->getMessage());

        self::assertTrue($this->schema->hasTable('users'));
        self::assertFalse($this->schema->hasTable('files'));
        self::assertCount(1, $this->repository->all());
        self::assertCount(2, $migrator->pending());
    }

    public function testRollbackUndoesTheLastBatchInReverse(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->createTableMigration('forum', '2026_10_14_093000_create_topics', 'forum_topics');

        $migrator = $this->migrator();
        $migrator->run();

        $rolledBack = $migrator->rollback();

        self::assertSame(
            ['forum:20261014093000', 'system:20260901120000'],
            array_map(static fn (MigrationFile $file): string => $file->id(), $rolledBack)
        );
        self::assertFalse($this->schema->hasTable('users'));
        self::assertFalse($this->schema->hasTable('forum_topics'));
        self::assertSame([], $this->repository->all());
    }

    public function testRollbackTouchesOnlyTheLastBatch(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->migrator()->run();

        $this->createTableMigration('system', '2026_09_02_120000_create_files', 'files');
        $this->migrator()->run();

        $this->migrator()->rollback();

        self::assertTrue($this->schema->hasTable('users'));
        self::assertFalse($this->schema->hasTable('files'));
        self::assertCount(1, $this->repository->all());
    }

    public function testAMigrationThatCannotBeUndoneRefusesInsteadOfLosingData(): void
    {
        $this->migration('system', '2026_09_01_120000_one_way', <<<'BODY'
            public function up(): void
            {
                $this->db->execute('CREATE TABLE one_way (id integer)');
            }
            BODY);

        $migrator = $this->migrator();
        $migrator->run();

        $this->expectException(IrreversibleMigrationException::class);

        $migrator->rollback();
    }

    public function testStatusTellsAppliedFromWaiting(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->createTableMigration('system', '2026_09_02_120000_create_files', 'files');

        $migrator = $this->migrator();
        $migrator->run('system');

        $this->createTableMigration('forum', '2026_10_14_093000_create_topics', 'forum_topics');

        $statuses = $this->migrator()->status();

        self::assertCount(3, $statuses);
        self::assertTrue($statuses[0]->isApplied);
        self::assertSame(1, $statuses[0]->batch);
        self::assertTrue($statuses[1]->isApplied);
        self::assertFalse($statuses[2]->isApplied);
        self::assertSame('forum', $statuses[2]->source);
        self::assertNull($statuses[2]->batch);
    }

    public function testStatusNoticesAFileEditedAfterItRan(): void
    {
        $path = $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->migrator()->run();

        self::assertTrue($this->migrator()->status()[0]->checksumMatches);

        file_put_contents($path, file_get_contents($path) . "\n// edited\n");

        self::assertFalse($this->migrator()->status()[0]->checksumMatches);
    }

    public function testStatusRemembersAMigrationWhoseFileIsGone(): void
    {
        $path = $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->migrator()->run();

        unlink($path);

        $statuses = $this->migrator()->status();

        self::assertCount(1, $statuses);
        self::assertFalse($statuses[0]->fileExists);
        self::assertTrue($statuses[0]->isApplied);
        self::assertSame('create_users', $statuses[0]->name);
    }

    public function testARunInProgressKeepsAnotherOut(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');

        $this->expectException(MigrationsLockedException::class);

        $this->migrator(busy: true)->run();
    }

    public function testAFileThatDoesNotReturnAMigrationIsFatal(): void
    {
        $this->write('system', '2026_09_01_120000_not_a_migration.php', "<?php\n\nreturn 'nope';\n");

        $this->expectException(InvalidMigrationFileException::class);
        $this->expectExceptionMessageMatches('/has to return an instance of/');

        $this->migrator()->run();
    }

    /**
     * A migration that only moves data may ask for a transaction, and then a failure halfway
     * through leaves the rows as they were.
     */
    public function testATransactionalMigrationLeavesNothingBehindWhenItFails(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');
        $this->migration('system', '2026_09_02_120000_fill_users', <<<'BODY'
            public function useTransaction(): bool
            {
                return true;
            }

            public function up(): void
            {
                $this->db->execute('INSERT INTO users (id) VALUES (1)');
                $this->db->execute('INSERT INTO users (id) VALUES (2)');

                throw new \RuntimeException('boom');
            }
            BODY);

        try {
            $this->migrator()->run();
        } catch (MigrationFailedException) {
            // The point of the test is what is left in the table.
        }

        self::assertSame(0, (int) $this->connection->select('SELECT COUNT(*) AS total FROM users')[0]['total']);
    }

    public function testTheReporterIsToldWhatHappened(): void
    {
        $this->createTableMigration('system', '2026_09_01_120000_create_users', 'users');

        $reporter = new class implements MigrationReporterInterface {
            /** @var list<string> */
            public array $lines = [];

            public function starting(MigrationFile $migration, MigrationDirection $direction): void
            {
                $this->lines[] = 'starting ' . $migration->name . ' ' . $direction->value;
            }

            public function finished(MigrationFile $migration, MigrationDirection $direction, int $durationMs): void
            {
                $this->lines[] = 'finished ' . $migration->name . ' ' . $direction->value;
            }

            public function failed(MigrationFile $migration, MigrationDirection $direction, Throwable $exception): void
            {
                $this->lines[] = 'failed ' . $migration->name . ' ' . $direction->value;
            }
        };

        $migrator = $this->migrator();
        $migrator->run(reporter: $reporter);
        $migrator->rollback(reporter: $reporter);

        self::assertSame(
            [
                'starting create_users up',
                'finished create_users up',
                'starting create_users down',
                'finished create_users down',
            ],
            $reporter->lines
        );
    }

    private function migrator(bool $busy = false): Migrator
    {
        $sources = [
            new MigrationSource('system', $this->root . '/system'),
            new MigrationSource('forum', $this->root . '/forum'),
        ];

        $provider = new class ($sources) implements MigrationSourceProviderInterface {
            /**
             * @param list<MigrationSource> $sources
             */
            public function __construct(private readonly array $sources)
            {
            }

            public function sources(): array
            {
                return $this->sources;
            }
        };

        $mutex = $busy
            ? new class implements ScheduleMutexInterface {
                public function acquire(string $key): mixed
                {
                    return null;
                }

                public function release(mixed $lock): void
                {
                }
            }
            : new class implements ScheduleMutexInterface {
                public function acquire(string $key): mixed
                {
                    return 'held';
                }

                public function release(mixed $lock): void
                {
                }
            };

        return new Migrator(
            new MigrationLocator([$provider]),
            $this->repository,
            $this->schema,
            $this->connection,
            $mutex
        );
    }

    private function createTableMigration(string $source, string $fileName, string $table): string
    {
        return $this->migration($source, $fileName, sprintf(
            <<<'BODY'
                public function up(): void
                {
                    $this->schema->create('%s', static function (TableDefinition $table): void {
                        $table->increments('id');
                    });
                }

                public function down(): void
                {
                    $this->schema->dropIfExists('%s');
                }
            BODY,
            $table,
            $table
        ));
    }

    private function migration(string $source, string $fileName, string $body): string
    {
        $contents = <<<PHP
        <?php

        declare(strict_types=1);

        use Johncms\Database\Migrations\Migration;
        use Johncms\Database\Schema\TableDefinition;

        return new class extends Migration {
        $body
        };

        PHP;

        return $this->write($source, $fileName . '.php', $contents);
    }

    private function write(string $source, string $fileName, string $contents): string
    {
        $directory = $this->root . '/' . $source;
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $path = $directory . '/' . $fileName;
        file_put_contents($path, $contents);

        return $path;
    }

    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        foreach (glob($directory . '/*') ?: [] as $entry) {
            is_dir($entry) ? $this->removeDirectory($entry) : unlink($entry);
        }

        rmdir($directory);
    }
}
