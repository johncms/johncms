<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/** A topic as a row of a listing, with the counts the visitor may see. */
final readonly class ForumTopicRowDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $url,
        /** The last page of the topic; its first one when it has only that. */
        public string $lastPageUrl,
        public int $posts,
        public string $author,
        public string $lastAuthor,
        /** Date of the last post, formatted; empty when nothing was written. */
        public string $lastDate,
        public bool $pinned,
        public bool $closed,
        public bool $deleted,
        public string $deletedBy,
        public bool $hasPoll,
        /** Something was written since the member read the topic; false for a guest. */
        public bool $unread,
        /** Where the topic is, for the listings across the forum; empty elsewhere. */
        public string $sectionName,
        public string $sectionUrl,
        public string $categoryName,
        public string $categoryUrl,
    ) {
    }
}
