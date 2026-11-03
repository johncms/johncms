<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

use Twig\Markup;

final readonly class NewsCommentViewDTO
{
    /** @param array<string, mixed> $moderatorData */
    public function __construct(
        public int $id,
        public int $itemId,
        public int $userId,
        public Markup $text,
        public string $createdAt,
        public ?NewsAuthorDTO $author,
        public bool $canDelete,
        public bool $canReply,
        public array $moderatorData = [],
    ) {
    }
}
