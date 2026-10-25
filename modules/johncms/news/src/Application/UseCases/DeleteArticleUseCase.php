<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\UseCases;

use Johncms\Files\FileStore;
use Johncms\Modules\News\Application\Exceptions\ArticleNotFoundException;
use Johncms\Modules\News\Domain\Models\NewsArticle;
use Johncms\Modules\News\Domain\Repository\NewsArticleRepositoryInterface;

/**
 * Deletes an article and the files attached to it. The row stays, marked as deleted; the files
 * are gone for good.
 */
final readonly class DeleteArticleUseCase
{
    public function __construct(
        private NewsArticleRepositoryInterface $articles,
        private FileStore $files,
    ) {
    }

    /**
     * @return NewsArticle The article as it was, for the caller to say where it stood.
     * @throws ArticleNotFoundException
     */
    public function execute(int $id): NewsArticle
    {
        $article = $this->articles->findById($id) ?? throw new ArticleNotFoundException();

        if (! empty($article->attached_files)) {
            $this->files->deleteMany($article->attached_files);
        }

        $this->articles->delete($article);

        return $article;
    }
}
