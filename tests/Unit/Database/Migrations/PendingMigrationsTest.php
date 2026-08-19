<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Illuminate\Database\Capsule\Manager as Capsule;
use Johncms\Database\Migrations\DatabaseMigrationRepository;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\MigrationSource;
use Johncms\Database\Migrations\MigrationSourceProviderInterface;
use Johncms\Database\Migrations\Migrator;
use Johncms\Database\Migrations\PendingMigrations;
use Johncms\Database\PdoConnection;
use Johncms\Database\Schema\Adapters\IlluminateSchema;
use Johncms\Scheduler\ScheduleMutexInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\NullLogger;
use Stringable;
use Tests\Support\BootsInMemoryDatabase;
use Tests\Support\InMemoryCache;

final class PendingMigrationsTest extends TestCase
{
    use BootsInMemoryDatabase;

    private string $root;

    protected function setUp(): void
    {
        $this->bootDatabase();
        $this->root = sys_get_temp_dir() . '/johncms-pending-' . bin2hex(random_bytes(4));
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

    public function testItCountsWhatHasNotBeenAppliedYet(): void
    {
        $this->write('2026_09_01_120000_first.php');
        $this->write('2026_09_02_120000_second.php');

        self::assertSame(2, $this->pendingMigrations()->count());
    }

    public function testTheAnswerIsCachedUntilItIsForgotten(): void
    {
        $this->write('2026_09_01_120000_first.php');

        $pending = $this->pendingMigrations();

        self::assertSame(1, $pending->count());

        $this->write('2026_09_02_120000_second.php');

        self::assertSame(1, $pending->count(), 'The cached answer should still be the old one.');

        $pending->forget();

        self::assertSame(2, $pending->count());
    }

    /**
     * A file nobody can read must not take down every page that only wanted to print a notice.
     */
    public function testAMigrationThatCannotBeReadIsLoggedAndCountedAsNone(): void
    {
        $this->write('not_named_like_a_migration.php');

        $logger = new class extends AbstractLogger {
            /** @var list<string> */
            public array $errors = [];

            public function log($level, string|Stringable $message, array $context = []): void
            {
                $this->errors[] = (string) $message;
            }
        };

        self::assertSame(0, $this->pendingMigrations($logger)->count());
        self::assertCount(1, $logger->errors);
    }

    private function pendingMigrations(?AbstractLogger $logger = null): PendingMigrations
    {
        $schema = new IlluminateSchema(Capsule::schema());
        $connection = new PdoConnection(Capsule::connection()->getPdo());

        $provider = new class ($this->root) implements MigrationSourceProviderInterface {
            public function __construct(private readonly string $directory)
            {
            }

            public function sources(): array
            {
                return [new MigrationSource('system', $this->directory)];
            }
        };

        $mutex = new class implements ScheduleMutexInterface {
            public function acquire(string $key): mixed
            {
                return 'held';
            }

            public function release(mixed $lock): void
            {
            }
        };

        $migrator = new Migrator(
            new MigrationLocator([$provider]),
            new DatabaseMigrationRepository($schema, $connection),
            $schema,
            $connection,
            $mutex
        );

        return new PendingMigrations($migrator, InMemoryCache::create(), $logger ?? new NullLogger());
    }

    private function write(string $fileName): void
    {
        file_put_contents($this->root . '/' . $fileName, "<?php\n");
    }
}
