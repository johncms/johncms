<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Johncms\Modules\Collections\Domain\Models\CollectionUploadContext;
use Johncms\Modules\Collections\Domain\Models\CollectionFileAsset;
use Johncms\Modules\Collections\Domain\Models\CollectionFileLink;
use Johncms\Modules\Collections\Domain\Repository\CollectionFileRepositoryInterface;

final class CollectionFileRepository implements CollectionFileRepositoryInterface
{
    public function createContext(string $token, int $collectionId, int $userId, ?int $itemId): void
    {
        CollectionUploadContext::query()->create(['token' => $token, 'collection_id' => $collectionId, 'user_id' => $userId, 'item_id' => $itemId, 'created_at' => gmdate('Y-m-d H:i:s')]);
    }

    public function context(string $token, bool $lock = false): ?array
    {
        $query = CollectionUploadContext::query();
        /** @var CollectionUploadContext|null $row */
        $row = ($lock ? $query->lockForUpdate() : $query)->find($token);
        return $row === null ? null : ['collection_id' => $row->getAttribute('collection_id'), 'user_id' => $row->getAttribute('user_id'), 'item_id' => $row->getAttribute('item_id'), 'created_at' => $row->getAttribute('created_at')];
    }

    public function register(int $fileId, bool $managed): void
    {
        CollectionFileAsset::query()->firstOrCreate(['file_id' => $fileId], ['managed' => $managed]);
        if ($managed) {
            CollectionFileAsset::query()->where('file_id', $fileId)->update(['managed' => true]);
        }
    }

    public function lockAsset(int $fileId): bool
    {
        /** @var CollectionFileAsset|null $row */
        $row = CollectionFileAsset::query()->lockForUpdate()->find($fileId);
        return $row?->getAttribute('managed') === true;
    }

    public function linkContext(string $token, int $fileId): void
    {
        CollectionFileLink::query()->firstOrCreate(['context_token' => $token, 'file_id' => $fileId], ['item_id' => null]);
    }

    public function contextFiles(string $token): array
    {
        return CollectionFileLink::query()->where('context_token', $token)->orderBy('file_id')->pluck('file_id')->all();
    }

    public function claim(string $token, int $itemId): void
    {
        foreach ($this->contextFiles($token) as $fileId) {
            $this->lockAsset($fileId);
            $this->linkItem($itemId, $fileId);
        }
        $this->deleteContext($token);
    }

    public function linkItem(int $itemId, int $fileId): void
    {
        CollectionFileLink::query()->firstOrCreate(['item_id' => $itemId, 'file_id' => $fileId], ['context_token' => null]);
    }

    public function unlinkItem(int $itemId): array
    {
        $ids = CollectionFileLink::query()->where('item_id', $itemId)->orderBy('file_id')->pluck('file_id')->all();
        foreach ($ids as $fileId) {
            $this->lockAsset($fileId);
        }
        CollectionFileLink::query()->where('item_id', $itemId)->delete();
        return $ids;
    }

    public function deleteContext(string $token): array
    {
        $ids = $this->contextFiles($token);
        foreach ($ids as $fileId) {
            $this->lockAsset($fileId);
        }
        CollectionFileLink::query()->where('context_token', $token)->delete();
        CollectionUploadContext::query()->where('token', $token)->delete();
        return $ids;
    }

    public function hasLinks(int $fileId): bool
    {
        return CollectionFileLink::query()->where('file_id', $fileId)->exists();
    }

    public function removeAsset(int $fileId): void
    {
        CollectionFileAsset::query()->where('file_id', $fileId)->delete();
    }

    public function expiredContexts(string $before, int $limit): array
    {
        return CollectionUploadContext::query()->where('created_at', '<', $before)->orderBy('created_at')->limit($limit)->pluck('token')->all();
    }
}
