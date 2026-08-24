<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class PollVotersResultDTO
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public string $pollName,
        public array $items,
        public int $total,
    ) {
    }
}
