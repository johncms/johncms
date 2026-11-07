<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionBulkPreviewDTO
{
    /** @param list<int> $ids
     * @param list<string> $names
     */
    public function __construct(
        public string $action,
        public array $ids,
        public array $names,
        public string $revision,
        public ?int $targetSectionId,
        public ?string $targetSectionName,
        public ?CollectionRelationImpactDTO $relations,
    ) {
    }

    public function canExecute(): bool
    {
        return $this->relations === null || ! $this->relations->isBlocked();
    }
}
