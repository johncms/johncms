<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumIndexResultDTO
{
    /**
     * @param list<ForumSectionRowDTO>        $sections The sections of the root of the forum.
     * @param array{topics: int, posts: int} $totals   What they hold together.
     */
    public function __construct(
        public array $sections,
        public array $totals,
        public int $onlineUsers,
        public int $onlineGuests,
        public int $filesCount,
        public bool $showFileCounters,
        public string $keywords,
        public string $description,
    ) {
    }
}
