<?php

declare(strict_types=1);

namespace Johncms\Modules\Collections\Application\DTO;

use DateTimeImmutable;

/** What a visitor finds in a section together with its subsections. */
final readonly class CollectionSectionStatsDTO
{
    public function __construct(
        public int $sectionId,
        public int $items,
        public ?DateTimeImmutable $lastPublishedAt,
    ) {
    }
}
