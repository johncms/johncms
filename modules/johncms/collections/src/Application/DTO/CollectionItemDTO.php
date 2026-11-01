<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use DateTimeImmutable;

/**
 * @phpstan-type ScalarValue string|int|float|bool|DateTimeImmutable|null
 */
final readonly class CollectionItemDTO
{
    /** @param array<string, ScalarValue|list<ScalarValue>> $values */
    public function __construct(
        public int $id,
        public int $collectionId,
        public ?int $sectionId,
        public string $code,
        public string $name,
        public bool $active,
        public ?DateTimeImmutable $activeFrom,
        public ?DateTimeImmutable $activeTo,
        public int $sort,
        public ?string $previewText,
        public ?string $detailText,
        public int $viewCount,
        public ?int $createdBy,
        public ?int $updatedBy,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
        public array $values,
    ) {
    }
}
