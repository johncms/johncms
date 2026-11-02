<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionPreviewAccessDeniedException;

final readonly class CollectionsPreviewApi implements CollectionsPreviewApiInterface
{
    public function __construct(
        private CollectionsApiInterface $api,
        private \Johncms\Modules\Collections\Application\UseCases\EnsureCollectionContentAccessUseCase $guard,
    ) {
    }

    public function findItem(int $collectionId, int $id): ?CollectionItemDTO
    {
        try {
            $this->guard->execute($collectionId);
        } catch (\Johncms\Modules\Collections\Application\Exceptions\CollectionAccessDeniedException $exception) {
            throw new CollectionPreviewAccessDeniedException($exception->getMessage(), previous: $exception);
        }
        $item = $this->api->getItem($id);
        return $item !== null && $item->collectionId === $collectionId ? $item : null;
    }
}
