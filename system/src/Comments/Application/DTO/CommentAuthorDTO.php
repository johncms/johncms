<?php

declare(strict_types=1);

namespace Johncms\Comments\Application\DTO;

/** The account behind a comment, while it exists. */
final readonly class CommentAuthorDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $online,
        public string $rightsName,
    ) {
    }
}
