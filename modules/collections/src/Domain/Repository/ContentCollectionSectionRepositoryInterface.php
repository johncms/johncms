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

    public function countByCollection(int $collectionId, ?int $parent): int;

    public function findByCode(int $collectionId, ?int $parent, string $code): ?ContentCollectionSection;

    /**
     * The section path from the root down to the given section (breadcrumbs/URL).
     *
     * @return Collection<int, ContentCollectionSection>
     */
    public function getPathTo(int $sectionId): Collection;
}
