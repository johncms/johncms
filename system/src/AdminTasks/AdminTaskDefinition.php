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
        /** A task that needs arguments has nothing to offer a screen of buttons. */
        public bool $listed = true,
    ) {
    }

    public function lockKey(): string
    {
        return 'admin-task|' . $this->commandName;
    }
}
