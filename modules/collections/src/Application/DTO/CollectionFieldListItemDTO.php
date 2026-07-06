<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use Johncms\Modules\Collections\Domain\Enums\FieldType;

/**
 * A single row of the admin field list. Presentation URLs and the type label are
 * built in the controller.
 */
final readonly class CollectionFieldListItemDTO
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public FieldType $type,
        public bool $required,
        public bool $multiple,
        public int $sort,
    ) {
    }
}
