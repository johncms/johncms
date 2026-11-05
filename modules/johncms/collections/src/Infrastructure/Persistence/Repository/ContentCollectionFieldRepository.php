<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final class ContentCollectionFieldRepository implements ContentCollectionFieldRepositoryInterface
{
    public function getByCollection(int $collectionId): Collection
    {
        return ContentCollectionField::query()
            ->where('collection_id', $collectionId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function getForUpdate(int $collectionId): Collection
    {
        $query = ContentCollectionField::query()->where('collection_id', $collectionId)->orderBy('id');
        $query->lockForUpdate();
        return $query->get();
    }

    public function findByCode(int $collectionId, string $code): ?ContentCollectionField
    {
        return ContentCollectionField::query()
            ->where('collection_id', $collectionId)
            ->where('code', $code)
            ->first();
    }

    public function findByRole(int $collectionId, string $role): ?ContentCollectionField
    {
        return ContentCollectionField::query()->where('collection_id', $collectionId)->where('role', $role)->first();
    }

    public function findForUpdate(int $id): ?ContentCollectionField
    {
        $query = ContentCollectionField::query();
        $query->lockForUpdate();
        return $query->find($id);
    }

    public function findById(int $id): ?ContentCollectionField
    {
        return ContentCollectionField::query()->find($id);
    }

    public function hasExternalRelationsToCollectionForUpdate(int $collectionId): bool
    {
        return ContentCollectionField::query()->where('collection_id', '!=', $collectionId)
            ->where('type', \Johncms\Modules\Collections\Domain\Enums\FieldType::Relation->value)
            ->where('settings->target_collection_id', $collectionId)->orderBy('id')->lockForUpdate()->first(['id']) !== null;
    }

    public function getRelationsToCollection(int $collectionId, bool $forUpdate = false): Collection
    {
        $query = ContentCollectionField::query()->where('type', \Johncms\Modules\Collections\Domain\Enums\FieldType::Relation->value)
            ->where('settings->target_collection_id', $collectionId)->orderBy('id');
        if ($forUpdate) {
            $query->lockForUpdate();
        }
        return $query->get();
    }

    public function hasValues(int $id): bool
    {
        return \Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue::query()->where('field_id', $id)->lockForUpdate()->first(['id']) !== null;
    }

    public function hasSelectValuesOutside(int $id, array $codes): bool
    {
        $used = \Johncms\Modules\Collections\Domain\Models\ContentCollectionItemValue::query()->where('field_id', $id)->whereNotNull('value_string')
            ->lockForUpdate()->pluck('value_string')->all();
        return array_diff($used, $codes) !== [];
    }

    public function create(array $attributes): ContentCollectionField
    {
        return ContentCollectionField::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        $field = $this->findForUpdate($id);
        if ($field === null) {
            return;
        }

        $field->fill($attributes)->save();
    }

    public function delete(int $id): void
    {
        // Related values are removed by the FK cascade on collection_item_values.
        ContentCollectionField::query()->where('id', $id)->delete();
    }
}
