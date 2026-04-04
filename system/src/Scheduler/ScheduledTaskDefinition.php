<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

final readonly class ScheduledTaskDefinition
{
    /**
     * @param array<string, scalar|array<array-key, scalar|null>|null> $arguments
     */
    public function __construct(
        public string $commandName,
        public string $commandClass,
        public string $expression,
        public ?string $timezone,
        public array $arguments,
        public bool $withoutOverlapping,
        public ?string $description,
    ) {
    }

    public function lockKey(): string
    {
        return implode('|', [$this->commandName, $this->expression, json_encode($this->arguments) ?: '[]']);
    }
}
