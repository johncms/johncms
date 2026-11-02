<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Infrastructure\Persistence\Repository;

use DateTimeInterface;
use DateTimeImmutable;
use DateTimeZone;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\CollectionPublicationQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Enums\ItemStatus;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;
use Johncms\Modules\Collections\Infrastructure\Persistence\Query\ContentCollectionItemQueryCompiler;

final class ContentCollectionItemRepository implements ContentCollectionItemRepositoryInterface
{
    public function __construct(
        private readonly ContentCollectionFieldRepositoryInterface $fieldRepository,
        private readonly ContentCollectionItemQueryCompiler $compiler,
        private readonly CollectionPublicationQuery $publication = new CollectionPublicationQuery(),
    ) {
    }

    public function findItems(ContentCollectionItemQuery $query): Collection
    {
        $fields = $this->resolveFields($query);
        $builder = $this->baseQuery($query, $fields);
        $this->compiler->applyOrder($builder, $query, $fields);

        $builder->offset($query->offset)->limit($query->limit);
        return $builder->with([
            'values' => static function ($values) use ($query): void {
                $values->orderBy('sort')->orderBy('id');
                if ($query->fields !== null) {
                    $values->whereHas('field', static fn ($field) => $field->whereIn('code', $query->fields));
                }
            },
            'values.field',
        ])->get();
    }

    public function countItems(ContentCollectionItemQuery $query): int
    {
        return $this->baseQuery($query, $this->resolveFields($query))->count();
    }

    public function findById(int $id): ?ContentCollectionItem
    {
        return ContentCollectionItem::query()->find($id);
    }

    public function findForUpdate(int $id): ?ContentCollectionItem
    {
        $query = ContentCollectionItem::query();
        $query->lockForUpdate();
        return $query->find($id);
    }

