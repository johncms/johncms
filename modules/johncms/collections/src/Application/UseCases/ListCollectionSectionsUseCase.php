<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\SectionTreeItemDTO;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionSection;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

/**
 * The sections of a collection as one tree: every section under its parent, in the order the
 * collection keeps them, with the items of its whole subtree.
 */
final readonly class ListCollectionSectionsUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $repository,
    ) {
    }

    /**
     * @return list<SectionTreeItemDTO>
     */
    public function getTree(int $collectionId): array
    {
        /** @var array<int, ContentCollectionSection> $byId */
        $byId = $this->repository->getAllWithItemCounts($collectionId)->keyBy('id')->all();
        $tree = SectionTree::of($byId);
        $items = [];

        foreach ($tree->ordered() as $id) {
            $inSubtree = 0;
            foreach ($tree->subtreeOf($id) as $sectionId) {
                $inSubtree += $this->itemCount($byId[$sectionId] ?? null);
            }

            $items[] = new SectionTreeItemDTO(
                section: $byId[$id],
                depth: $tree->depthOf($id),
                items: $this->itemCount($byId[$id]),
                itemsInSubtree: $inSubtree,
            );
        }

        return $items;
    }

    private function itemCount(?ContentCollectionSection $section): int
    {
        return (int) $section?->getAttribute('items_count');
    }
}
