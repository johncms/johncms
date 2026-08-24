<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class UnreadTopicsQueryDTO
{
    public function __construct(
        public int $start,
    ) {
    }
}
