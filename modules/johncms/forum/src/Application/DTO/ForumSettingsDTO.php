<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

final readonly class ForumSettingsDTO
{
    public const int DEFAULT_MAX_FILES = 5;
    public const int MAX_FILES_LIMIT = 20;

    public function __construct(
        public bool $fileCounters,
        /** How many files one post may hold, from 1 to MAX_FILES_LIMIT. */
        public int $maxFilesPerPost,
        public string $topicKeywords,
        public string $topicDescription,
        public string $sectionKeywords,
        public string $sectionDescription,
        public string $forumKeywords,
        public string $forumDescription,
    ) {
    }

    /**
     * The settings as the configuration holds them; a key it lacks is empty.
     *
     * @param array<string, mixed> $settings
     */
    public static function fromArray(array $settings): self
    {
        return new self(
            fileCounters: (bool) ($settings['file_counters'] ?? false),
            maxFilesPerPost: self::clampMaxFiles((int) ($settings['max_files_per_post'] ?? self::DEFAULT_MAX_FILES)),
            topicKeywords: (string) ($settings['topic_keywords'] ?? ''),
            topicDescription: (string) ($settings['topic_description'] ?? ''),
            sectionKeywords: (string) ($settings['section_keywords'] ?? ''),
            sectionDescription: (string) ($settings['section_description'] ?? ''),
            forumKeywords: (string) ($settings['forum_keywords'] ?? ''),
            forumDescription: (string) ($settings['forum_description'] ?? ''),
        );
    }

    public static function clampMaxFiles(int $value): int
    {
        return max(1, min(self::MAX_FILES_LIMIT, $value));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'file_counters'       => $this->fileCounters ? 1 : 0,
            'max_files_per_post'  => $this->maxFilesPerPost,
            'topic_keywords'      => $this->topicKeywords,
            'topic_description'   => $this->topicDescription,
            'section_keywords'    => $this->sectionKeywords,
            'section_description' => $this->sectionDescription,
            'forum_keywords'      => $this->forumKeywords,
            'forum_description'   => $this->forumDescription,
        ];
    }
}
