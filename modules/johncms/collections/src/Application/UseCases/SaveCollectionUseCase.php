<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
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
        private \Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface $transaction,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
    ) {
    }

    /**
     * Creates or updates a collection. Returns the id of the collection saved, so the panel
     * can open a new one right away.
     *
     * @throws CollectionCodeAlreadyExistsException when the code belongs to another collection
     * @throws CollectionCodeReservedException when the code collides with a reserved top-level segment
     */
    public function execute(?int $id, CollectionFormDTO $dto): int
    {
        return $this->transaction->run(function () use ($id, $dto): int {
            $current = $id !== null ? $this->repository->findForUpdate($id) : null;
            if ($current?->owner_module !== null && $current->code !== $dto->code) {
                throw new \Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException(d__('collections', 'The identity of a module-owned collection cannot be changed here.'));
            }
            if ($current?->owner_module === null && $this->reservedCodeChecker->isReserved($dto->code)) {
                throw new CollectionCodeReservedException();
            }
            if (! preg_match('/^[a-z0-9_.-]+$/D', $dto->contentPermission)) {
                throw new \Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException(d__('collections', 'Choose a valid content permission.'));
            }
            $existing = $this->repository->findByCode($dto->code);
            if ($existing !== null && $existing->id !== $id) {
                throw new CollectionCodeAlreadyExistsException();
            }
            $before = $current !== null ? new CollectionChangeStateDTO(null, $current->active, $current->code) : null;
            $attributes = [
            'code'        => $dto->code,
            'name'        => $current?->owner_module !== null ? $current->getRawOriginal('name') : $dto->name,
            'description' => $dto->description,
            'active'      => $dto->active,
            'public'      => $dto->public,
            'sort'        => $dto->sort,
            'settings'    => array_replace($current->settings ?? [], [
                'has_sections' => $dto->hasSections,
                'per_page'     => $dto->perPage,
                '_content_permission' => $current?->owner_module !== null ? ($current->settings['_content_permission'] ?? 'collections.manage') : $dto->contentPermission,
            ]),
            ];

            if ($current !== null) {
                $this->repository->update($id, $attributes);
                $this->changes->dispatch(new CollectionChangeDTO('collection', 'saved', $id, $id, $before, new CollectionChangeStateDTO(null, $dto->active, $dto->code)));
                $this->transaction->afterCommit(fn () => $this->codeCache->invalidate());

                return $id;
            }

            $collection = $this->repository->create($attributes);
            $this->changes->dispatch(new CollectionChangeDTO('collection', 'saved', $collection->id, $collection->id, null, new CollectionChangeStateDTO(null, $dto->active, $dto->code)));
            $this->transaction->afterCommit(fn () => $this->codeCache->invalidate());

            return $collection->id;
        });
    }
}
