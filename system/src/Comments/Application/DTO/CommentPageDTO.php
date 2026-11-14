<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\DTO;

/** A page of a discussion and what the visitor may do on it. */
final readonly class CommentPageDTO
{
    /**
     * @param list<CommentViewDTO> $comments
     * @param string               $refusal  Why the visitor may not write, when they may not; empty otherwise.
     */
    public function __construct(
        public array $comments,
        public bool $canWrite,
        public string $refusal,
    ) {
    }
}
