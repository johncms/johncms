<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\Exceptions\CollectionItemFormException;
use Johncms\Modules\Collections\Domain\Repository\CollectionTransactionInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionRepositoryInterface;
use Johncms\Modules\Collections\Domain\Repository\ContentCollectionItemRepositoryInterface;

final readonly class CollectionItemRelationsApi implements CollectionItemRelationsApiInterface
{
    public function __construct(private CollectionTransactionInterface $transaction, private ContentCollectionRepositoryInterface $collections, private ContentCollectionItemRepositoryInterface $items)
    {
    }

    public function withCollectionLock(int $collectionId, callable $operation): mixed
    {
        return $this->transaction->run(function () use ($collectionId, $operation): mixed {
            $this->collections->findForUpdate($collectionId);
            return $operation();
        });
    }

    public function withItemLock(int $collectionId, int $itemId, callable $operation, bool $publishedOnly = false): mixed
    {
        return $this->transaction->run(function () use ($collectionId, $itemId, $operation, $publishedOnly): mixed {
            $collection = $this->collections->findForUpdate($collectionId);
            $item = $collection !== null ? ($publishedOnly ? $this->items->findVisibleByIdForUpdate($collectionId, $itemId) : $this->items->findForUpdate($itemId)) : null;
            if ($item === null || $item->collection_id !== $collectionId) {
                throw new CollectionItemFormException(['id' => [d__('collections', 'The item does not belong to this collection or no longer exists.')]]);
            }
            return $operation();
        });
    }
}
