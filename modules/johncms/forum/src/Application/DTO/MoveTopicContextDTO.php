<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumSection;
use Johncms\Modules\Forum\Domain\Models\ForumTopic;

final readonly class MoveTopicContextDTO
{
    /**
     * @param ForumSection[] $currentSections
     * @param ForumSection[] $otherCategories
     */
    public function __construct(
        public ForumTopic $topic,
        public ForumSection $currentSection,
        public array $currentSections,
        public array $otherCategories,
    ) {
    }
}
