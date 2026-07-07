<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionFormDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeAlreadyExistsException;
use Johncms\Modules\Collections\Application\Exceptions\CollectionCodeReservedException;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\ReservedCodeCheckerInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;

final readonly class SaveCollectionUseCase
{
    public function __construct(
        private ContentCollectionRepositoryInterface $repository,
        private CollectionCodeCacheInterface $codeCache,
        private ReservedCodeCheckerInterface $reservedCodeChecker,
    ) {
    }

    /**
     * Creates or updates a collection. Returns true when an existing collection
     * was updated, false when a new one was created.
     *
     * @throws CollectionCodeAlreadyExistsException when the code belongs to another collection
     * @throws CollectionCodeReservedException when the code collides with a reserved top-level segment
     */
    public function execute(?int $id, CollectionFormDTO $dto): bool
    {
        if ($this->reservedCodeChecker->isReserved($dto->code)) {
            throw new CollectionCodeReservedException();
        }

        $existing = $this->repository->findByCode($dto->code);
        if ($existing !== null && $existing->id !== $id) {
            throw new CollectionCodeAlreadyExistsException();
        }

        $attributes = [
            'code'        => $dto->code,
            'name'        => $dto->name,
            'description' => $dto->description,
            'active'      => $dto->active,
            'public'      => $dto->public,
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
