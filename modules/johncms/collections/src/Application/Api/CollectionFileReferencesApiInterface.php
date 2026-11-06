<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

interface CollectionFileReferencesApiInterface
{
    /** Use inside a cleanup transaction: locks the asset and reads current references. */
    /** Transfer physical cleanup responsibility inside the caller's transaction, under its relation lock. */
    public function transferOwnership(int $fileId, bool $toCollections): void;

    /** Linked to a record, or shown in the text of one, which then holds it. */
    public function isReferenced(int $fileId): bool;

    /**
     * Trusted system operations only: register existing files for a record without copying them.
     * @param list<int> $fileIds
     */
    public function registerExisting(int $collectionId, int $itemId, array $fileIds, bool $transferOwnership = false): void;

    /** @return list<int> */
    public function registeredFileIds(int $collectionId, int $itemId): array;
}
