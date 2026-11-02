<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Api;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionItemDTO;
use Johncms\Modules\Collections\Application\Exceptions\CollectionPreviewAccessDeniedException;
use Johncms\Modules\Collections\Application\Services\CollectionsPermissions;

final readonly class CollectionsPreviewApi implements CollectionsPreviewApiInterface
{
    public function __construct(
        private AccessCheckerInterface $access,
        private CollectionsApiInterface $api,
    ) {
    }

    public function findItem(int $collectionId, int $id): ?CollectionItemDTO
    {
        if (! $this->access->allows(CollectionsPermissions::MANAGE)) {
            throw new CollectionPreviewAccessDeniedException('Collection preview requires collections.manage.');
        }
        $item = $this->api->getItem($id);
        return $item !== null && $item->collectionId === $collectionId ? $item : null;
    }
}
