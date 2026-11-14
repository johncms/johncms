<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\DTO;

use Twig\Markup;

/** One comment as the discussion shows it to the current visitor. */
final readonly class CommentViewDTO
{
    /**
     * @param int                   $number             Position in the discussion, from 1.
     * @param CommentAuthorDTO|null $author             Null when the account is gone or was never known.
     * @param string                $authorName         The name it was written under.
     * @param string                $editedAt           Empty when it was never edited.
     * @param int                   $authorCommentCount Comments of the same author under the subject.
     * @param array{ip: string, ip_via_proxy: string, user_agent: string}|null $origin For the staff only.
     */
    public function __construct(
        public int $id,
        public int $number,
        public Markup $text,
        public string $createdAt,
        public ?CommentAuthorDTO $author,
        public string $authorName,
        public string $editedAt,
        public string $editorName,
        public int $editCount,
        public bool $canEdit,
        public bool $canDelete,
        public int $authorCommentCount,
        public ?array $origin,
    ) {
    }
}
