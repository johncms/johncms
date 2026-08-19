<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\Migrations\DatabaseMigrationRepository;
use Johncms\Database\Migrations\MigrationFile;
use Johncms\Database\PdoConnection;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Database\Schema\SchemaInterface;
use PHPUnit\Framework\TestCase;
use Tests\Support\BootsInMemoryDatabase;

final class DatabaseMigrationRepositoryTest extends TestCase
{
    use BootsInMemoryDatabase;

    private SchemaInterface $schema;

    private DatabaseMigrationRepository $repository;

    private string $root;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->schema = new IlluminateSchema(Capsule::schema());
        $this->repository = new DatabaseMigrationRepository(
            $this->schema,
            new PdoConnection(Capsule::connection()->getPdo())
        );

        $this->root = sys_get_temp_dir() . '/johncms-migration-journal-' . bin2hex(random_bytes(4));
        mkdir($this->root, 0775, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->root . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->root);

        $this->shutdownDatabase();
    }

    /**
     * A database with no journal has simply been through nothing: reading must not create it.
     */
    public function testReadingAnAbsentJournalAnswersWithNothing(): void
    {
        self::assertFalse($this->schema->hasTable(DatabaseMigrationRepository::TABLE));
        self::assertSame([], $this->repository->all());
        self::assertSame(0, $this->repository->lastBatch());
        self::assertSame(1, $this->repository->nextBatch());
        self::assertFalse($this->schema->hasTable(DatabaseMigrationRepository::TABLE));
    }

    public function testTheJournalIsCreatedOnceAndThenLeftAlone(): void
    {
        $this->repository->ensureStorageExists();
        $this->repository->log($this->file('system', '20260901120000'), 1, 5);

        $this->repository->ensureStorageExists();

        self::assertCount(1, $this->repository->all());
    }

    public function testWhatWasLoggedComesBack(): void
    {
        $this->repository->ensureStorageExists();
        $this->repository->log($this->file('system', '20260901120000', 'create_users'), 1, 12);

        $applied = $this->repository->all()[0];

        self::assertSame('system', $applied->source);
        self::assertSame('20260901120000', $applied->version);
        self::assertSame('create_users', $applied->name);
        self::assertSame(1, $applied->batch);
        self::assertSame(sha1_file($this->root . '/create_users.php'), $applied->checksum);
        self::assertNotSame('', $applied->appliedAt);
    }

    /**
     * Identity is the source and the version together, so two modules reaching the same minute do
     * not collide, and one source repeating a version cannot be written twice.
     */
    public function testASourceAndAVersionIdentifyARowOnTheirOwn(): void
    {
        $this->repository->ensureStorageExists();
        $this->repository->log($this->file('system', '20260901120000'), 1, 0);
        $this->repository->log($this->file('forum', '20260901120000'), 1, 0);

        self::assertCount(2, $this->repository->all());
    }

    public function testBatchesAreCountedAndCanBeAskedForOneByOne(): void
    {
        $this->repository->ensureStorageExists();
        $this->repository->log($this->file('system', '20260901120000'), 1, 0);
        $this->repository->log($this->file('system', '20260901130000'), 2, 0);
        $this->repository->log($this->file('forum', '20260901140000'), 2, 0);

        self::assertSame(2, $this->repository->lastBatch());
        self::assertSame(3, $this->repository->nextBatch());
        self::assertCount(1, $this->repository->forBatch(1));
        self::assertCount(2, $this->repository->forBatch(2));
    }

    public function testForgettingRemovesOneRow(): void
    {
        $this->repository->ensureStorageExists();
        $this->repository->log($this->file('system', '20260901120000'), 1, 0);
        $this->repository->log($this->file('forum', '20260901120000'), 1, 0);

        $this->repository->forget($this->repository->all()[0]);

        $left = $this->repository->all();

        self::assertCount(1, $left);
        self::assertSame('forum', $left[0]->source);
    }

    private function file(string $source, string $version, string $name = 'create_users'): MigrationFile
    {
        $path = $this->root . '/' . $name . '.php';
        if (! is_file($path)) {
            file_put_contents($path, "<?php\n// " . $name);
        }

        return new MigrationFile($source, $version, $name, $path);
    }
}
