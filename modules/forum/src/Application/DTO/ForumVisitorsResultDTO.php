<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumVisitorsResultDTO
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $start,
    ) {
    }
}
