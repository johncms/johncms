<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

interface CollectionFileRepositoryInterface
{
    public function createContext(string $token, int $collectionId, int $userId, ?int $itemId): void;

    /** @return array{collection_id: int, user_id: int, item_id: ?int, created_at: string}|null */
    public function context(string $token, bool $lock = false): ?array;

    public function register(int $fileId, bool $managed): void;

    /** Lock the asset before changing any references. Returns whether collections own its bytes. */
    public function lockAsset(int $fileId): bool;

    public function linkContext(string $token, int $fileId): void;

    /** @return list<int> */
    public function contextFiles(string $token): array;

    public function claim(string $token, int $itemId): void;

    public function linkItem(int $itemId, int $fileId): void;

    /** @return list<int> */
    public function unlinkItem(int $itemId): array;

    /** @return list<int> */
    public function deleteContext(string $token): array;

    public function hasLinks(int $fileId): bool;

    public function removeAsset(int $fileId): void;

    /** @return list<string> */
    public function expiredContexts(string $before, int $limit): array;
}
