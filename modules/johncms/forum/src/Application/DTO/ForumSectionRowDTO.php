<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/**
 * A section as a row of a listing: what it holds counted over all the sections under it, and the
 * topic where the last post was written.
 */
final readonly class ForumSectionRowDTO
{
    /**
     * @param list<ForumSectionRowDTO> $children The sections right under it, without theirs.
     */
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
        public string $description,
        public bool $holdsTopics,
        public int $topics,
        public int $posts,
        /** Date of the last post, formatted; empty when nothing was written. */
        public string $lastDate,
        public ?ForumLastTopicDTO $lastTopic,
        public array $children,
    ) {
    }
}
