<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class UpdateMailSettingsCommand
{
    public function __construct(
        public int $access,
    ) {
    }
}
