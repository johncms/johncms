<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use Johncms\Modules\Collections\Domain\Enums\FieldType;

final readonly class CollectionFieldDTO
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public int $id,
        public int $collectionId,
        public string $code,
        public string $name,
        public FieldType $type,
        public bool $required,
        public bool $multiple,
        public bool $inList,
        public int $sort,
        public ?string $role,
        public array $settings,
        public ?string $ownerModule,
        public string $messageId,
        public ?string $translationDomain,
    ) {
    }
}
