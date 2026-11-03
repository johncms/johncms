<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

final readonly class NewsCommentPageDTO
{
    /** @param list<NewsCommentViewDTO> $comments */
    public function __construct(public array $comments, public int $total, public int $limit, public int $offset)
    {
    }
}
