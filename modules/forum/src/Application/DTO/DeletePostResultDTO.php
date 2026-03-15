<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class DeletePostResultDTO
{
    public function __construct(
        public string $redirectUrl,
    ) {
    }
}
