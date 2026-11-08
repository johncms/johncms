<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Domain\Query;

use Johncms\Modules\Collections\Domain\Enums\FieldType;
use Johncms\Modules\Collections\Domain\Models\ContentCollectionField;

/** What a selection of items needs to know about a field: where its values are and how they read. */
final readonly class CollectionFieldDefinition
{
    public function __construct(
        public int $id,
        public string $code,
        public FieldType $type,
        public bool $multiple,
        public ?string $role,
    ) {
    }

    public static function of(ContentCollectionField $field): self
    {
        return new self(
            id:       $field->id,
            code:     $field->code,
            type:     $field->type,
            multiple: $field->multiple,
            role:     $field->role,
        );
    }
}
