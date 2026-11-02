<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemValueRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;

final readonly class DeleteCollectionItemUseCase
{
    public function __construct(
        private ContentCollectionItemRepositoryInterface $repository,
        private ContentCollectionItemValueRepositoryInterface $values,
        private ContentCollectionRepositoryInterface $collections,
        private CollectionTransactionInterface $transaction,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
    ) {
    }

    public function execute(int $id, ?int $collectionId = null): void
    {
        $collectionId ??= $this->repository->findById($id)?->collection_id;
        $this->transaction->run(function () use ($id, $collectionId): void {
            $collection = $collectionId !== null ? $this->collections->findForUpdate($collectionId) : null;
            $item = $this->repository->findForUpdate($id);
            if ($collection === null || $item === null || $item->collection_id !== $collectionId) {
                throw new CollectionItemFormException(['id' => [d__('collections', 'The item does not belong to this collection or no longer exists.')]]);
            }
            $this->changes->ensureOwnerLoaded($collection->owner_module);
            $this->changes->dispatch(new CollectionChangeDTO(
                'item',
                'deleted',
                $collectionId,
                $id,
                new CollectionChangeStateDTO($item->section_id, $item->active, $item->code),
                null
            ));
            $this->values->deleteByItem($id);
            $this->repository->delete($id);
        });
    }
}
