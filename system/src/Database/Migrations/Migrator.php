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

use Johncms\Database\ConnectionInterface;
use Johncms\Database\Migrations\Exceptions\InvalidMigrationFileException;
use Johncms\Database\Migrations\Exceptions\IrreversibleMigrationException;
use Johncms\Database\Migrations\Exceptions\MigrationFailedException;
use Johncms\Database\Migrations\Exceptions\MigrationsLockedException;
use Johncms\Database\Schema\SchemaInterface;
use InvalidArgumentException;
use Johncms\Scheduler\ScheduleMutexInterface;
use ReflectionMethod;
use Throwable;

/**
 * Takes the database through the migrations it has not been through yet.
 *
 * Runs them one at a time and writes each into the journal as it goes, so a run that fails in the
 * middle leaves everything before the failure recorded and everything after it pending. The
 * failed one is not recorded: on a database that rolls back DDL it did nothing, and on one that
 * does not the exception says so.
 */
final readonly class Migrator implements MigrationRunnerInterface
{
    private const string LOCK_KEY = 'johncms.migrations';

    public function __construct(
        private MigrationLocator $locator,
        private MigrationRepositoryInterface $repository,
        private SchemaInterface $schema,
        private ConnectionInterface $connection,
        private ScheduleMutexInterface $mutex,
    ) {
    }

    /**
     * @return list<MigrationFile>
     */
    public function pending(?string $source = null): array
    {
        $applied = $this->appliedIds();

        return array_values(
            array_filter(
                $this->locator->locate($source),
                static fn (MigrationFile $migration): bool => ! isset($applied[$migration->id()])
            )
        );
    }

    /**
     * @return list<MigrationFile> What was applied, in the order it was applied.
     * @throws MigrationsLockedException
     * @throws MigrationFailedException
     */
    public function run(?string $source = null, MigrationReporterInterface $reporter = new NullMigrationReporter()): array
    {
        $pending = $this->pending($source);
        if ($pending === []) {
            return [];
        }

        $lock = $this->acquireLock();

        try {
            $this->repository->ensureStorageExists();
            $batch = $this->repository->nextBatch();

            $applied = [];
            foreach ($pending as $migration) {
                $duration = $this->execute($migration, MigrationDirection::Up, $reporter);
                // Journalled before it is called done, so that nothing is ever reported as
                // applied that the next run would apply again.
                $this->repository->log($migration, $batch, $duration);
                $reporter->finished($migration, MigrationDirection::Up, $duration);
                $applied[] = $migration;
            }

            return $applied;
        } finally {
            $this->mutex->release($lock);
        }
    }

    /**
     * Undoes the last batches. A tool of development: on a live site a rollback throws away what
     * the step forward carried.
     *
     * @return list<MigrationFile> What was rolled back, in the order it was rolled back.
     * @throws MigrationsLockedException
     * @throws MigrationFailedException
     * @throws IrreversibleMigrationException
     * @throws InvalidMigrationFileException
     */
    public function rollback(
        ?string $source = null,
        int $steps = 1,
        MigrationReporterInterface $reporter = new NullMigrationReporter(),
        bool $all = false,
    ): array {
        if ($all && ($source === null || $source === '')) {
            throw new InvalidArgumentException('Rolling everything back is only allowed for one source at a time.');
        }

        $target = $this->rollbackTargets($source, $steps, $all);
        if ($target === []) {
            return [];
        }

        $lock = $this->acquireLock();

        try {
            $rolledBack = [];
            foreach ($target as $migration) {
                $duration = $this->execute($migration['file'], MigrationDirection::Down, $reporter);
                $this->repository->forget($migration['applied']);
                $reporter->finished($migration['file'], MigrationDirection::Down, $duration);
                $rolledBack[] = $migration['file'];
            }

            return $rolledBack;
        } finally {
            $this->mutex->release($lock);
        }
    }

    /**
     * The applied migrations of a source that refuse to be undone — the ones that never said how.
     *
     * Asked before a module is uninstalled with its data: rolling back half a module and stopping
     * at the first migration that will not go back leaves a schema nobody can describe. Better to
     * refuse while nothing has happened, naming what would have been left behind.
     *
     * @return list<MigrationFile>
     */
    public function irreversible(string $source): array
    {
        $applied = [];
        foreach ($this->applied() as $record) {
            if ($record->source === $source) {
                $applied[$record->id()] = true;
            }
        }

        $irreversible = [];
        foreach ($this->locator->locate($source) as $file) {
            if (! isset($applied[$file->id()])) {
                continue;
            }

            $declaring = (new ReflectionMethod($this->load($file), 'down'))->getDeclaringClass()->getName();
            if ($declaring === Migration::class) {
                $irreversible[] = $file;
            }
        }

        return $irreversible;
    }

    /**
     * Every migration this site knows of, applied or not, plus the ones the journal remembers and
     * the disk no longer has.
     *
     * @return list<MigrationStatus>
     */
    public function status(?string $source = null): array
    {
        $applied = $this->applied();
        $statuses = [];

        foreach ($this->locator->locate($source) as $migration) {
            $record = $applied[$migration->id()] ?? null;
            unset($applied[$migration->id()]);

            $statuses[] = new MigrationStatus(
                source: $migration->source,
                version: $migration->version,
                name: $migration->name,
                isApplied: $record !== null,
                fileExists: true,
                batch: $record?->batch,
                appliedAt: $record?->appliedAt,
                checksumMatches: $record === null || $record->checksum === null || $record->checksum === $migration->checksum(),
            );
        }

        foreach ($applied as $record) {
            if ($source !== null && $record->source !== $source) {
                continue;
            }

            $statuses[] = new MigrationStatus(
                source: $record->source,
                version: $record->version,
                name: $record->name,
                isApplied: true,
                fileExists: false,
                batch: $record->batch,
                appliedAt: $record->appliedAt,
            );
        }

        return $statuses;
    }

    /**
     * @return list<array{file: MigrationFile, applied: AppliedMigration}>
     * @throws InvalidMigrationFileException
     */
    private function rollbackTargets(?string $source, int $steps, bool $all = false): array
    {
        $lastBatch = $this->repository->lastBatch();
        if ($lastBatch === 0) {
            return [];
        }

        // Undoing a source completely ignores the batches: what is being undone is not the last
        // step forward but everything a module ever did to this database.
        $firstBatch = $all ? 1 : max(1, $lastBatch - max(1, $steps) + 1);

        $wanted = [];
        foreach ($this->applied() as $record) {
            if ($record->batch < $firstBatch) {
                continue;
            }

            if ($source !== null && $record->source !== $source) {
                continue;
            }

            $wanted[$record->id()] = $record;
        }

        $targets = [];
        foreach (array_reverse($this->locator->locate($source)) as $migration) {
            $record = $wanted[$migration->id()] ?? null;
            if ($record === null) {
                continue;
            }

            unset($wanted[$migration->id()]);
            $targets[] = ['file' => $migration, 'applied' => $record];
        }

        if ($wanted !== []) {
            $missing = array_map(static fn (AppliedMigration $record): string => $record->id(), $wanted);

            throw new InvalidMigrationFileException(
                sprintf(
                    'The journal has migrations whose files are gone, so they cannot be rolled back: %s.',
                    implode(', ', $missing)
                )
            );
        }

        return $targets;
    }

    /**
     * @throws MigrationFailedException
     * @throws IrreversibleMigrationException
     * @return int How long the step took, in milliseconds.
     */
    private function execute(MigrationFile $file, MigrationDirection $direction, MigrationReporterInterface $reporter): int
    {
        $reporter->starting($file, $direction);

        $migration = $this->load($file);
        $migration->bind($this->schema, $this->connection);

        $startedAt = hrtime(true);

        try {
            if ($migration->useTransaction()) {
                $this->connection->transaction(static function () use ($migration, $direction): void {
                    $direction === MigrationDirection::Up ? $migration->up() : $migration->down();
                });
            } else {
                $direction === MigrationDirection::Up ? $migration->up() : $migration->down();
            }
        } catch (IrreversibleMigrationException $exception) {
            // Not a failure to report as one: the migration is refusing, and the caller has to be
            // able to tell that apart from a step that broke halfway through.
            $reporter->failed($file, $direction, $exception);

            throw $exception;
        } catch (Throwable $exception) {
            $reporter->failed($file, $direction, $exception);

            throw new MigrationFailedException($file, $direction, $exception);
        }

        return (int) round((hrtime(true) - $startedAt) / 1_000_000);
    }

    /**
     * @throws InvalidMigrationFileException
     */
    private function load(MigrationFile $file): Migration
    {
        /** @psalm-suppress UnresolvableInclude */
        $migration = require $file->path;

        if (! $migration instanceof Migration) {
            throw new InvalidMigrationFileException(
                sprintf('The migration %s of %s has to return an instance of %s.', $file->name, $file->source, Migration::class)
            );
        }

        return $migration;
    }

    /**
     * Two migrators against one database would apply the same step twice or interleave two halves
     * of the schema. Released in the finally of whoever took it.
     *
     * @throws MigrationsLockedException
     */
    private function acquireLock(): mixed
    {
        $lock = $this->mutex->acquire(self::LOCK_KEY);

        if ($lock === null) {
            throw new MigrationsLockedException('Migrations are already running. Wait for that run to finish before starting another.');
        }

        return $lock;
    }

    /**
     * @return array<string, AppliedMigration>
     */
    private function applied(): array
    {
        $applied = [];
        foreach ($this->repository->all() as $record) {
            $applied[$record->id()] = $record;
        }

        return $applied;
    }

    /**
     * @return array<string, true>
     */
    private function appliedIds(): array
    {
        return array_fill_keys(array_keys($this->applied()), true);
    }
}
