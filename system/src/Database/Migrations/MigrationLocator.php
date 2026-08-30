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

use Johncms\Database\Migrations\Exceptions\DuplicateMigrationSourceException;
use Johncms\Database\Migrations\Exceptions\InvalidMigrationFileException;

/**
 * Finds the migrations of every source and puts them in the one order they may be applied in:
 * sources in the order the providers name them, and inside a source by version.
 */
final readonly class MigrationLocator
{
    private const string FILE_PATTERN = '/^(\d{4}_\d{2}_\d{2}_\d{6})_([a-z0-9_]+)\.php$/';

    /**
     * @param iterable<MigrationSourceProviderInterface> $providers
     */
    public function __construct(private iterable $providers)
    {
    }

    /**
     * Every source, and no two of them under one name.
     *
     * The name is what the journal of migrations records. Two directories answering to it would be
     * one history describing both, and rolling that source back would walk through the migrations
     * of whoever else claimed the name — a module whose alias is "system" would be asking the CMS
     * to undo its own schema. Refused here, before a single file is read.
     *
     * @return list<MigrationSource>
     * @throws DuplicateMigrationSourceException
     */
    public function sources(): array
    {
        $sources = [];
        $seen = [];

        foreach ($this->providers as $provider) {
            foreach ($provider->sources() as $source) {
                if (isset($seen[$source->name])) {
                    throw new DuplicateMigrationSourceException(
                        sprintf(
                            'Two sources of migrations answer to "%s": "%s" and "%s".',
                            $source->name,
                            $seen[$source->name],
                            $source->directory
                        )
                    );
                }

                $seen[$source->name] = $source->directory;
                $sources[] = $source;
            }
        }

        return $sources;
    }

    /**
     * @param string|null $source Limits the answer to one source; null means all of them.
     * @return list<MigrationFile>
     * @throws InvalidMigrationFileException
     */
    public function locate(?string $source = null): array
    {
        $located = [];
        foreach ($this->sources() as $candidate) {
            if ($source !== null && $candidate->name !== $source) {
                continue;
            }

            foreach ($this->locateIn($candidate) as $file) {
                $located[] = $file;
            }
        }

        return $located;
    }

    /**
     * @return list<MigrationFile>
     * @throws InvalidMigrationFileException
     */
    private function locateIn(MigrationSource $source): array
    {
        if (! is_dir($source->directory)) {
            return [];
        }

        $paths = glob(rtrim($source->directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($paths);

        $files = [];
        $seen = [];

        foreach ($paths as $path) {
            $file = $this->describe($source, $path);

            if (isset($seen[$file->version])) {
                throw new InvalidMigrationFileException(
                    sprintf(
                        'The migrations %s and %s of %s claim the same version %s, and the journal cannot tell them apart.',
                        basename($seen[$file->version]),
                        basename($path),
                        $source->name,
                        $file->version
                    )
                );
            }

            $seen[$file->version] = $path;
            $files[] = $file;
        }

        return $files;
    }

    /**
     * @throws InvalidMigrationFileException
     */
    private function describe(MigrationSource $source, string $path): MigrationFile
    {
        $fileName = basename($path);

        if (preg_match(self::FILE_PATTERN, $fileName, $matches) !== 1) {
            throw new InvalidMigrationFileException(
                sprintf(
                    'The file %s in the migrations of %s is not named like a migration. Expected 2026_09_01_120000_what_it_does.php.',
                    $fileName,
                    $source->name
                )
            );
        }

        return new MigrationFile(
            source: $source->name,
            version: str_replace('_', '', $matches[1]),
            name: $matches[2],
            path: $path,
        );
    }
}
