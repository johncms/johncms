<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionBulkPreviewDTO;

/** Visitor-aware atomic operations, with an obligatory current confirmation revision. */
interface CollectionBulkApiInterface
{
    /** @param list<int> $ids */
    public function preview(int $collectionId, array $ids, string $action, ?int $targetSectionId = null): CollectionBulkPreviewDTO;

    /** Returns the number of processed items; validation and mandatory-handler failures roll back the operation.
     * @param list<int> $ids
     */
    public function execute(int $collectionId, array $ids, string $action, string $revision, ?int $targetSectionId = null): int;
}
