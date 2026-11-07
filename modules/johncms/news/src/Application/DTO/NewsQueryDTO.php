<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

use DateTimeImmutable;

final readonly class NewsQueryDTO
{
    public function __construct(
        public ?int $sectionId = null,
        public bool $includeSubsections = true,
        public string $search = '',
        public ?string $tag = null,
        public int $limit = 20,
        public int $offset = 0,
        public ?DateTimeImmutable $publishedSince = null,
        public ?int $authorId = null,
        /** The most viewed first instead of the latest first. */
        public bool $popular = false,
    ) {
    }
}
