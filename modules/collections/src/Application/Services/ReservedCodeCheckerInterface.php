<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

interface ReservedCodeCheckerInterface
{
    /**
     * True when the code collides with a top-level URL segment already claimed by
     * a real route (or a reserved filesystem path), which would make a collection
     * with that code unreachable.
     */
    public function isReserved(string $code): bool;
}
