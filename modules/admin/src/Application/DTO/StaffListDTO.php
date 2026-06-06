<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class StaffListDTO
{
    /**
     * @param array<int, StaffGroupDTO> $groups
     */
    public function __construct(
        public array $groups,
        public int $total,
    ) {
    }
}
