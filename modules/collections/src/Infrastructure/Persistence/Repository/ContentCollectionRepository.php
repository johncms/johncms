<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final class ContentCollectionRepository implements ContentCollectionRepositoryInterface
{
    public function findByCode(string $code): ?ContentCollection
    {
        return ContentCollection::query()->where('code', $code)->first();
    }

    public function findById(int $id): ?ContentCollection
    {
        return ContentCollection::query()->find($id);
    }

    public function getAll(int $limit, int $offset): Collection
    {
        return ContentCollection::query()
            ->orderBy('sort')
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countAll(): int
    {
        return ContentCollection::query()->count();
    }

    public function create(array $attributes): ContentCollection
    {
        return ContentCollection::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        // Load and save via the model so the `settings` array cast is applied
        // (a query-builder update() would bypass casting the JSON column).
        $collection = ContentCollection::query()->find($id);
        if ($collection === null) {
            return;
        }

        $collection->fill($attributes)->save();
    }

    public function delete(int $id): void
    {
        // Fields, sections, items and values are removed by the FK cascade.
        ContentCollection::query()->where('id', $id)->delete();
    }
}
