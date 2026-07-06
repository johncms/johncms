<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionFieldFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionFieldCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class SaveCollectionFieldUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
    ) {
    }

    /**
     * Creates or updates a field. Returns true when an existing field was updated.
     *
     * @throws CollectionFieldCodeAlreadyExistsException when the code belongs to another field of the collection
     */
    public function execute(?int $id, CollectionFieldFormDTO $dto): bool
    {
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
            'sort'          => $dto->sort,
        ];

        if ($id !== null && $this->repository->findById($id) !== null) {
            $this->repository->update($id, $attributes);

            return true;
        }

        $this->repository->create($attributes);

        return false;
    }
}
