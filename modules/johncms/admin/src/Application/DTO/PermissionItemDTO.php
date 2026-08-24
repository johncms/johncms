<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * One checkbox of the permission matrix.
 */
final readonly class PermissionItemDTO
{
    public function __construct(
        public string $key,
        public string $label,
        public bool $granted,
    ) {
    }
}
