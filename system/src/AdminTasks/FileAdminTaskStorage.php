<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

use DateTimeImmutable;
use DateTimeInterface;

/**
 * File-based state store for admin maintenance tasks.
 *
 * Keeps one JSON file per command under data/admin_tasks/state.
 * The directory is intentionally outside CACHE_PATH so that the
 * cache:clear task cannot wipe the queue while it is being processed.
 */
final class FileAdminTaskStorage
{
    private const STALE_RUN_SECONDS = 3600;

    public function __construct(
        private readonly string $directory = DATA_PATH . 'admin_tasks' . DS . 'state',
    ) {
    }

    /**
     * @param array<string, scalar> $arguments Kept with the task: the scheduler runs it later, in
     *                                         another process, and "install the blog module" is
     *                                         not the same task as "install the shop module".
     */
    public function queue(string $commandName, array $arguments = []): void
    {
        $this->write(new AdminTaskState(
            commandName: $commandName,
            status: AdminTaskStatus::Queued,
            exitCode: null,
            output: '',
            queuedAt: $this->now(),
            startedAt: null,
            finishedAt: null,
            arguments: $arguments,
        ));
    }

    public function markRunning(string $commandName): void
    {
        $current = $this->getState($commandName);

        $this->write(new AdminTaskState(
            commandName: $commandName,
            status: AdminTaskStatus::Running,
            exitCode: null,
            output: '',
            queuedAt: $current?->queuedAt,
            startedAt: $this->now(),
            finishedAt: null,
        ));
    }

    public function storeResult(string $commandName, int $exitCode, string $output): void
    {
        $current = $this->getState($commandName);

        $this->write(new AdminTaskState(
            commandName: $commandName,
            status: $exitCode === 0 ? AdminTaskStatus::Done : AdminTaskStatus::Failed,
            exitCode: $exitCode,
            output: $output,
            queuedAt: $current?->queuedAt,
            startedAt: $current?->startedAt,
            finishedAt: $this->now(),
        ));
    }

    public function getState(string $commandName): ?AdminTaskState
    {
        return $this->read($this->pathFor($commandName));
    }

    /**
     * @return list<string>
     */
    /**
     * The whole state rather than the name: the arguments a task was queued with are part of it,
     * and whoever runs it needs them.
     *
     * @return list<AdminTaskState>
     */
    public function getQueued(): array
    {
        $queued = [];

        foreach ($this->allStates() as $state) {
            if ($state->status === AdminTaskStatus::Queued) {
                $queued[] = $state;
            }
        }

        usort($queued, static fn (AdminTaskState $a, AdminTaskState $b): int => $a->commandName <=> $b->commandName);

        return $queued;
    }

    /**
     * Mark tasks stuck in the running state (e.g. after a fatal error) as failed.
     */
    public function healStale(DateTimeImmutable $now, int $maxRunSeconds = self::STALE_RUN_SECONDS): void
    {
        foreach ($this->allStates() as $state) {
            if ($state->status !== AdminTaskStatus::Running || $state->startedAt === null) {
                continue;
            }

            $startedAt = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $state->startedAt);
            if ($startedAt === false || $now->getTimestamp() - $startedAt->getTimestamp() < $maxRunSeconds) {
                continue;
            }

            $this->write(new AdminTaskState(
                commandName: $state->commandName,
                status: AdminTaskStatus::Failed,
                exitCode: null,
                output: 'The task did not finish and was marked as failed.',
                queuedAt: $state->queuedAt,
                startedAt: $state->startedAt,
                finishedAt: $now->format(DateTimeInterface::ATOM),
            ));
        }
    }

    /**
     * @return list<AdminTaskState>
     */
    private function allStates(): array
    {
        if (! is_dir($this->directory)) {
            return [];
        }

        $states = [];
        foreach (glob($this->directory . DS . '*.json') ?: [] as $path) {
            $state = $this->read($path);
            if ($state !== null) {
                $states[] = $state;
            }
        }

        return $states;
    }

    private function read(string $path): ?AdminTaskState
    {
        if (! is_file($path)) {
            return null;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return null;
        }

        try {
            if (! flock($handle, LOCK_SH)) {
                return null;
            }

            $content = stream_get_contents($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }

        if ($content === false || $content === '') {
            return null;
        }

        $data = json_decode($content, true);

        return is_array($data) ? AdminTaskState::fromArray($data) : null;
    }

    private function write(AdminTaskState $state): void
    {
        if (! is_dir($this->directory) && ! mkdir($this->directory, 0777, true) && ! is_dir($this->directory)) {
            return;
        }

        $handle = fopen($this->pathFor($state->commandName), 'c+');
        if ($handle === false) {
            return;
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                return;
            }

            ftruncate($handle, 0);
            fwrite($handle, json_encode($state->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
            fflush($handle);
            flock($handle, LOCK_UN);
        } finally {
            fclose($handle);
        }
    }

    private function pathFor(string $commandName): string
    {
        return $this->directory . DS . hash('sha256', $commandName) . '.json';
    }

    private function now(): string
    {
        return (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
    }
}
