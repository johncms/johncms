<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\DTO;

/** The comment an answer was written to, as the answer recalls it: who wrote it and how it began. */
final readonly class CommentReplyDTO
{
    /**
     * @param string $authorName The name of the account, or the one the comment was written under.
     * @param string $excerpt    The start of its own words, as plain text; empty when it holds no words, a picture alone.
     * @param string $url        The permanent address of the comment: it finds the page it is on.
     */
    public function __construct(
        public int $id,
        public string $authorName,
        public string $excerpt,
        public string $url,
    ) {
    }
}
