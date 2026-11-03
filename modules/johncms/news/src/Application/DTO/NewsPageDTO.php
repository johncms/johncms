<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\DTO;

final readonly class NewsPageDTO
{
    /** @param list<NewsArticleViewDTO> $articles */
    public function __construct(public array $articles, public int $total, public int $limit, public int $offset)
    {
    }

    /** @return list<array<string, mixed>> */
    public function toTemplate(): array
    {
        return array_map(static fn (NewsArticleViewDTO $article): array => $article->toTemplate(), $this->articles);
    }
}
