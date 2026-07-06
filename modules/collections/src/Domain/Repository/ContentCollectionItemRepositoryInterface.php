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

    public function findByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem;
}
