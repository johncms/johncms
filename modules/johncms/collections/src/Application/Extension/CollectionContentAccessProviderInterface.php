<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Extension;

/** Additional owner restrictions, applied before user content access (including schema managers). */
interface CollectionContentAccessProviderInterface
{
    public function assertAvailable(int $collectionId): void;
}
