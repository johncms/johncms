<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

interface CollectionCodeCacheInterface
{
    /**
     * Map of active collection code => id.
     *
     * @return array<string, int>
     */
    public function map(): array;

    public function invalidate(): void;
}
