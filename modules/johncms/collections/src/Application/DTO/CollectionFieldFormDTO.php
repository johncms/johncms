<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use Johncms\Modules\Collections\Domain\Enums\FieldType;

/**
 * Validated input for creating or updating a collection field definition.
 */
final readonly class CollectionFieldFormDTO
{
    public function __construct(
        public int $collectionId,
        public string $code,
        public string $name,
        public FieldType $type,
        public bool $required,
        public bool $multiple,
        public int $sort,
    ) {
    }
}
