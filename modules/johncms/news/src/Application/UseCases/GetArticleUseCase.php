<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Modules\News\Application\Exceptions\ArticleNotFoundException;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;

final readonly class GetArticleUseCase
{
    public function __construct(
        private NewsArticleRepositoryInterface $articles,
    ) {
    }

    /**
     * @throws ArticleNotFoundException
     */
    public function execute(int $id): NewsArticle
    {
        return $this->articles->findById($id) ?? throw new ArticleNotFoundException();
    }
}
