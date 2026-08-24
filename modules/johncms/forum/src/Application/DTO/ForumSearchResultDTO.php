<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumSearchResultDTO
{
    /**
     * @param array<int, array<string, mixed>> $results
     * @param string[] $historyTerms
     */
    public function __construct(
        public string $query,
        public bool $searchInTopicNames,
        public int $total,
        public array $results,
        public array $historyTerms,
        public int $start,
    ) {
    }
}
