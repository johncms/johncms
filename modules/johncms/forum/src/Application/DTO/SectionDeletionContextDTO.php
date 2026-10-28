<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

use Johncms\Modules\Forum\Domain\Models\ForumSection;

/**
 * What deleting a section of the forum involves: what it holds, and where that can go first.
 */
final readonly class SectionDeletionContextDTO
{
    /**
     * @param list<array{id: int, title: string}> $targets Where what the section holds can go;
     *                                                     empty for an empty section.
     */
    public function __construct(
        public ForumSection $section,
        public bool $holdsTopics,
        public int $sections,
        public int $topics,
        public array $targets,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->holdsTopics ? $this->topics === 0 : $this->sections === 0;
    }
}
