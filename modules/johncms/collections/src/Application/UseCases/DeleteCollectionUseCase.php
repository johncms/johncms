<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class DeleteCollectionUseCase
{
    public function __construct(
        private ContentCollectionRepositoryInterface $repository,
        private CollectionCodeCacheInterface $codeCache,
        private CollectionTransactionInterface $transaction,
        private CollectionDeletionPlanner $planner,
        private DeleteCollectionItemUseCase $deleteItem,
        private ContentCollectionSectionRepositoryInterface $sections,
        private ContentCollectionFieldRepositoryInterface $fields,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
    ) {
    }

    public function execute(int $id, ?string $expectedRevision = null): void
    {
        $this->transaction->run(function () use ($id, $expectedRevision): void {
            $plan = $this->planner->plan(collectionId: $id, forUpdate: true);
            $this->planner->ensureRevision($plan->preview, $expectedRevision);
            if ($this->fields->hasExternalRelationsToCollectionForUpdate($id)) {
                throw new CollectionDeletionException([
                    'relations' => [d__('collections', 'Other collections have relation fields targeting this collection. Remove or retarget those fields before deleting it.')],
                ]);
            }
            $this->changes->ensureOwnerLoaded($plan->preview->ownerModule);
            foreach ($plan->itemIds as $itemId) {
                $this->deleteItem->execute(id: $itemId, collectionId: $id, deletedItemIds: $plan->itemIds, deletedCollectionId: $id);
            }
            foreach (array_reverse($plan->sectionIds) as $sectionId) {
                $this->changes->dispatch(new CollectionChangeDTO('section', 'deleted', $id, $sectionId, $plan->sectionStates[$sectionId], null));
                $this->sections->delete($sectionId);
            }
            foreach ($plan->fieldIds as $fieldId) {
                $this->changes->dispatch(new CollectionChangeDTO('field', 'deleted', $id, $fieldId, $plan->fieldStates[$fieldId], null));
                $this->fields->delete($fieldId);
            }
            $this->changes->dispatch(new CollectionChangeDTO('collection', 'deleted', $id, $id, $plan->collectionState, null));
            $this->repository->delete($id);
            $this->transaction->afterCommit(fn () => $this->codeCache->invalidate());
        });
    }
}
