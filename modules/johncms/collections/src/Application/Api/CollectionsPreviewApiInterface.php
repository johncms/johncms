<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;

interface CollectionsPreviewApiInterface
{
    /** Requires collections.manage for the current identity, including when invoked outside HTTP. */
    public function findItem(int $collectionId, int $id): ?CollectionItemDTO;
}
