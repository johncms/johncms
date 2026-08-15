<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\DTO;

/**
 * The permissions of one module, as the role editor lists them.
 */
final readonly class PermissionGroupDTO
{
    /**
     * @param list<PermissionItemDTO> $items
     */
    public function __construct(
        public string $key,
        public string $label,
        public array $items,
    ) {
    }
}
