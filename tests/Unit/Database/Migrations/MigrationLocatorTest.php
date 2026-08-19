<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Johncms\Database\Migrations\Exceptions\InvalidMigrationFileException;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\MigrationSource;
use Johncms\Database\Migrations\MigrationSourceProviderInterface;
use PHPUnit\Framework\TestCase;

final class MigrationLocatorTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/johncms-migration-locator-' . bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testSourcesComeInProviderOrderAndFilesInVersionOrder(): void
    {
        $this->write('system', '2026_09_01_120000_create_users.php');
        $this->write('system', '2026_08_01_090000_create_files.php');
        $this->write('forum', '2026_10_14_093000_add_slug.php');

        $locator = $this->locator(['system', 'forum']);

        self::assertSame(
            ['system:20260801090000', 'system:20260901120000', 'forum:20261014093000'],
            array_map(static fn ($file): string => $file->id(), $locator->locate())
        );
    }

    public function testTheVersionAndTheNameAreReadOutOfTheFileName(): void
    {
        $this->write('forum', '2026_10_14_093000_add_slug_to_sections.php');

        $file = $this->locator(['forum'])->locate()[0];

        self::assertSame('forum', $file->source);
        self::assertSame('20261014093000', $file->version);
        self::assertSame('add_slug_to_sections', $file->name);
        self::assertSame($this->root . '/forum/2026_10_14_093000_add_slug_to_sections.php', $file->path);
    }

    public function testOneSourceCanBeAskedForOnItsOwn(): void
    {
        $this->write('system', '2026_09_01_120000_create_users.php');
        $this->write('forum', '2026_10_14_093000_add_slug.php');

        $located = $this->locator(['system', 'forum'])->locate('forum');

        self::assertCount(1, $located);
        self::assertSame('forum', $located[0]->source);
    }

    public function testASourceWithoutADirectoryContributesNothing(): void
    {
        $this->write('system', '2026_09_01_120000_create_users.php');

        self::assertCount(1, $this->locator(['system', 'never_installed'])->locate());
    }

    public function testFilesThatAreNotPhpAreIgnored(): void
    {
        $this->write('system', '2026_09_01_120000_create_users.php');
        file_put_contents($this->root . '/system/README.md', 'not a migration');

        self::assertCount(1, $this->locator(['system'])->locate());
    }

    /**
     * A migration quietly skipped is a schema change that never happens, and the failure surfaces
     * somewhere else entirely.
     */
    public function testAFileNamedLikeAnythingElseIsFatal(): void
    {
        $this->write('system', 'create_users.php');

        $this->expectException(InvalidMigrationFileException::class);
        $this->expectExceptionMessageMatches('/is not named like a migration/');

        $this->locator(['system'])->locate();
    }

    public function testTwoFilesOfOneSourceCannotShareAVersion(): void
    {
        $this->write('system', '2026_09_01_120000_create_users.php');
        $this->write('system', '2026_09_01_120000_create_files.php');

        $this->expectException(InvalidMigrationFileException::class);
        $this->expectExceptionMessageMatches('/claim the same version/');

        $this->locator(['system'])->locate();
    }

    /**
     * Two modules reaching the same minute is ordinary, and the journal tells them apart by the
     * source they came from.
     */
    public function testTwoSourcesMayShareAVersion(): void
    {
        $this->write('system', '2026_09_01_120000_create_users.php');
        $this->write('forum', '2026_09_01_120000_create_topics.php');

        self::assertCount(2, $this->locator(['system', 'forum'])->locate());
    }

    /**
     * @param list<string> $names
     */
    private function locator(array $names): MigrationLocator
    {
        $sources = array_map(fn (string $name): MigrationSource => new MigrationSource($name, $this->root . '/' . $name), $names);

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

        return new MigrationLocator([$provider]);
    }

    private function write(string $source, string $fileName): void
    {
        $directory = $this->root . '/' . $source;
        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        file_put_contents($directory . '/' . $fileName, "<?php\n");
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
