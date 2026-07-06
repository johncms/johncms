<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;

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
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ContentCollectionItem;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): void;

    public function delete(int $id): void;
}
