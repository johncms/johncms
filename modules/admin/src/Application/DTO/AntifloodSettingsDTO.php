<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class AntifloodSettingsDTO
{
    public function __construct(
        public int $mode,
        public int $day,
        public int $night,
        public int $dayFrom,
        public int $dayTo,
    ) {
    }
}
