<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class BanUserCommand
{
    public function __construct(
        public int $term,
        public int $timeval,
        public int $time,
        public string $reason,
        public int $banref,
    ) {
    }
}
