<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class SaveCollectionUseCase
{
    public function __construct(
        private ContentCollectionRepositoryInterface $repository,
        private CollectionCodeCacheInterface $codeCache,
    ) {
    }

    /**
     * Creates or updates a collection. Returns true when an existing collection
     * was updated, false when a new one was created.
     *
     * @throws CollectionCodeAlreadyExistsException when the code belongs to another collection
     */
    public function execute(?int $id, CollectionFormDTO $dto): bool
    {
        $existing = $this->repository->findByCode($dto->code);
        if ($existing !== null && $existing->id !== $id) {
            throw new CollectionCodeAlreadyExistsException();
        }

        $attributes = [
            'code'        => $dto->code,
            'name'        => $dto->name,
            'description' => $dto->description,
            'active'      => $dto->active,
            'sort'        => $dto->sort,
            'settings'    => [
                'has_sections' => $dto->hasSections,
                'per_page'     => $dto->perPage,
            ],
        ];

        if ($id !== null && $this->repository->findById($id) !== null) {
            $this->repository->update($id, $attributes);
            $this->codeCache->invalidate();

            return true;
        }

        $this->repository->create($attributes);
        $this->codeCache->invalidate();

        return false;
    }
}
