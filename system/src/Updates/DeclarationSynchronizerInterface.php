<?php

declare(strict_types=1);

namespace Johncms\Updates;

/** Safe synchronization runs before hooks; validation runs after every hook. */
interface DeclarationSynchronizerInterface
{
    /** The owning module key, or "system" for the core. */
    public function owner(): string;

    public function synchronize(): void;

    /** Throw an actionable exception if declarations still differ. */
    public function validate(): void;
}
