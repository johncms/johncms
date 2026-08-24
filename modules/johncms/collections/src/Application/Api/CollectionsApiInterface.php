<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
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
    public function findCollection(string $code): ?ContentCollection;

    /**
     * Select items matching the query specification (the `CIBlockElement::GetList` analog).
     *
     * @return Collection<int, ContentCollectionItem>
     */
    public function getItems(ContentCollectionItemQuery $query): Collection;

    /**
     * Count items matching the query specification (without limit/offset).
     */
    public function countItems(ContentCollectionItemQuery $query): int;

    /**
     * Load a single item by id together with its custom field values eager loaded,
     * so {@see ContentCollectionItem::getValuesMap()} can be called without N+1.
     */
    public function getItem(int $id): ?ContentCollectionItem;

    /**
     * Load a single item by code within a collection/section.
     */
    public function getItemByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem;

    /**
     * Create an item and its custom field values. Returns the new item id
     * (the `CIBlockElement::Add` analog). An empty code is generated from the name
     * as a slug, made unique with a numeric suffix on collision.
     *
     * @throws CollectionItemCodeAlreadyExistsException when an explicit code is taken in the same collection/section
     */
    public function addItem(CollectionItemFormDTO $item): int;

    /**
     * Update an existing item and rebuild its custom field values
     * (the `CIBlockElement::Update` analog).
     *
     * @throws CollectionItemCodeAlreadyExistsException when the code belongs to another item in the same collection/section
     */
    public function updateItem(int $id, CollectionItemFormDTO $item): void;

    /**
     * Delete an item (its EAV values cascade via FK).
     */
    public function deleteItem(int $id): void;
}
