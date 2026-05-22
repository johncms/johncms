<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Services;

use Johncms\Modules\Library\Domain\Models\LibraryCategory;
use Johncms\Modules\Library\Domain\Models\LibraryText;

final class LibraryArticlePathService
{
    public function __construct(
        private LibraryCategoryPathService $categoryPathService,
    ) {
    }

    public function getArticleSlug(LibraryText $article): string
    {
        $slug = trim((string) $article->slug);

        return $slug !== '' ? $slug : 'article-' . $article->id;
    }

    /**
     * @return array{catPath: string, articleSlug: string, articleId: int}|null
     */
    public function parseArticlePath(string $path): ?array
    {
        $normalized = trim($path, '/');
        if ($normalized === '' || ! str_starts_with($normalized, 'library/')) {
            return null;
        }

        $libraryPath = trim(substr($normalized, strlen('library/')), '/');
        $lastSlashPos = strrpos($libraryPath, '/');
        if ($lastSlashPos === false) {
            return null;
        }

        $catPath = substr($libraryPath, 0, $lastSlashPos);
        $articlePart = substr($libraryPath, $lastSlashPos + 1);

        if ($catPath === '' || preg_match('/^(?<slug>[a-z0-9-]+)-(?<id>\d+)$/', $articlePart, $matches) !== 1) {
            return null;
        }

        return [
            'catPath'     => $catPath,
            'articleSlug' => $matches['slug'],
            'articleId'   => (int) $matches['id'],
        ];
    }

    public function getArticleUrl(LibraryText $article): string
    {
        $category = LibraryCategory::query()->find($article->cat_id);
        if ($category === null) {
            return '/library/';
        }

        $catPath = $this->categoryPathService->getCategoryPath($category);

        return '/library/' . $catPath . '/' . $this->getArticleSlug($article) . '-' . $article->id . '/';
    }

    public function getArticleUrlById(int $id): ?string
    {
        $article = LibraryText::query()->find($id);
        if ($article === null) {
            return null;
        }

        return $this->getArticleUrl($article);
    }
}
