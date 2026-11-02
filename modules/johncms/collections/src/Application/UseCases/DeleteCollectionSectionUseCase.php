<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Domain\Services\SectionTree;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSectionFormException;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionSectionRepositoryInterface;

final readonly class DeleteCollectionSectionUseCase
{
    public function __construct(
        private ContentCollectionSectionRepositoryInterface $repository,
        private ContentCollectionRepositoryInterface $collections,
        private CollectionTransactionInterface $transaction,
    ) {
    }

    public function execute(int $id, int $collectionId): void
    {
        $this->transaction->run(function () use ($id, $collectionId): void {
            $collection = $this->collections->findForUpdate($collectionId);
            $section = $this->repository->findForUpdate($id);
            if ($collection === null || $section === null || $section->collection_id !== $collectionId) {
                throw new CollectionSectionFormException(['id' => [d__('collections', 'The section does not belong to this collection or no longer exists.')]]);
            }
            $tree = SectionTree::of($this->repository->getAllForUpdate($collectionId));
            // Delete leaves first so correctness does not depend on recursive FK support or depth.
            foreach (array_reverse($tree->subtreeOf($id)) as $sectionId) {
                $this->repository->delete($sectionId);
            }
        });
    }
}
