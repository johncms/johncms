<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class MaintenanceTaskDTO
{
    public function __construct(
        public string $commandName,
        public string $title,
        public ?string $description,
        public bool $background,
        public ?string $status,
        public ?int $exitCode,
        public string $output,
        public ?string $finishedAt,
    ) {
    }

    public function isPending(): bool
    {
        return $this->status === 'queued' || $this->status === 'running';
    }
}
