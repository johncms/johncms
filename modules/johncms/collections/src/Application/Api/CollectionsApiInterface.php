<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPreviewDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRelationImpactDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;

/**
 * Stable programmatic API to collections for third-party modules — the Bitrix
 * `CIBlockElement`/`CIBlock` analog. Register it in DI as public and inject the
 * interface; it wraps the repositories, {@see ContentCollectionItemQuery} and the
 * item use cases (uniqueness check + EAV value rebuild) without any HTTP knowledge.
 */
interface CollectionsApiInterface
{
    /**
     * Resolve a collection by its machine code (`blog`, `catalog`, ...).
     */
    public function findCollection(string $code): ?CollectionRecordDTO;

    public function findDeclaredCollection(string $owner, string $key): ?CollectionRecordDTO;

    /**
     * Select items matching the query specification (the `CIBlockElement::GetList` analog).
     *
     * @return list<CollectionItemDTO>
     */
    public function getItems(ContentCollectionItemQuery $query): array;

    /**
     * Count items matching the query specification (without limit/offset).
     */
    public function countItems(ContentCollectionItemQuery $query): int;

    /**
     * Load an item with typed custom values, without exposing ORM models.
     * @phpstan-impure
     */
    public function getItem(int $id): ?CollectionItemDTO;

    /**
     * Load a single item by code within a collection/section.
     */
    public function getItemByCode(int $collectionId, ?int $sectionId, string $code): ?CollectionItemDTO;

    /**
     * Create an item and its custom field values. Returns the new item id
     * (the `CIBlockElement::Add` analog). An empty code is generated from the name
     * as a slug, made unique with a numeric suffix on collision.
     *
     * @throws CollectionItemCodeAlreadyExistsException when an explicit code is taken in the same collection/section
     */
    public function addItem(CollectionItemFormDTO $item, ?int $actorId = null): int;

    /**
     * Update an existing item and rebuild its custom field values
     * (the `CIBlockElement::Update` analog).
     *
     * @throws CollectionItemCodeAlreadyExistsException when the code belongs to another item in the same collection/section
     */
    public function updateItem(int $id, CollectionItemFormDTO $item, ?int $actorId = null): void;

    /**
     * Delete an item (its EAV values cascade via FK).
     */
    public function previewItemDeletion(int $id): CollectionRelationImpactDTO;

    public function deleteItem(int $id, ?string $expectedRelations = null): void;

    public function previewCollectionDeletion(int $collectionId): CollectionDeletionPreviewDTO;

    public function deleteCollection(int $collectionId, ?string $expectedRevision = null): void;

    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO;

    public function patchItem(int $id, CollectionItemPatchDTO $patch, ?int $actorId = null): void;

    /** Call only for public views; hidden and out-of-window items are rejected. */
    public function incrementViews(int $id): void;
}
