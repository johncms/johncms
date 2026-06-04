<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\DTO;

final readonly class UpdateUserSettingsCommand
{
    public function __construct(
        public int $timeshift,
        public bool $directUrl,
        public bool $youtube,
        public int $fieldHeight,
        public int $kmess,
        // Selected language ISO code (empty when the form provides none)
        public string $lng,
    ) {
    }
}
