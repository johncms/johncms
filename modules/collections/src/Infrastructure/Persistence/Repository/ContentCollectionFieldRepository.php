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
}
