<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionTreeNodeDTO;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionSectionUseCase;
use Johncms\Modules\Collections\Application\UseCases\ListCollectionSectionsUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionSectionUseCase;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Services\SectionTree;

final readonly class CollectionSectionsApi implements CollectionSectionsApiInterface
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $sections,
        private ListCollectionSectionsUseCase $list,
        private SaveCollectionSectionUseCase $save,
        private DeleteCollectionSectionUseCase $delete,
        private CollectionApiMapper $mapper,
    ) {
    }

    public function findSection(int $collectionId, int $id): ?CollectionSectionDTO
    {
        $section = $this->sections->findById($id);
        return $section !== null && $section->collection_id === $collectionId ? $this->mapper->section($section) : null;
    }

    public function getTree(int $collectionId): array
    {
        return array_map(fn ($node): CollectionSectionTreeNodeDTO => new CollectionSectionTreeNodeDTO(
            $this->mapper->section($node->section),
            $node->depth,
            $node->items,
            $node->itemsInSubtree
        ), $this->list->getTree($collectionId));
    }

    public function getPath(int $collectionId, int $id): array
    {
        $byId = $this->sections->getAllByCollection($collectionId)->keyBy('id');
        $tree = SectionTree::of($byId);
        if (! $tree->has($id)) {
            return [];
        }
        return array_map(fn (int $sectionId): CollectionSectionDTO => $this->mapper->section($byId[$sectionId]), [
            ...array_reverse($tree->ancestorsOf($id)), $id,
        ]);
    }

    public function resolvePath(int $collectionId, array $codes): ?CollectionSectionDTO
    {
        if (! array_is_list($codes)) {
            throw new \InvalidArgumentException('Section codes must be a list.');
        }
        $section = null;
        $parent = null;
        foreach ($codes as $code) {
            if (! is_string($code) || $code === '' || ! preg_match('/^(?!\.{1,2}$)[\w.+-]+$/D', $code)) {
                throw new \InvalidArgumentException('Invalid section path segment.');
            }
            $section = $this->sections->findByCode($collectionId, $parent, $code);
            if ($section === null) {
                return null;
            }
            $parent = $section->id;
        }
        return $section === null ? null : $this->mapper->section($section);
    }

    public function addSection(CollectionSectionFormDTO $section): int
    {
        return $this->save->execute(null, $section);
    }

    public function updateSection(int $id, CollectionSectionFormDTO $section): void
    {
        $this->save->execute($id, $section);
    }

    public function deleteSection(int $collectionId, int $id): void
    {
        $this->delete->execute($id, $collectionId);
    }
}
