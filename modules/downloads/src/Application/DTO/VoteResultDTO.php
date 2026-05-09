<?php

declare(strict_types=1);

namespace Johncms\Modules\Downloads\Application\DTO;

final readonly class VoteResultDTO
{
    public function __construct(
        public int $plus,
        public int $minus,
        public bool $wasAccepted,
    ) {
    }
}
