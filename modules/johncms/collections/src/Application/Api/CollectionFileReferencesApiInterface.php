<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

interface CollectionFileReferencesApiInterface
{
    /** Use inside a cleanup transaction: locks the asset and reads current references. */
    public function isReferenced(int $fileId): bool;
}
