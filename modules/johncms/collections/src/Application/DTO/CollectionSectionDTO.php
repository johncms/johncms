<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use DateTimeImmutable;

final readonly class CollectionSectionDTO
{
    /**
     * @param string|null $summary A short plain text about the section, for its heading and its card.
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public int $id,
        public int $collectionId,
        public ?int $parent,
        public string $code,
        public string $name,
        public ?string $description,
        public ?string $summary,
        public bool $active,
        public int $sort,
        public array $metadata,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }
}
