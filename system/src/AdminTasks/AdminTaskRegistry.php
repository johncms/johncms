<?php

declare(strict_types=1);

namespace Johncms\AdminTasks;

use ReflectionClass;
use Symfony\Component\Console\Command\Command;

final readonly class AdminTaskRegistry
{
    /**
     * @param iterable<Command> $commands
     */
    public function __construct(
        private iterable $commands,
    ) {
    }

    /**
     * @return list<AdminTaskDefinition>
     */
    public function all(): array
    {
        $definitions = [];

        foreach ($this->commands as $command) {
            $attributes = (new ReflectionClass($command))->getAttributes(AsAdminTask::class);
            if ($attributes === []) {
                continue;
            }

            $commandName = $command->getName();
            if ($commandName === null || $commandName === '') {
                continue;
            }

            /** @var AsAdminTask $adminTask */
            $adminTask = $attributes[0]->newInstance();

            $definitions[] = new AdminTaskDefinition(
                commandName: $commandName,
                commandClass: $command::class,
                title: $adminTask->title ?? $commandName,
                description: $adminTask->description ?? $command->getDescription(),
                background: $adminTask->background,
                listed: $adminTask->listed,
            );
        }

        usort(
            $definitions,
            static fn (AdminTaskDefinition $left, AdminTaskDefinition $right): int => $left->commandName <=> $right->commandName
        );

        return $definitions;
    }

    public function find(string $commandName): ?AdminTaskDefinition
    {
        foreach ($this->all() as $definition) {
            if ($definition->commandName === $commandName) {
                return $definition;
            }
        }

        return null;
    }
}
