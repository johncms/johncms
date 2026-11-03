<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

interface CollectionFileReferencesApiInterface
{
    /** Use inside a cleanup transaction: locks the asset and reads current references. */
    /** Transfer physical cleanup responsibility inside the caller's transaction, under its relation lock. */
    public function transferOwnership(int $fileId, bool $toCollections): void;

    public function isReferenced(int $fileId): bool;
}
