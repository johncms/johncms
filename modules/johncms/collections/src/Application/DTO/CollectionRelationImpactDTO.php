<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

final readonly class CollectionRelationImpactDTO
{
    public function __construct(
        public int $clearValues,
        public int $blockedValues,
        public int $defaultFields,
        public int $schemaFields,
        public string $revision,
    ) {
    }
}
