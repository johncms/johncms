<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/** Entity is item/section/collection/field; action is saved/moved/deleted. */
final readonly class CollectionChangeDTO
{
    public function __construct(
        public string $entity,
        public string $action,
        public int $collectionId,
        public int $id,
        public ?CollectionChangeStateDTO $before,
        public ?CollectionChangeStateDTO $after,
        public bool $imported = false,
    ) {
        if (! in_array($entity, ['item', 'section', 'collection', 'field'], true) || ! in_array($action, ['saved', 'moved', 'deleted'], true)) {
            throw new \InvalidArgumentException('Invalid collection change entity or action.');
        }
    }
}
