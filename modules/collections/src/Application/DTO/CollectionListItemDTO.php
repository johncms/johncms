<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

/**
 * A single row of the admin collections list. Presentation URLs are built in
 * the controller.
 */
final readonly class CollectionListItemDTO
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public bool $active,
        public int $sort,
    ) {
    }
}
