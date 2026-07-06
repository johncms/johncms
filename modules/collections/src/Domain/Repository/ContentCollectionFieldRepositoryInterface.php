<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;

interface ContentCollectionFieldRepositoryInterface
{
    /**
     * @return Collection<int, ContentCollectionField>
     */
    public function getByCollection(int $collectionId): Collection;

    public function findByCode(int $collectionId, string $code): ?ContentCollectionField;
}
