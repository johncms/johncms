<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionSectionListItemDTO;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class ListCollectionSectionsUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $repository,
    ) {
    }

    public function count(int $collectionId, ?int $parent): int
    {
        return $this->repository->countByCollection($collectionId, $parent);
    }

    /**
     * @return list<CollectionSectionListItemDTO>
     */
    public function getPage(int $collectionId, ?int $parent, int $limit, int $offset): array
    {
        return $this->repository->getByCollection($collectionId, $parent, $limit, $offset)
            ->map(static fn (ContentCollectionSection $section): CollectionSectionListItemDTO => new CollectionSectionListItemDTO(
                id: $section->id,
                code: $section->code,
                name: $section->name,
                active: $section->active,
                sort: $section->sort,
                childCount: (int) ($section->child_sections_count ?? 0),
            ))
            ->all();
    }
}
