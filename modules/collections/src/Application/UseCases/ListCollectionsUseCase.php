<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionListItemDTO;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class ListCollectionsUseCase
{
    public function __construct(
        private ContentCollectionRepositoryInterface $repository,
    ) {
    }

    public function count(): int
    {
        return $this->repository->countAll();
    }

    /**
     * @return list<CollectionListItemDTO>
     */
    public function getPage(int $limit, int $offset): array
    {
        return $this->repository->getAll($limit, $offset)
            ->map(static fn (ContentCollection $collection): CollectionListItemDTO => new CollectionListItemDTO(
                id: $collection->id,
                code: $collection->code,
                name: $collection->name,
                active: $collection->active,
                sort: $collection->sort,
            ))
            ->all();
    }
}
