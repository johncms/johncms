<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\DTO;

use Johncms\Comments\CommentSubjectDTO;

/** A comment of a feed of the latest ones, with the object it was left under. */
final readonly class LatestCommentDTO
{
    /**
     * @param string $html      The stored text, for a feed that renders it its own way.
     * @param int    $writtenAt Unix time.
     */
    public function __construct(
        public CommentSubjectDTO $subject,
        public CommentViewDTO $comment,
        public int $authorId,
        public string $html,
        public int $writtenAt,
    ) {
    }

    public function permalink(): string
    {
        return $this->subject->discussionUrl . '/comment/' . $this->comment->id;
    }
}
