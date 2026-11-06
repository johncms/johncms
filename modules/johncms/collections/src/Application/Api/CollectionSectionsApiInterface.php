<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPreviewDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionTreeNodeDTO;

/** Administrative section access; public visibility is a separate publication policy. */
interface CollectionSectionsApiInterface
{
    public function findSection(int $collectionId, int $id): ?CollectionSectionDTO;

    /** @return list<CollectionSectionTreeNodeDTO> Flat preorder, with direct/subtree counts of all items. */
    public function getTree(int $collectionId): array;

    /** @return list<CollectionSectionDTO> Root to the section itself, or [] when missing/foreign. */
    public function getPath(int $collectionId, int $id): array;

    /**
     * The same for a whole list, reading the tree once.
     * @param list<int> $ids
     * @return array<int, list<CollectionSectionDTO>> Keyed by section id; missing or foreign ones are left out.
     */
    public function getPaths(int $collectionId, array $ids): array;

    /** @param list<string> $codes Complete root-to-section code chain; [] resolves to null. */
    public function resolvePath(int $collectionId, array $codes): ?CollectionSectionDTO;

    public function addSection(CollectionSectionFormDTO $section): int;

    public function updateSection(int $id, CollectionSectionFormDTO $section): void;

    public function previewDeletion(int $collectionId, int $id): CollectionDeletionPreviewDTO;

    public function deleteSection(int $collectionId, int $id, SectionDeletionMode $mode, ?int $targetId = null, ?string $expectedRevision = null): void;
}
