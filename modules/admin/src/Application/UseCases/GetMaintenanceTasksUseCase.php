<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use DateTimeImmutable;
use DateTimeInterface;
use Johncms\AdminTasks\AdminTaskRegistry;
use Johncms\AdminTasks\FileAdminTaskStorage;
use Johncms\Modules\Admin\Application\DTO\MaintenanceTaskDTO;

final readonly class GetMaintenanceTasksUseCase
{
    public function __construct(
        private AdminTaskRegistry $registry,
        private FileAdminTaskStorage $storage,
    ) {
    }

    /**
     * @return list<MaintenanceTaskDTO>
     */
    public function execute(): array
    {
        $tasks = [];

        foreach ($this->registry->all() as $definition) {
            $state = $this->storage->getState($definition->commandName);

            $tasks[] = new MaintenanceTaskDTO(
                commandName: $definition->commandName,
                title: $definition->title,
                description: $definition->description,
                background: $definition->background,
                status: $state?->status->value,
                exitCode: $state?->exitCode,
                output: $state?->output ?? '',
                finishedAt: $this->formatDate($state?->finishedAt),
            );
        }

        return $tasks;
    }

    private function formatDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }

        $dateTime = DateTimeImmutable::createFromFormat(DateTimeInterface::ATOM, $date);

        return $dateTime === false ? null : $dateTime->format('d.m.Y H:i');
    }
}
