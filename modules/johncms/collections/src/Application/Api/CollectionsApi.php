<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionUseCase;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Application\DTO\CollectionDeletionPreviewDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionRecordDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPageDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionItemPatchDTO;
use Johncms\Modules\Collections\Application\Services\CollectionApiMapper;
use Johncms\Modules\Collections\Application\UseCases\PatchCollectionItemUseCase;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Query\ContentCollectionItemQuery;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class CollectionsApi implements CollectionsApiInterface
{
    public function __construct(
        private ContentCollectionRepositoryInterface $collectionRepository,
        private ContentCollectionItemRepositoryInterface $itemRepository,
        private SaveCollectionItemUseCase $saveItem,
        private DeleteCollectionItemUseCase $deleteItem,
        private PatchCollectionItemUseCase $patch,
        private CollectionApiMapper $mapper,
        private CollectionDeletionPlanner $deletions,
        private DeleteCollectionUseCase $deleteCollection,
    ) {
    }

    public function findCollection(string $code): ?CollectionRecordDTO
    {
        $collection = $this->collectionRepository->findByCode($code);
        return $collection === null ? null : $this->mapper->collection($collection);
    }

    public function findDeclaredCollection(string $owner, string $key): ?CollectionRecordDTO
    {
        $collection = $this->collectionRepository->findByDeclaration($owner, $key);
        return $collection === null ? null : $this->mapper->collection($collection);
    }

    public function getItems(ContentCollectionItemQuery $query): array
    {
        return $this->itemRepository->findItems($query)->map($this->mapper->item(...))->all();
    }

    public function countItems(ContentCollectionItemQuery $query): int
    {
        return $this->itemRepository->countItems($query);
    }

    public function getItem(int $id): ?CollectionItemDTO
    {
        $item = $this->itemRepository->findWithValues($id);
        return $item === null ? null : $this->mapper->item($item);
    }

    public function getItemByCode(int $collectionId, ?int $sectionId, string $code): ?CollectionItemDTO
    {
        $item = $this->itemRepository->findByCode($collectionId, $sectionId, $code);
        return $item === null ? null : $this->getItem($item->id);
    }

    public function addItem(CollectionItemFormDTO $item, ?int $actorId = null): int
    {
        return $this->saveItem->execute(null, $item, $actorId);
    }

    public function updateItem(int $id, CollectionItemFormDTO $item, ?int $actorId = null): void
    {
        $this->saveItem->execute($id, $item, $actorId);
    }

    public function getPage(ContentCollectionItemQuery $query): CollectionItemPageDTO
    {
        return new CollectionItemPageDTO($this->getItems($query), $this->countItems($query), $query->limit, $query->offset);
    }

    public function patchItem(int $id, CollectionItemPatchDTO $patch, ?int $actorId = null): void
    {
        $this->patch->execute($id, $patch, $actorId);
    }

    public function incrementViews(int $id): void
    {
        if (! $this->itemRepository->incrementViews($id)) {
            throw new CollectionItemFormException(['id' => [d__('collections', 'The item is missing or not published.')]]);
        }
    }

    public function previewCollectionDeletion(int $collectionId): CollectionDeletionPreviewDTO
    {
        return $this->deletions->plan($collectionId)->preview;
    }

    public function deleteCollection(int $collectionId, ?string $expectedRevision = null): void
    {
        $this->deleteCollection->execute($collectionId, $expectedRevision);
    }

    public function previewItemDeletion(int $id): \Johncms\Modules\Collections\Application\DTO\CollectionRelationImpactDTO
    {
        return $this->deleteItem->preview($id);
    }

    public function deleteItem(int $id, ?string $expectedRelations = null): void
    {
        $this->deleteItem->execute($id, expectedRelations: $expectedRelations);
    }
}
