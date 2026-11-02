<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionCodeCacheInterface;
use Johncms\Modules\Collections\Application\Services\CollectionDeletionPlanner;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

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
    ) {
    }

    public function execute(int $id, ?string $expectedRevision = null): void
    {
        $this->transaction->run(function () use ($id, $expectedRevision): void {
            $plan = $this->planner->plan($id, forUpdate: true);
            $this->planner->ensureRevision($plan->preview, $expectedRevision);
            foreach ($plan->itemIds as $itemId) {
                $this->deleteItem->execute($itemId, $id);
            }
            foreach (array_reverse($plan->sectionIds) as $sectionId) {
                $this->sections->delete($sectionId);
            }
            foreach ($plan->fieldIds as $fieldId) {
                $this->fields->delete($fieldId);
            }
            $this->repository->delete($id);
        });
        $this->codeCache->invalidate();
    }
}