    public function findByCodeForUpdate(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem
    {
        $query = ContentCollectionItem::query()->where('collection_id', $collectionId)
            ->where('section_id', $sectionId)->where('code', $code);
        $query->lockForUpdate();
        return $query->first();
    }

    public function findWithValues(int $id): ?ContentCollectionItem
    {
        return ContentCollectionItem::query()->with(['values' => static fn ($values) => $values->orderBy('sort')->orderBy('id'), 'values.field'])->find($id);
    }

    public function findWithValuesForUpdate(int $id): ?ContentCollectionItem
    {
        $query = ContentCollectionItem::query()->with([
            'values' => static fn ($values) => $values->lockForUpdate()->orderBy('sort')->orderBy('id'),
            'values.field',
        ]);
        $query->lockForUpdate();
        return $query->find($id);
    }

    public function incrementViews(int $id): bool
    {
        $collectionId = ContentCollectionItem::query()->where('id', $id)->value('collection_id');
        if ($collectionId === null) {
            return false;
        }
        $query = ContentCollectionItem::query()->where('id', $id);
        $this->publication->apply($query, (int) $collectionId, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        return $query->toBase()->update(['view_count' => new \Illuminate\Database\Query\Expression('COALESCE(view_count, 0) + 1')]) === 1;
    }

    public function create(array $attributes): ContentCollectionItem
    {
        return ContentCollectionItem::query()->create($attributes);
    }

    public function update(int $id, array $attributes): void
    {
        $item = $this->findForUpdate($id);
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

    public function getVisibleForSitemap(int $collectionId, ?DateTimeImmutable $at = null): iterable
    {
        $query = ContentCollectionItem::query();
        $query->select(['id', 'code', 'section_id', 'updated_at']);
        $this->publication->apply($query, $collectionId, $at ?? new DateTimeImmutable('now', new DateTimeZone('UTC')));
        return $query->orderBy('id')->cursor();
    }

    public function findVisibleByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem
    {
        $query = ContentCollectionItem::query()->with(['values' => static fn ($values) => $values->orderBy('sort')->orderBy('id'), 'values.field'])->where('code', $code)->where('section_id', $sectionId);
        $this->publication->apply($query, $collectionId, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        return $query->first();
    }

    public function findVisibleById(int $collectionId, int $id): ?ContentCollectionItem
    {
        $query = ContentCollectionItem::query()->with(['values' => static fn ($values) => $values->orderBy('sort')->orderBy('id'), 'values.field'])->where('id', $id);
        $this->publication->apply($query, $collectionId, new DateTimeImmutable('now', new DateTimeZone('UTC')));
        return $query->first();
    }

    public function countForPanel(int $collectionId, ItemListFilter $filter, ?array $sectionIds, DateTimeInterface $now): int
    {
        return $this->panelQuery($collectionId, $filter, $sectionIds, $now)->count();
    }

    public function getForPanel(int $collectionId, ItemListFilter $filter, ?array $sectionIds, DateTimeInterface $now, int $limit, int $offset): Collection
    {
        return $this->panelQuery($collectionId, $filter, $sectionIds, $now)
            ->with(['values.field', 'section'])
            ->orderBy('sort')
            ->orderBy('id')
            ->offset($offset)
            ->limit($limit)
            ->get();
    }

    /**
     * @param list<int>|null $sectionIds
     * @return Builder<ContentCollectionItem>
     */
    private function panelQuery(int $collectionId, ItemListFilter $filter, ?array $sectionIds, DateTimeInterface $now): Builder
    {
        $builder = ContentCollectionItem::query()->where('collection_id', $collectionId);

        if ($filter->search !== '') {
            $search = $filter->search;
            $builder->where(static function (Builder $query) use ($search): void {
                $like = '%' . addcslashes($search, '%_\\') . '%';
                $query->where('name', 'like', $like)->orWhere('code', 'like', $like);
                if (ctype_digit($search)) {
                    $query->orWhere('id', (int) $search);
                }
            });
        }

        if ($sectionIds !== null) {
            $ids = array_values(array_filter($sectionIds, static fn (int $id): bool => $id !== ItemListFilter::WITHOUT_SECTION));
            $outside = in_array(ItemListFilter::WITHOUT_SECTION, $sectionIds, true);
            $builder->where(static function (Builder $query) use ($ids, $outside): void {
                $query->whereIn('section_id', $ids);
                if ($outside) {
                    $query->orWhereNull('section_id');
                }
            });
        }

        match ($filter->status) {
            null                  => null,
            ItemStatus::HIDDEN    => $builder->where('active', false),
            ItemStatus::SCHEDULED => $builder->where('active', true)->where('active_from', '>', $now),
            ItemStatus::EXPIRED   => $builder->where('active', true)
                ->where(static fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $now))
                ->where('active_to', '<', $now),
            ItemStatus::PUBLISHED => $builder->where('active', true)
                ->where(static fn (Builder $q) => $q->whereNull('active_from')->orWhere('active_from', '<=', $now))
                ->where(static fn (Builder $q) => $q->whereNull('active_to')->orWhere('active_to', '>=', $now)),
        };

        return $builder;
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

        if ($query->withoutSection) {
            $builder->whereNull('section_id');
        } elseif ($query->sectionId !== null) {
            if ($query->includeSubsections) {
                $tree = SectionTree::of(ContentCollectionSection::query()->where('collection_id', $query->collectionId)->get());
                $builder->whereIn('section_id', $tree->subtreeOf($query->sectionId));
            } else {
                $builder->where('section_id', $query->sectionId);
            }
        }

        if ($query->onlyActive) {
            $this->publication->apply($builder, $query->collectionId, $query->at);
        }

        $this->compiler->validate($query, $fields);
        $this->compiler->applyFilters($builder, $query, $fields);

        return $builder;
    }

    /**
     * Field definitions used to validate references and selected custom values.
     *
     * @return array<string, ContentCollectionField>
     */
    private function resolveFields(ContentCollectionItemQuery $query): array
    {
        return $this->fieldRepository->getByCollection($query->collectionId)
            ->keyBy('code')
            ->all();
    }
}
