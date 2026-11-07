<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

interface CollectionFileRepositoryInterface
{
    public function createContext(string $token, int $collectionId, int $userId, ?int $itemId): void;

    /** @return array{collection_id: int, user_id: int, item_id: ?int, created_at: string}|null */
    public function context(string $token, bool $lock = false): ?array;

    public function register(int $fileId, bool $managed): void;

    /** Current references to committed content or defaults of the collection. @return list<int> */
    public function availableForCollection(int $collectionId, bool $current = true): array;

    /** Null item identifies the schema default. Returns replaced references for deferred cleanup.
     * @param list<int> $fileIds
     * @return list<int>
     */
    public function replaceValueLinks(int $fieldId, int $itemId, array $fileIds): array;

    /** @return list<int> */
    public function unlinkField(int $fieldId): array;

    /** @return list<int> */
    public function fieldItems(int $fieldId): array;

    /** Lock the asset before changing any references. Returns whether collections own its bytes. */
    public function lockAsset(int $fileId): bool;

    public function setManaged(int $fileId, bool $managed): void;

    /** @return list<int> */
    public function itemFiles(int $itemId, bool $current = false): array;

    public function linkContext(string $token, int $fileId, ?int $fieldId = null): void;

    /** @return list<int> */
    public function contextFiles(string $token, bool $current = false): array;

    public function touchContext(string $token, string $createdAt): void;

    /** @return list<int> Items whose text or field values mention the address. */
    public function itemsMentioning(string $address): array;

    public function claim(string $token, int $itemId): void;

    public function linkItem(int $itemId, int $fileId): void;

    /** @return list<int> */
    public function unlinkItem(int $itemId): array;

    /** @return list<int> */
    public function deleteContext(string $token): array;

    public function hasLinks(int $fileId, bool $current = false): bool;

    public function removeAsset(int $fileId): void;

    /** @return list<string> */
    public function expiredContexts(string $before, int $limit): array;
}
