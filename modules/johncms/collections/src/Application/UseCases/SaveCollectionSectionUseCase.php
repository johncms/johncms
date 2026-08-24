<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionSectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class SaveCollectionSectionUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $repository,
    ) {
    }

    /**
     * Creates or updates a section. Returns true when an existing section was updated.
     *
     * Uniqueness of the code is enforced within the same parent, because the DB
     * unique index treats NULL parents as distinct (see the schema notes).
     *
     * @throws CollectionSectionCodeAlreadyExistsException when the code belongs to another sibling
     */
    public function execute(?int $id, CollectionSectionFormDTO $dto): bool
    {
        $existing = $this->repository->findByCode($dto->collectionId, $dto->parent, $dto->code);
        if ($existing !== null && $existing->id !== $id) {
            throw new CollectionSectionCodeAlreadyExistsException();
        }

        $attributes = [
            'collection_id' => $dto->collectionId,
            'parent'        => $dto->parent,
            'code'          => $dto->code,
            'name'          => $dto->name,
            'description'   => $dto->description,
            'active'        => $dto->active,
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
