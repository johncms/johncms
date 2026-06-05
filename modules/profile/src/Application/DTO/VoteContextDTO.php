<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class VoteContextDTO
{
    public function __construct(
        public int $targetId,
        public string $targetName,
        public int $availablePoints,
    ) {
    }
}
