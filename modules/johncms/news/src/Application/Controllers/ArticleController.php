<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\DTO\NewsArticleViewDTO;
use Johncms\Modules\News\Application\DTO\NewsQueryDTO;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\NavChain;

final readonly class ArticleController
{
    /** Other news offered beside the article. */
    private const int RELATED = 3;

    /** The most viewed news beside the article, and the period they are chosen from. */
    private const int POPULAR = 4;
    private const int POPULAR_DAYS = 30;

    public function __construct(
        private NewsReadServiceInterface $news,
        private NavChain $navChain,
        private AccessCheckerInterface $access,
    ) {
    }

    public function index(string $article_code, string $category = ''): ViewResponse
    {
        $this->navChain->add(d__('news', 'News'), '/news/');
        try {
            $article = $this->news->findByCode($category, $article_code) ?? throw new NewsItemNotFoundException();
        } catch (NewsItemNotFoundException) {
            throw new PageNotFoundException();
        }
        $section = $article->record->sectionId !== null ? $this->news->section($article->record->sectionId) : null;
        if ($section !== null) {
            foreach ($this->news->breadcrumbs($section->record->id) as $crumb) {
                $this->navChain->add($crumb->record->name, $crumb->url);
            }
        }
        $this->navChain->add($article->record->name);
        $this->news->registerView($article->record->id);
        $related = $this->news->related($article, self::RELATED);
        $popular = $this->news->popular(self::POPULAR_DAYS, self::POPULAR, [
            $article->record->id,
            ...array_map(static fn (NewsArticleViewDTO $entry): int => $entry->record->id, $related),
        ]);
        return new ViewResponse('@news/public/article.twig', $article->metadata() + [
            'article' => $article->toTemplate(), 'current_section' => $section?->toTemplate(),
            'related' => array_map(static fn (NewsArticleViewDTO $entry): array => $entry->toTemplate(), $related),
            'popular' => array_map(static fn (NewsArticleViewDTO $entry): array => $entry->toTemplate(), $popular),
            'author_articles' => $article->author !== null ? $this->news->count(new NewsQueryDTO(authorId: $article->author->id)) : 0,
            'edit_url' => $this->access->allows(NewsPermissions::MANAGE)
                ? '/admin/collections/' . $article->record->collectionId . '/items/' . $article->record->id . '/edit'
                : null,
            'can_write' => $this->access->allows(NewsPermissions::COMMENTS_POST),
        ]);
    }
}
