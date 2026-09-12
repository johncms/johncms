<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumSettingsDTO
{
    public function __construct(
        public bool $fileCounters,
        public string $topicKeywords,
        public string $topicDescription,
        public string $sectionKeywords,
        public string $sectionDescription,
        public string $forumKeywords,
        public string $forumDescription,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file_counters'       => $this->fileCounters ? 1 : 0,
            'topic_keywords'      => $this->topicKeywords,
            'topic_description'   => $this->topicDescription,
            'section_keywords'    => $this->sectionKeywords,
            'section_description' => $this->sectionDescription,
            'forum_keywords'      => $this->forumKeywords,
            'forum_description'   => $this->forumDescription,
        ];
    }
}
