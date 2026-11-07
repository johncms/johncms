<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionRelationImpactDTO
{
    /**
     * @param list<array{target: int, name: string, field: string, source: ?string}> $blockers What keeps
     *        each target: a field and the item that links to it, or a field whose default it is (no source).
     * @param list<array{field: string, collection: string}> $schemaBlockers The relation fields of other
     *        collections that keep the deleted collection.
     */
    public function __construct(
        public int $clearValues,
        public int $blockedValues,
        public int $defaultFields,
        public int $schemaFields,
        public string $revision,
        public array $blockers = [],
        public array $schemaBlockers = [],
    ) {
    }

    public function isBlocked(): bool
    {
        return $this->blockedValues > 0 || $this->defaultFields > 0 || $this->schemaFields > 0;
    }

    /** @return list<int> The targets that cannot go. */
    public function blockedTargets(): array
    {
        return array_values(array_unique(array_column($this->blockers, 'target')));
    }
}
