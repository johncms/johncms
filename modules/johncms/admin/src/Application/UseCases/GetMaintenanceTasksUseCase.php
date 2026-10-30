<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use DateTimeImmutable;
use Gettext\TranslatorFunctions;
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
            // A task that needs arguments is queued from the screen that has them, not from here.
            if (! $definition->listed) {
                continue;
            }

            $state = $this->storage->getState($definition->commandName);

            $tasks[] = new MaintenanceTaskDTO(
                commandName: $definition->commandName,
                title: $this->translate($definition->domain, $definition->title),
                description: $definition->description === null ? null : $this->translate($definition->domain, $definition->description),
                background: $definition->background,
                group: $definition->group->value,
                status: $state?->status->value,
                exitCode: $state?->exitCode,
                output: $state?->output ?? '',
                finishedAt: $this->formatDate($state?->finishedAt),
            );
        }

        return $tasks;
    }

    /**
     * The title and the description are written in English in the attribute, and i18n:scan
     * collects them into the domain it names. Looked up through the translator rather than d__():
     * the scanner takes every d__() call for a literal to extract.
     */
    private function translate(string $domain, string $original): string
    {
        return TranslatorFunctions::getTranslator()->dgettext($domain, $original);
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
