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

use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Tells the progress of a run to whoever started it from a terminal.
 *
 * Not a service: it is built around the output of one command.
 */
final readonly class ConsoleMigrationReporter implements MigrationReporterInterface
{
    public function __construct(private SymfonyStyle $io)
    {
    }

    public function starting(MigrationFile $migration, MigrationDirection $direction): void
    {
        $this->io->text(sprintf('  running %s / %s', $migration->source, $migration->name));
    }

    public function finished(MigrationFile $migration, MigrationDirection $direction, int $durationMs): void
    {
        $this->io->text(
            sprintf(
                '  <info>%s</info> %s / %s <comment>(%d ms)</comment>',
                $direction === MigrationDirection::Up ? 'applied' : 'rolled back',
                $migration->source,
                $migration->name,
                $durationMs
            )
        );
    }

    public function failed(MigrationFile $migration, MigrationDirection $direction, Throwable $exception): void
    {
        $this->io->text(sprintf('  <error>failed</error> %s / %s', $migration->source, $migration->name));
    }
}
