<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use DateTimeImmutable;

final readonly class CollectionRecordDTO
{
    /** @param array<string, mixed> $settings */
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?string $description,
        public bool $active,
        public bool $public,
        public int $sort,
        public array $settings,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
    ) {
    }
}
