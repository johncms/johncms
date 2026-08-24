<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Illuminate\Database\Eloquent\Collection;
use Johncms\Modules\Collections\Application\DTO\CollectionItemFormDTO;
use Johncms\Modules\Collections\Application\UseCases\DeleteCollectionItemUseCase;
use Johncms\Modules\Collections\Application\UseCases\SaveCollectionItemUseCase;
use Johncms\Modules\Collections\Domain\Models\ContentCollection;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionItem;
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
    ) {
    }

    public function findCollection(string $code): ?ContentCollection
    {
        return $this->collectionRepository->findByCode($code);
    }

    public function getItems(ContentCollectionItemQuery $query): Collection
    {
        return $this->itemRepository->findItems($query);
    }

    public function countItems(ContentCollectionItemQuery $query): int
    {
        return $this->itemRepository->countItems($query);
    }

    public function getItem(int $id): ?ContentCollectionItem
    {
        return $this->itemRepository->findWithValues($id);
    }

    public function getItemByCode(int $collectionId, ?int $sectionId, string $code): ?ContentCollectionItem
    {
        return $this->itemRepository->findByCode($collectionId, $sectionId, $code);
    }

    public function addItem(CollectionItemFormDTO $item): int
    {
        return $this->saveItem->execute(null, $item);
    }

    public function updateItem(int $id, CollectionItemFormDTO $item): void
    {
        $this->saveItem->execute($id, $item);
    }

    public function deleteItem(int $id): void
    {
        $this->deleteItem->execute($id);
    }
}
