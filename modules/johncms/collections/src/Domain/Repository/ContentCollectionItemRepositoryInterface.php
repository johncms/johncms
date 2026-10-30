<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\ValueObjects\ItemListFilter;

interface ContentCollectionItemRepositoryInterface
{
    /**
     * Central selection method: returns items matching the query specification.
     *
     * @return Collection<int, ContentCollectionItem>
     */
    public function findItems(ContentCollectionItemQuery $query): Collection;

    public function countItems(ContentCollectionItemQuery $query): int;

    public function findById(int $id): ?ContentCollectionItem;

    /**
     * Load an item together with its custom field values (values.field eager loaded).
     */
    public function findWithValues(int $id): ?ContentCollectionItem;

    public function findByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem;

    /**
     * Find an item by code that is currently published (active + within the
     * active_from/active_to window), with its values eager loaded. For the public site.
     */
    public function findVisibleByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem;

    /**
     * Lazily iterate published items of a collection for the sitemap.
     *
     * @return iterable<ContentCollectionItem>
     */
    public function getVisibleForSitemap(int $collectionId): iterable;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ContentCollectionItem;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): void;

    public function delete(int $id): void;

    /**
     * The items of the list of the panel: whatever their window, narrowed by the filter.
     *
     * @param list<int>|null $sectionIds The sections they may stand in, worked out by the caller
     *                                   from the tree: null for any, ItemListFilter::WITHOUT_SECTION
     *                                   among them for the items outside every section.
     */
    public function countForPanel(int $collectionId, ItemListFilter $filter, ?array $sectionIds, DateTimeInterface $now): int;

    /**
     * A page of them, in the order of the collection, each with its values and section.
     *
     * @param list<int>|null $sectionIds As for countForPanel().
     * @return Collection<int, ContentCollectionItem>
     */
    public function getForPanel(int $collectionId, ItemListFilter $filter, ?array $sectionIds, DateTimeInterface $now, int $limit, int $offset): Collection;
}
