<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;

final readonly class DeleteCollectionFieldUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
        private \Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface $transaction,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
    ) {
    }

    public function execute(int $id): void
    {
        $this->transaction->run(function () use ($id): void {
            $field = $this->repository->findById($id);
            if ($field === null) {
                return;
            }
            $this->changes->dispatch(new CollectionChangeDTO('field', 'deleted', $field->collection_id, $id, new CollectionChangeStateDTO(null, null, $field->code), null));
            $this->repository->delete($id);
        });
    }
}
