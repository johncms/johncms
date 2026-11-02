<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\UseCases;

use Johncms\Modules\Collections\Application\Services\CollectionChangeDispatcher;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeStateDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionSchemaException;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionFieldRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Updates\SystemOperationContext;

final readonly class DeleteCollectionFieldUseCase
{
    public function __construct(
        private ContentCollectionFieldRepositoryInterface $repository,
        private CollectionTransactionInterface $transaction,
        private ContentCollectionRepositoryInterface $collections,
        private CollectionChangeDispatcher $changes = new CollectionChangeDispatcher(),
        private SystemOperationContext $context = new SystemOperationContext(),
    ) {
    }

    public function execute(int $id): void
    {
        $initial = $this->repository->findById($id);
        if ($initial === null) {
            return;
        }
        $this->transaction->run(function () use ($id, $initial): void {
            $collection = $this->collections->findForUpdate($initial->collection_id);
            $field = $this->repository->findForUpdate($id);
            if ($collection === null || $field === null || $field->collection_id !== $initial->collection_id) {
                throw new CollectionSchemaException(d__('collections', 'The field does not belong to this collection or no longer exists.'));
            }
            if ($field->owner_module !== null && ! $this->context->isActive()) {
                throw new CollectionSchemaException(d__('collections', 'This field is managed by its module. Change the declaration or use its trusted update hook.'));
            }
            $this->changes->dispatch(new CollectionChangeDTO('field', 'deleted', $field->collection_id, $id, new CollectionChangeStateDTO(null, null, $field->code), null));
            $this->repository->delete($id);
        });
    }
}
