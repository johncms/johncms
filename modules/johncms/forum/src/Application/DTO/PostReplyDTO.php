<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\DTO;

/**
 * The post an answer was written to, as the answer recalls it. A hidden one is recalled only as
 * deleted to whoever may not see it: without its author, its words or a link.
 */
final readonly class PostReplyDTO
{
    /**
     * @param string  $authorName The name the post was written under; empty when it is hidden from the visitor.
     * @param string  $excerpt    The start of its own words, as plain text; empty when it holds none or is hidden from the visitor.
     * @param ?string $url        The permanent address of the post; null when it is hidden from the visitor.
     */
    public function __construct(
        public int $id,
        public string $authorName,
        public string $excerpt,
        public ?string $url,
    ) {
    }

    public function isHidden(): bool
    {
        return $this->url === null;
    }
}
