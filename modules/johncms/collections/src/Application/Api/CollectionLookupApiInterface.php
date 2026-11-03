<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;

/** Lightweight trusted lookup suitable for lifecycle handlers without writer dependencies. */
interface CollectionLookupApiInterface
{
    public function findDeclared(string $owner, string $key): ?CollectionRecordDTO;
}
