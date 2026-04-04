<?php

declare(strict_types=1);

namespace Johncms\Scheduler;

use ReflectionClass;
use Symfony\Component\Console\Command\Command;

final readonly class ScheduledTaskRegistry
{
    /**
     * @param iterable<Command> $commands
     */
    public function __construct(
        private iterable $commands,
    ) {
    }

    /**
     * @return list<ScheduledTaskDefinition>
     */
    public function all(): array
    {
        $definitions = [];

        foreach ($this->commands as $command) {
            $attributes = (new ReflectionClass($command))->getAttributes(AsScheduledTask::class);
            if ($attributes === []) {
                continue;
            }

            $commandName = $command->getName();
            if ($commandName === null || $commandName === '') {
                continue;
            }

            foreach ($attributes as $attribute) {
                /** @var AsScheduledTask $scheduledTask */
                $scheduledTask = $attribute->newInstance();

                $definitions[] = new ScheduledTaskDefinition(
                    commandName: $commandName,
                    commandClass: $command::class,
                    expression: $scheduledTask->expression,
                    timezone: $scheduledTask->timezone,
                    arguments: $scheduledTask->arguments,
                    withoutOverlapping: $scheduledTask->withoutOverlapping,
                    description: $scheduledTask->description ?? $command->getDescription(),
                );
            }
        }

        usort(
            $definitions,
            static fn (ScheduledTaskDefinition $left, ScheduledTaskDefinition $right): int => $left->commandName <=> $right->commandName
        );

        return $definitions;
    }
}
