<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use DateTimeImmutable;

final readonly class CollectionSectionDTO
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public int $id,
        public int $collectionId,
        public ?int $parent,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $active,
        public int $sort,
        public array $metadata,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }
}
