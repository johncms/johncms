<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Johncms\Modules\Collections\Domain\Models\CollectionUploadContext;
use Johncms\Modules\Collections\Domain\Models\CollectionFileAsset;
use Johncms\Modules\Collections\Domain\Models\CollectionFileLink;
use Johncms\Modules\Collections\Domain\Models\CollectionFileValueLink;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue;
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

    public function availableForCollection(int $collectionId, bool $current = true): array
    {
        $attachments = CollectionFileLink::query()->join('collection_items', 'collection_items.id', '=', 'collection_file_links.item_id')
            ->where('collection_items.collection_id', $collectionId)->orderBy('collection_file_links.file_id');
        $values = CollectionFileValueLink::query()->join('collection_fields', 'collection_fields.id', '=', 'collection_file_value_links.field_id')
            ->where('collection_fields.collection_id', $collectionId)->orderBy('collection_file_value_links.file_id');
        if ($current) {
            $attachments->lockForUpdate();
            $values->lockForUpdate();
        }
        return array_values(array_unique([...$attachments->pluck('collection_file_links.file_id')->all(), ...$values->pluck('collection_file_value_links.file_id')->all()]));
    }

    public function replaceValueLinks(int $fieldId, ?int $itemId, array $fileIds): array
    {
        $query = CollectionFileValueLink::query()->where('field_id', $fieldId)->where('item_id', $itemId);
        $old = (clone $query)->lockForUpdate()->orderBy('file_id')->pluck('file_id')->all();
        foreach ($fileIds as $fileId) {
            CollectionFileValueLink::query()->firstOrCreate(['field_id' => $fieldId, 'item_id' => $itemId, 'file_id' => $fileId]);
        }
        $query->whereNotIn('file_id', $fileIds)->delete();
        return array_values(array_diff($old, $fileIds));
    }

    public function fieldItems(int $fieldId): array
    {
        return CollectionFileValueLink::query()->where('field_id', $fieldId)->whereNotNull('item_id')->orderBy('item_id')->lockForUpdate()->distinct()->pluck('item_id')->all();
    }

    public function unlinkField(int $fieldId): array
    {
        $query = CollectionFileValueLink::query()->where('field_id', $fieldId);
        $ids = (clone $query)->lockForUpdate()->orderBy('file_id')->pluck('file_id')->all();
        $query->delete();
        return array_values(array_unique($ids));
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

    public function setManaged(int $fileId, bool $managed): void
    {
        CollectionFileAsset::query()->where('file_id', $fileId)->update(['managed' => $managed]);
    }

    public function itemFiles(int $itemId, bool $current = false): array
    {
        $attachments = CollectionFileLink::query()->where('item_id', $itemId)->orderBy('file_id');
        $values = CollectionFileValueLink::query()->where('item_id', $itemId)->orderBy('file_id');
        if ($current) {
            $attachments->lockForUpdate();
            $values->lockForUpdate();
        }
        return array_values(array_unique([...$attachments->pluck('file_id')->all(), ...$values->pluck('file_id')->all()]));
    }

    public function linkContext(string $token, int $fileId, ?int $fieldId = null): void
    {
        CollectionFileLink::query()->firstOrCreate(['context_token' => $token, 'file_id' => $fileId], ['item_id' => null, 'field_id' => $fieldId]);
    }

    public function contextFiles(string $token, bool $current = false): array
    {
        $query = CollectionFileLink::query()->where('context_token', $token)->orderBy('file_id');
        if ($current) {
            $query->lockForUpdate();
        }
        return $query->pluck('file_id')->all();
    }

    public function itemsMentioning(string $address): array
    {
        $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $address) . '%';
        $items = ContentCollectionItem::query()->whereRaw("preview_text LIKE ? ESCAPE '!'", [$like])
            ->orWhereRaw("detail_text LIKE ? ESCAPE '!'", [$like])->orderBy('id')->pluck('id')->all();
        $values = ContentCollectionItemValue::query()->whereRaw("value_text LIKE ? ESCAPE '!'", [$like])->orderBy('item_id')->pluck('item_id')->all();
        return array_values(array_unique([...$items, ...$values]));
    }

    public function touchContext(string $token, string $createdAt): void
    {
        CollectionUploadContext::query()->where('token', $token)->update(['created_at' => $createdAt]);
    }

    public function claim(string $token, int $itemId): void
    {
        $attachments = CollectionFileLink::query()->where('context_token', $token)->whereNull('field_id')->orderBy('file_id')->lockForUpdate()->pluck('file_id')->all();
        foreach ($attachments as $fileId) {
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
        $ids = $this->itemFiles($itemId, true);
        foreach ($ids as $fileId) {
            $this->lockAsset($fileId);
        }
        CollectionFileLink::query()->where('item_id', $itemId)->delete();
        CollectionFileValueLink::query()->where('item_id', $itemId)->delete();
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

    public function hasLinks(int $fileId, bool $current = false): bool
    {
        $query = CollectionFileLink::query()->where('file_id', $fileId);
        if ($current ? $query->lockForUpdate()->first(['id']) !== null : $query->exists()) {
            return true;
        }
        $values = CollectionFileValueLink::query()->where('file_id', $fileId);
        return $current ? $values->lockForUpdate()->first(['id']) !== null : $values->exists();
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
