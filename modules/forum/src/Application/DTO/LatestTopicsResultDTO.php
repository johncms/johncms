<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class LatestTopicsResultDTO
{
    /**
     * @param array<int, array<string, mixed>> $topics
     */
    public function __construct(
        public array $topics,
        public int $total,
    ) {
    }
}
