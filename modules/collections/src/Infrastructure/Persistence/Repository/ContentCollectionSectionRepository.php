<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final class ContentCollectionSectionRepository implements ContentCollectionSectionRepositoryInterface
{
    public function getByCollection(int $collectionId, ?int $parent, int $limit, int $offset): Collection
    {
        return $this->parentScopedQuery($collectionId, $parent)
            ->orderBy('sort')
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    public function countByCollection(int $collectionId, ?int $parent): int
    {
        return $this->parentScopedQuery($collectionId, $parent)->count();
    }

    public function findByCode(int $collectionId, ?int $parent, string $code): ?ContentCollectionSection
    {
        return $this->parentScopedQuery($collectionId, $parent)
            ->where('code', $code)
            ->first();
    }

    public function getPathTo(int $sectionId): Collection
    {
        // Ancestor walk from the section up to the root; bounded by tree depth.
        $path = new Collection();
        $current = ContentCollectionSection::query()->find($sectionId);
        while ($current !== null) {
            $path->prepend($current);
            $current = $current->parent !== null
                ? ContentCollectionSection::query()->find($current->parent)
                : null;
        }

        return $path;
    }

    /**
     * @return Builder<ContentCollectionSection>
     */
    private function parentScopedQuery(int $collectionId, ?int $parent): Builder
    {
        $builder = ContentCollectionSection::query()->where('collection_id', $collectionId);
        $parent === null ? $builder->whereNull('parent') : $builder->where('parent', $parent);

        return $builder;
    }
}
