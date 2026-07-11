<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

final readonly class AdminTaskState
{
    public function __construct(
        public string $commandName,
        public AdminTaskStatus $status,
        public ?int $exitCode,
        public string $output,
        public ?string $queuedAt,
        public ?string $startedAt,
        public ?string $finishedAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): ?self
    {
        $commandName = $data['command'] ?? null;
        $status = AdminTaskStatus::tryFrom((string) ($data['status'] ?? ''));

        if (! is_string($commandName) || $commandName === '' || $status === null) {
            return null;
        }

        return new self(
            commandName: $commandName,
            status: $status,
            exitCode: isset($data['exitCode']) ? (int) $data['exitCode'] : null,
            output: (string) ($data['output'] ?? ''),
            queuedAt: isset($data['queuedAt']) ? (string) $data['queuedAt'] : null,
            startedAt: isset($data['startedAt']) ? (string) $data['startedAt'] : null,
            finishedAt: isset($data['finishedAt']) ? (string) $data['finishedAt'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'command'    => $this->commandName,
            'status'     => $this->status->value,
            'exitCode'   => $this->exitCode,
            'output'     => $this->output,
            'queuedAt'   => $this->queuedAt,
            'startedAt'  => $this->startedAt,
            'finishedAt' => $this->finishedAt,
        ];
    }
}
