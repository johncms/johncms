<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\Services;

use Johncms\Cache\CacheInterface;
use Johncms\Modules\Collections\Application\DTO\CollectionChangeDTO;
use Johncms\Modules\Collections\Application\Extension\CollectionChangeHandlerInterface;

final readonly class CollectionCacheInvalidator implements CollectionChangeHandlerInterface
{
    public function __construct(
        private CacheInterface $cache,
        private CollectionCodeCacheInterface $codes,
        private CollectionStructureMemo $memo,
    ) {
    }

    public static function tag(int $collectionId): string
    {
        return 'collections_' . $collectionId;
    }

    public function withinTransaction(CollectionChangeDTO $change): void
    {
    }

    public function afterCommit(CollectionChangeDTO $change): void
    {
        $this->cache->invalidateTags(self::tag($change->collectionId));
        $this->memo->reset();
        if ($change->entity === 'collection') {
            $this->codes->invalidate();
            // A collection is looked up before its id is known, so that entry is not under its tag.
            $this->memo->invalidate();
        }
    }
}
