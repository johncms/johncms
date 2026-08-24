<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

/**
 * Immutable query specification for selecting collection items.
 *
 * Analogous to Bitrix `CIBlockElement::GetList`: it collects the selection
 * conditions and is passed to the repository, giving a single query API to any
 * collection. Custom-field (EAV) filtering/sorting is implemented in a later
 * iteration; the core iteration honors collection/section/active/order/limit.
 */
final readonly class ContentCollectionItemQuery
{
    /**
     * @param list<FieldFilterDTO> $filters
     * @param list<OrderByDTO> $orderBy
     */
    public function __construct(
        public int $collectionId,
        public ?int $sectionId = null,
        public bool $includeSubsections = false,
        public array $filters = [],
        public bool $onlyActive = true,
        public array $orderBy = [],
        public ?int $limit = null,
        public int $offset = 0,
    ) {
    }
}
