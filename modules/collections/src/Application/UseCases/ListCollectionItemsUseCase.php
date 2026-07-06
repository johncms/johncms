<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionItemListItemDTO;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class ListCollectionItemsUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $repository,
    ) {
    }

    public function count(int $collectionId, ?int $sectionId): int
    {
        return $this->repository->countItems($this->query($collectionId, $sectionId));
    }

    /**
     * @return list<CollectionItemListItemDTO>
     */
    public function getPage(int $collectionId, ?int $sectionId, int $limit, int $offset): array
    {
        $query = $this->query($collectionId, $sectionId, $limit, $offset);

        return $this->repository->findItems($query)
            ->map(static fn (ContentCollectionItem $item): CollectionItemListItemDTO => new CollectionItemListItemDTO(
                id: $item->id,
                code: $item->code,
                name: $item->name,
                active: $item->active,
                sort: $item->sort,
                sectionId: $item->section_id,
            ))
            ->all();
    }

    private function query(int $collectionId, ?int $sectionId, ?int $limit = null, int $offset = 0): ContentCollectionItemQuery
    {
        // Admin listing shows every item regardless of the publish window.
        return new ContentCollectionItemQuery(
            collectionId: $collectionId,
            sectionId: $sectionId,
            onlyActive: false,
            limit: $limit,
            offset: $offset,
        );
    }
}
