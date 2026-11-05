<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;

final class ContentCollectionItemValueRepository implements ContentCollectionItemValueRepositoryInterface
{
    public function hasIncomingRelationsForUpdate(array $targetIds, array $deletedItemIds): bool
    {
        return ContentCollectionItemValue::query()->join('collection_fields', 'collection_fields.id', '=', 'collection_item_values.field_id')
            ->whereIn('collection_item_values.value_int', $targetIds)->whereNotIn('collection_item_values.item_id', $deletedItemIds)
            ->where('collection_fields.type', \Johncms\Modules\Collections\Domain\Enums\FieldType::Relation->value)
            ->orderBy('collection_item_values.id')->lockForUpdate()->first(['collection_item_values.id']) !== null;
    }

    public function getIncomingRelations(array $targetIds, array $deletedItemIds, bool $forUpdate = false): \Illuminate\Database\Eloquent\Collection
    {
        $query = ContentCollectionItemValue::query()->with($forUpdate ? [
            'field' => static fn ($fields) => $fields->lockForUpdate(),
            'item' => static fn ($items) => $items->lockForUpdate(),
        ] : ['field', 'item']);
        $query->join('collection_fields', 'collection_fields.id', '=', 'collection_item_values.field_id')
            ->whereIn('collection_item_values.value_int', $targetIds)->whereNotIn('collection_item_values.item_id', $deletedItemIds)
            ->where('collection_fields.type', \Johncms\Modules\Collections\Domain\Enums\FieldType::Relation->value)->orderBy('collection_item_values.id');
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        return $query->get(['collection_item_values.*']);
    }

    public function deleteRelationValues(array $ids): void
    {
        ContentCollectionItemValue::query()->whereIn('id', $ids)->delete();
    }

    public function deleteByItem(int $itemId): void
    {
        ContentCollectionItemValue::query()->where('item_id', $itemId)->delete();
    }

    public function deleteFields(int $itemId, array $fieldIds): void
    {
        ContentCollectionItemValue::query()->where('item_id', $itemId)->whereIn('field_id', $fieldIds)->delete();
    }

    public function insertMany(array $rows): void
    {
        if ($rows === []) {
            return;
        }

        ContentCollectionItemValue::query()->insert($rows);
    }
}
