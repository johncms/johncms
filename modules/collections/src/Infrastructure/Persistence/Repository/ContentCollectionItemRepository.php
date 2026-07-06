<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;

final class ContentCollectionItemRepository implements ContentCollectionItemRepositoryInterface
{
    public function __construct(
        private readonly ContentCollectionFieldRepositoryInterface $fieldRepository,
        private readonly ContentCollectionItemQueryCompiler $compiler,
    ) {
    }

    public function findItems(ContentCollectionItemQuery $query): Collection
    {
        $fields = $this->resolveFields($query);
        $builder = $this->baseQuery($query, $fields);
        $this->compiler->applyOrder($builder, $query, $fields);

        if ($query->limit !== null) {
            $builder->offset($query->offset)->limit($query->limit);
        }

        return $builder->get();
    }

    public function countItems(ContentCollectionItemQuery $query): int
    {
        return $this->baseQuery($query, $this->resolveFields($query))->count();
    }

    public function findById(int $id): ?ContentCollectionItem
    {
        return ContentCollectionItem::query()->find($id);
    }

    public function findWithValues(int $id): ?ContentCollectionItem
    {
        return ContentCollectionItem::query()->with('values.field')->find($id);
    }

    public function create(array $attributes): ContentCollectionItem
    {
        return ContentCollectionItem::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        $item = ContentCollectionItem::query()->find($id);
        if ($item === null) {
            return;
        }

        $item->fill($attributes)->save();
    }

    public function delete(int $id): void
    {
        // Values are removed by the FK cascade on collection_item_values.
        ContentCollectionItem::query()->where('id', $id)->delete();
    }

    public function findByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem
    {
        $builder = ContentCollectionItem::query()
            ->where('collection_id', $collectionId)
            ->where('code', $code);
        $sectionId === null ? $builder->whereNull('section_id') : $builder->where('section_id', $sectionId);

        return $builder->first();
    }

    public function getVisibleForSitemap(int $collectionId): iterable
    {
        $now = Carbon::now();

        return ContentCollectionItem::query()
            ->select(['id', 'code', 'section_id', 'updated_at'])
            ->where('collection_id', $collectionId)
            ->where('active', true)
            ->where(static fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $now))
            ->where(static fn (Builder $q) => $q->whereNull('active_to')->orWhere('active_to', '>=', $now))
            ->orderBy('id')
            ->cursor();
    }

    public function findVisibleByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem
    {
        $now = Carbon::now();
        $builder = ContentCollectionItem::query()
            ->with('values.field')
            ->where('collection_id', $collectionId)
            ->where('code', $code)
            ->where('active', true)
            ->where(static fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $now))
            ->where(static fn (Builder $q) => $q->whereNull('active_to')->orWhere('active_to', '>=', $now));
        $sectionId === null ? $builder->whereNull('section_id') : $builder->where('section_id', $sectionId);

        return $builder->first();
    }

    /**
     * Builds the base query: collection, section and the active/publish window,
     * plus the query's filter clauses (base columns and custom EAV fields).
     *
     * @param array<string, ContentCollectionField> $fields
     * @return Builder<ContentCollectionItem>
     */
    private function baseQuery(ContentCollectionItemQuery $query, array $fields): Builder
    {
        $builder = ContentCollectionItem::query()->where('collection_id', $query->collectionId);

        // Subsection expansion (includeSubsections) is deferred; only the exact section is matched.
        if ($query->sectionId !== null) {
            $builder->where('section_id', $query->sectionId);
        }

        if ($query->onlyActive) {
            $now = Carbon::now();
            $builder->where('active', true)
                ->where(static fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $now))
                ->where(static fn (Builder $q) => $q->whereNull('active_to')->orWhere('active_to', '>=', $now));
        }

        $this->compiler->applyFilters($builder, $query, $fields);

        return $builder;
    }

    /**
     * Resolves the collection fields referenced by the query's custom-field
     * filters/ordering, keyed by code. Base-only queries skip the lookup.
     *
     * @return array<string, ContentCollectionField>
     */
    private function resolveFields(ContentCollectionItemQuery $query): array
    {
        if ($this->compiler->referencedFieldCodes($query) === []) {
            return [];
        }

        return $this->fieldRepository->getByCollection($query->collectionId)
            ->keyBy('code')
            ->all();
    }
}
