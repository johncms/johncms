<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPreviewDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRelationImpactDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionSectionTreeNodeDTO;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;

/** User operations. Trusted module hooks use the system APIs instead. */
interface CollectionsUserApiInterface
{
    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO;

    /** @return list<string> */
    public function suggestTags(int $collectionId, string $fieldCode, string $prefix = '', int $limit = 10): array;

    public function getItem(int $collectionId, int $id): ?CollectionItemDTO;

    public function addItem(CollectionItemFormDTO $item): int;

    public function updateItem(int $id, CollectionItemFormDTO $item): void;

    public function patchItem(int $collectionId, int $id, CollectionItemPatchDTO $patch): void;

    public function previewItemDeletion(int $collectionId, int $id): CollectionRelationImpactDTO;

    public function deleteItem(int $collectionId, int $id, ?string $expectedRelations = null): void;

    public function findSection(int $collectionId, int $id): ?CollectionSectionDTO;

    /** @return list<CollectionSectionTreeNodeDTO> */
    public function getTree(int $collectionId): array;

    public function addSection(CollectionSectionFormDTO $section): int;

    public function updateSection(int $id, CollectionSectionFormDTO $section): void;

    public function previewSectionDeletion(int $collectionId, int $id): CollectionDeletionPreviewDTO;

    public function deleteSection(int $collectionId, int $id, SectionDeletionMode $mode, ?int $targetId = null, ?string $expectedRevision = null): void;
}
