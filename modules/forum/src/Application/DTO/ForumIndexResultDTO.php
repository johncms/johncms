<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Illuminate\Support\Collection;
use Johncms\Modules\Forum\Domain\Models\ForumSection;

final readonly class ForumIndexResultDTO
{
    /**
     * @param Collection<int, ForumSection> $sections
     */
    public function __construct(
        public Collection $sections,
        public int $onlineUsers,
        public int $onlineGuests,
        public int $filesCount,
        public bool $showFileCounters,
        public string $keywords,
        public string $description,
    ) {
    }
}
