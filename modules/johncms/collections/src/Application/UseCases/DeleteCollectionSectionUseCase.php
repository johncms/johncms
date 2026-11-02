<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionDeletionException;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Domain\Enums\SectionDeletionMode;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class DeleteCollectionSectionUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $sections,
        private ContentCollectionItemRepositoryInterface $items,
        private CollectionTransactionInterface $transaction,
        private CollectionDeletionPlanner $planner,
        private DeleteCollectionItemUseCase $deleteItem,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
    ) {
    }

    public function execute(int $id, int $collectionId, SectionDeletionMode $mode, ?int $targetId = null, ?string $expectedRevision = null): void
    {
        $this->transaction->run(function () use ($id, $collectionId, $mode, $targetId, $expectedRevision): void {
            $plan = $this->planner->plan($collectionId, $id, true);
            $this->planner->ensureRevision($plan->preview, $expectedRevision);
            $this->changes->ensureOwnerLoaded($plan->preview->ownerModule);
            if ($mode === SectionDeletionMode::MoveItems) {
                $target = $targetId !== null ? $this->sections->findForUpdate($targetId) : null;
                if ($target === null || $target->collection_id !== $collectionId || in_array($targetId, $plan->sectionIds, true)) {
                    throw new CollectionDeletionException(['target' => [d__('collections', 'Choose a destination section of this collection outside the deleted subtree.')]]);
                }
                foreach ($plan->itemIds as $itemId) {
                    $item = $this->items->findForUpdate($itemId);
                    if ($item === null) {
                        throw new CollectionDeletionException(['revision' => [d__('collections', 'The contents changed during deletion. Check the counts and try again.')]]);
                    }
                    if ($this->items->findByCodeForUpdate($collectionId, $targetId, $item->code) !== null) {
                        throw new CollectionDeletionException(['target' => [d__('collections', 'Item codes conflict in the destination. Rename the conflicting items or choose another section.')]]);
                    }
                    $this->items->update($itemId, ['section_id' => $targetId]);
                    $this->changes->dispatch(new CollectionChangeDTO(
                        'item',
                        'moved',
                        $collectionId,
                        $itemId,
                        new CollectionChangeStateDTO($item->section_id, $item->active, $item->code),
                        new CollectionChangeStateDTO($targetId, $item->active, $item->code)
                    ));
                }
            } else {
                if ($targetId !== null) {
                    throw new CollectionDeletionException(['target' => [d__('collections', 'A destination is only allowed when moving items.')]]);
                }
                foreach ($plan->itemIds as $itemId) {
                    $this->deleteItem->execute($itemId, $collectionId);
                }
            }
            foreach (array_reverse($plan->sectionIds) as $sectionId) {
                $this->changes->dispatch(new CollectionChangeDTO('section', 'deleted', $collectionId, $sectionId, $plan->sectionStates[$sectionId], null));
                $this->sections->delete($sectionId);
            }
        });
    }
}
