<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Domain\Repository;

use Johncms\Modules\News\Domain\Models\NewsSection;

interface NewsSectionRepositoryInterface
{
    public function findById(int $id): ?NewsSection;

    /**
     * Where every section stands in the tree.
     *
     * @return array<int, int> The id of a section => the id of its parent, 0 at the root.
     */
    public function getParents(): array;

    /**
     * @param list<int> $ids
     */
    public function deleteByIds(array $ids): void;
}
