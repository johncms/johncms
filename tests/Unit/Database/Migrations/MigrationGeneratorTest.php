<?php

declare(strict_types=1);

namespace Tests\Unit\Database\Migrations;

use Johncms\Database\Migrations\Exceptions\UnknownMigrationSourceException;
use Johncms\Database\Migrations\Migration;
use Johncms\Database\Migrations\MigrationGenerator;
use Johncms\Database\Migrations\MigrationLocator;
use Johncms\Database\Migrations\MigrationSource;
use Johncms\Database\Migrations\MigrationSourceProviderInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class MigrationGeneratorTest extends TestCase
{
    private string $root;

    private MigrationGenerator $generator;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . '/johncms-migration-generator-' . bin2hex(random_bytes(4));

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

        $this->generator = new MigrationGenerator(new MigrationLocator([$provider]));
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
    }

    public function testTheFileLandsInTheDirectoryOfItsSource(): void
    {
        $path = $this->generator->create('forum', 'add_slug_to_sections');

        self::assertFileExists($path);
        self::assertStringStartsWith($this->root . '/forum/', $path);
        self::assertMatchesRegularExpression('/\d{4}_\d{2}_\d{2}_\d{6}_add_slug_to_sections\.php$/', $path);
    }

    public function testAMigrationThatCreatesATableIsReadyToRun(): void
    {
        $path = $this->generator->create('system', 'create_widgets_table', 'widgets', creatingTable: true);
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString("\$this->schema->create('widgets'", $contents);
        self::assertStringContainsString("\$this->schema->dropIfExists('widgets')", $contents);
        self::assertStringNotContainsString('{{ table }}', $contents);
        self::assertInstanceOf(Migration::class, require $path);
    }

    public function testAMigrationThatChangesATableStartsFromAnAlter(): void
    {
        $path = $this->generator->create('forum', 'add_slug', 'forum_sections');
        $contents = (string) file_get_contents($path);

        self::assertStringContainsString("\$this->schema->alter('forum_sections'", $contents);
        self::assertInstanceOf(Migration::class, require $path);
    }

    public function testAMigrationWithoutATableIsBlank(): void
    {
        $path = $this->generator->create('system', 'move_things_around');
        $contents = (string) file_get_contents($path);

        self::assertStringNotContainsString('$this->schema', $contents);
        self::assertInstanceOf(Migration::class, require $path);
    }

    public function testTheNameIsBroughtToTheOneShapeAFileNameCanHave(): void
    {
        $path = $this->generator->create('system', 'Add Slug, To Sections!');

        self::assertStringEndsWith('_add_slug_to_sections.php', $path);
    }

    public function testANameThatSaysNothingIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/needs a name/');

        $this->generator->create('system', '!!!');
    }

    public function testCreatingATableWithoutSayingWhichOneIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/has to be told which one/');

        $this->generator->create('system', 'create_widgets_table', creatingTable: true);
    }

    public function testSomethingThatIsNotATableNameIsRefused(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/is not a table name/');

        $this->generator->create('system', 'add_slug', 'Forum Sections');
    }

    public function testAnUnknownSourceSaysWhichOnesExist(): void
    {
        $this->expectException(UnknownMigrationSourceException::class);
        $this->expectExceptionMessage('Known sources: system, forum.');

        $this->generator->create('downloads', 'add_slug');
    }

    /**
     * Two files of one source sharing a version could not be told apart in the journal, so the
     * second one is moved on to the next free second.
     */
    public function testTwoMigrationsMadeInTheSameSecondGetDifferentVersions(): void
    {
        $first = $this->generator->create('system', 'first');
        $second = $this->generator->create('system', 'second');

        self::assertFileExists($first);
        self::assertFileExists($second);
        self::assertNotSame($this->version($first), $this->version($second));
    }

    private function version(string $path): string
    {
        return substr(basename($path), 0, 17);
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
