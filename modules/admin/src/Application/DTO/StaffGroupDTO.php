<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

final readonly class StaffGroupDTO
{
    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function __construct(
        public string $name,
        public array $items,
    ) {
    }
}
