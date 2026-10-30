<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;

interface ContentCollectionSectionRepositoryInterface
{
    /**
     * All sections of a collection (flat), ordered by sort. Used for section pickers.
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getAllByCollection(int $collectionId): Collection;

    /**
     * All sections of a collection, each with the number of the items standing right in it
     * (items_count).
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getAllWithItemCounts(int $collectionId): Collection;

    /**
     * Active child sections under the given parent (NULL = root), for public navigation.
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getActiveChildren(int $collectionId, ?int $parent): Collection;

    public function findByCode(int $collectionId, ?int $parent, string $code): ?ContentCollectionSection;

    public function findById(int $id): ?ContentCollectionSection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function create(array $attributes): ContentCollectionSection;

    /**
     * @param array<string, mixed> $attributes
     */
    public function update(int $id, array $attributes): void;

    public function delete(int $id): void;

    /**
     * The section path from the root down to the given section (breadcrumbs/URL).
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getPathTo(int $sectionId): Collection;
}
