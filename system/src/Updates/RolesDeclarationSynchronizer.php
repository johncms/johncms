<?php

declare(strict_types=1);

namespace Johncms\Updates;

use Johncms\Auth\Authorization\DefaultPermissionsApplier;
use Johncms\Auth\Authorization\RoleSeeder;

final readonly class RolesDeclarationSynchronizer implements DeclarationSynchronizerInterface
{
    public function __construct(private RoleSeeder $seeder, private DefaultPermissionsApplier $applier)
    {
    }

    public function owner(): string
    {
        return 'system';
    }

    public function synchronize(): void
    {
        $this->seeder->seed();
        $this->applier->apply();
    }

    public function validate(): void
    {
        // Both operations add missing declarations and leave custom grants intact.
    }
}
