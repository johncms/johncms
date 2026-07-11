<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

final readonly class AdminTaskDefinition
{
    public function __construct(
        public string $commandName,
        public string $commandClass,
        public string $title,
        public ?string $description,
        public bool $background,
    ) {
    }

    public function lockKey(): string
    {
        return 'admin-task|' . $this->commandName;
    }
}
