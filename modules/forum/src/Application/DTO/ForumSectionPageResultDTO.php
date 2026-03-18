<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumSection;

final readonly class ForumSectionPageResultDTO
{
    /**
     * @param array<string, mixed> $viewData
     */
    public function __construct(
        public ForumSection $section,
        public string $template,
        public array $viewData,
        public int $filesCount,
        public int $onlineUsers,
        public int $onlineGuests,
        public string $canonical,
    ) {
    }
}
