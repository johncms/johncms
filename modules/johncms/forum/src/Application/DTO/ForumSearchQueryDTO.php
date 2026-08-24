<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumSearchQueryDTO
{
    public function __construct(
        public int $start,
        public string $search,
        public bool $searchInTopicNames,
    ) {
    }
}
