<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

/** Immutable item selection; base references use item.*, custom references use field.*. */
final readonly class ContentCollectionItemQuery
{
    /**
     * @param list<FieldFilterDTO> $filters
     * @param list<OrderByDTO> $orderBy
     * @param list<string>|null $fields Custom field codes: null loads all, [] loads none.
     */
    public function __construct(
        public int $collectionId,
        public ?int $sectionId = null,
        public bool $includeSubsections = false,
        public array $filters = [],
        public bool $onlyActive = true,
        public array $orderBy = [],
        public int $limit = 100,
        public int $offset = 0,
        public ?array $fields = null,
        public bool $withoutSection = false,
        public \DateTimeImmutable $at = new \DateTimeImmutable('now', new \DateTimeZone('UTC')),
    ) {
        if (($withoutSection && ($sectionId !== null || $includeSubsections)) || ($includeSubsections && $sectionId === null)) {
            throw new \InvalidArgumentException('Choose collection, root-only, exact section or section subtree explicitly.');
        }
        if ($collectionId < 1 || ($sectionId !== null && $sectionId < 1) || $limit < 1 || $limit > 1000 || $offset < 0 || $offset > 1000000) {
            throw new \InvalidArgumentException('Use valid collection/section ids, limit 1..1000 and offset 0..1000000.');
        }
    }
    /** Public readers cannot disable visibility; default ordering uses the effective publication date. */
    public function forPublication(): self
    {
        return new self(
            $this->collectionId,
            $this->sectionId,
            $this->includeSubsections,
            $this->filters,
            true,
            $this->orderBy !== [] ? $this->orderBy : [
                new OrderByDTO('item.published_at', \Johncms\Modules\Collections\Domain\Enums\SortDirection::Desc),
                new OrderByDTO('item.id', \Johncms\Modules\Collections\Domain\Enums\SortDirection::Desc),
            ],
            $this->limit,
            $this->offset,
            $this->fields,
            $this->withoutSection,
            $this->at
        );
    }
}
