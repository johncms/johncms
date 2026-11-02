<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final class ContentCollectionSectionRepository implements ContentCollectionSectionRepositoryInterface
{
    public function getAllByCollection(int $collectionId): Collection
    {
        return ContentCollectionSection::query()
            ->where('collection_id', $collectionId)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function getAllWithItemCounts(int $collectionId): Collection
    {
        return ContentCollectionSection::query()
            ->where('collection_id', $collectionId)
            ->withCount('items')
            ->get();
    }

    public function getActiveChildren(int $collectionId, ?int $parent): Collection
    {
        return $this->parentScopedQuery($collectionId, $parent)
            ->where('active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();
    }

    public function findByCode(int $collectionId, ?int $parent, string $code): ?ContentCollectionSection
    {
        return $this->parentScopedQuery($collectionId, $parent)
            ->where('code', $code)
            ->first();
    }

    public function findByCodeForUpdate(int $collectionId, ?int $parent, string $code): ?ContentCollectionSection
    {
        $query = ContentCollectionSection::query()->where('collection_id', $collectionId)->where('parent', $parent)->where('code', $code);
        $query->lockForUpdate();
        return $query->first();
    }

    public function findById(int $id): ?ContentCollectionSection
    {
        return ContentCollectionSection::query()->find($id);
    }

    public function findForUpdate(int $id): ?ContentCollectionSection
    {
        $query = ContentCollectionSection::query();
        $query->lockForUpdate();
        return $query->find($id);
    }

    public function getAllForUpdate(int $collectionId): Collection
    {
        $query = ContentCollectionSection::query()->where('collection_id', $collectionId)->orderBy('id');
        $query->lockForUpdate();
        return $query->get();
    }

    public function create(array $attributes): ContentCollectionSection
    {
        return ContentCollectionSection::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        $section = $this->findForUpdate($id);
        if ($section === null) {
            return;
        }

        $section->fill($attributes)->save();
    }

    public function delete(int $id): void
    {
        // Child sections cascade via the self-referencing FK; items keep their
        // section_id set to null (onDelete set null).
        ContentCollectionSection::query()->where('id', $id)->delete();
    }

    public function getPathTo(int $sectionId): Collection
    {
        $path = new Collection();
        $current = $this->findById($sectionId);
        if ($current === null) {
            return $path;
        }
        $sections = $this->getAllByCollection($current->collection_id)->keyBy('id');
        $tree = SectionTree::of($sections);
        foreach ([...array_reverse($tree->ancestorsOf($sectionId)), $sectionId] as $id) {
            if (isset($sections[$id])) {
                $path->push($sections[$id]);
            }
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
