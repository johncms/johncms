<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class SaveCollectionFieldUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
        private \Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface $transaction,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
    ) {
    }

    /**
     * Creates or updates a field. Returns true when an existing field was updated.
     *
     * @throws CollectionFieldCodeAlreadyExistsException when the code belongs to another field of the collection
     */
    public function execute(?int $id, CollectionFieldFormDTO $dto): bool
    {
        return $this->transaction->run(function () use ($id, $dto): bool {
            $current = $id !== null ? $this->repository->findById($id) : null;
            $before = $current !== null ? new CollectionChangeStateDTO(null, null, $current->code) : null;
            $existing = $this->repository->findByCode($dto->collectionId, $dto->code);
            if ($existing !== null && $existing->id !== $id) {
                throw new CollectionFieldCodeAlreadyExistsException();
            }

            $attributes = [
            'collection_id' => $dto->collectionId,
            'code'          => $dto->code,
            'name'          => $dto->name,
            'type'          => $dto->type->value,
            'required'      => $dto->required,
            'multiple'      => $dto->multiple,
            'in_list'       => $dto->inList,
            'sort'          => $dto->sort,
            ];

            if ($id !== null && $this->repository->findById($id) !== null) {
                $this->repository->update($id, $attributes);

                $this->changes->dispatch(new CollectionChangeDTO('field', 'saved', $dto->collectionId, $id, $before, new CollectionChangeStateDTO(null, null, $dto->code)));
                return true;
            }

            $field = $this->repository->create($attributes);
            $this->changes->dispatch(new CollectionChangeDTO('field', 'saved', $dto->collectionId, $field->id, null, new CollectionChangeStateDTO(null, null, $dto->code)));

            return false;
        });
    }
}
