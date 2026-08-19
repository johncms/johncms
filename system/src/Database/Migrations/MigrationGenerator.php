<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Database\Migrations;

use Johncms\Database\Migrations\Exceptions\UnknownMigrationSourceException;
use RuntimeException;

/**
 * Writes a new, empty migration into the directory of its source.
 *
 * Separate from the command so that what it produces can be checked without a terminal, and so
 * that the name it picks is decided in one place: the version has to be free within the source,
 * or the two files would be indistinguishable in the journal.
 */
final readonly class MigrationGenerator
{
    public function __construct(
        private MigrationLocator $locator,
        private string $stubPath = ROOT_PATH . 'system/stubs/migrations',
    ) {
    }

    /**
     * @return string The path of the file that was written.
     * @throws UnknownMigrationSourceException
     * @throws RuntimeException
     */
    public function create(string $source, string $name, ?string $table = null, bool $creatingTable = false): string
    {
        $directory = $this->directoryOf($source);
        $name = $this->normalize($name);

        if ($creatingTable && ($table === null || $table === '')) {
            throw new RuntimeException('A migration that creates a table has to be told which one: pass --table.');
        }

        if ($table !== null && preg_match('/^[a-z0-9_]+$/', $table) !== 1) {
            throw new RuntimeException(sprintf('"%s" is not a table name: lower case letters, digits and underscores only.', $table));
        }

        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            throw new RuntimeException(sprintf('Could not create the migrations directory %s.', $directory));
        }

        $path = $directory . DIRECTORY_SEPARATOR . $this->freeVersion($directory) . '_' . $name . '.php';
        $contents = str_replace('{{ table }}', (string) $table, $this->stub($table, $creatingTable));

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException(sprintf('Could not write the migration %s.', $path));
        }

        return $path;
    }

    /**
     * @throws UnknownMigrationSourceException
     */
    private function directoryOf(string $source): string
    {
        $known = [];
        foreach ($this->locator->sources() as $candidate) {
            if ($candidate->name === $source) {
                return $candidate->directory;
            }

            $known[] = $candidate->name;
        }

        throw new UnknownMigrationSourceException(
            sprintf('There is no source named "%s". Known sources: %s.', $source, implode(', ', $known))
        );
    }

    private function normalize(string $name): string
    {
        $normalized = trim(strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '_', $name)), '_');

        if ($normalized === '') {
            throw new RuntimeException('A migration needs a name saying what it does, for example add_slug_to_sections.');
        }

        return $normalized;
    }

    /**
     * The current second, or the first one after it that no migration of this source has taken:
     * two files of one source sharing a version cannot be told apart in the journal.
     */
    private function freeVersion(string $directory): string
    {
        $taken = static fn (int $moment): bool =>
            (glob($directory . DIRECTORY_SEPARATOR . date('Y_m_d_His', $moment) . '_*.php') ?: []) !== [];

        $moment = time();
        while ($taken($moment)) {
            ++$moment;
        }

        return date('Y_m_d_His', $moment);
    }

    private function stub(?string $table, bool $creatingTable): string
    {
        $stub = match (true) {
            $creatingTable                      => 'migration.create.stub',
            $table !== null && $table !== ''     => 'migration.update.stub',
            default                             => 'migration.stub',
        };

        $contents = @file_get_contents($this->stubPath . DIRECTORY_SEPARATOR . $stub);

        if ($contents === false) {
            throw new RuntimeException(sprintf('The migration template %s is missing.', $stub));
        }

        return $contents;
    }
}
