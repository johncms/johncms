<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class VoteKarmaCommand
{
    public function __construct(
        public int $type,
        public int $points,
        public string $text,
    ) {
    }
}
