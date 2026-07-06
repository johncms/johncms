<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;

interface ContentCollectionSectionRepositoryInterface
{
    /**
     * Sections of a collection under the given parent (NULL = root level).
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getByCollection(int $collectionId, ?int $parent, int $limit, int $offset): Collection;

    /**
     * All sections of a collection (flat), ordered by sort. Used for section pickers.
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getAllByCollection(int $collectionId): Collection;

    public function countByCollection(int $collectionId, ?int $parent): int;

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
