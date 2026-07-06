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

    public function findByCode(int $collectionId, string $code): ?ContentCollectionField
    {
        return ContentCollectionField::query()
            ->where('collection_id', $collectionId)
            ->where('code', $code)
            ->first();
    }

    public function findById(int $id): ?ContentCollectionField
    {
        return ContentCollectionField::query()->find($id);
    }

    public function create(array $attributes): ContentCollectionField
    {
        return ContentCollectionField::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        $field = ContentCollectionField::query()->find($id);
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
