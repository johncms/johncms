<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\UseCases;

use Johncms\Modules\ModuleRegistry;
use Johncms\Modules\ModuleState;
use Johncms\Modules\ModuleStatus;

/**
 * What the modules screen shows: everything the site knows of, split by what can be done with it.
 */
final readonly class GetModulesOverviewUseCase
{
    public function __construct(private ModuleRegistry $registry)
    {
    }

    /**
     * @return array{installed: list<ModuleState>, available: list<ModuleState>, problems: list<ModuleState>}
     */
    public function execute(): array
    {
        $installed = [];
        $available = [];
        $problems = [];

        foreach ($this->registry->states() as $state) {
            match ($state->status) {
                ModuleStatus::Enabled, ModuleStatus::Disabled => $installed[] = $state,
                ModuleStatus::Discovered                      => $available[] = $state,
                ModuleStatus::Broken, ModuleStatus::Incompatible => $problems[] = $state,
            };
        }

        return ['installed' => $installed, 'available' => $available, 'problems' => $problems];
    }
}
