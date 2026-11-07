<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Collections\NewsReadServiceInterface;
use Johncms\Modules\News\Application\Exceptions\NewsItemNotFoundException;
use Johncms\Modules\News\Application\Services\NewsPermissions;
use Johncms\NavChain;

final readonly class ArticleController
{
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
        $this->news->registerView($article->record->id);
        return new ViewResponse('@news/public/article.twig', $article->metadata() + [
            'article' => $article->toTemplate(), 'current_section' => $section?->toTemplate(),
            'edit_url' => $this->access->allows(NewsPermissions::MANAGE)
                ? '/admin/collections/' . $article->record->collectionId . '/items/' . $article->record->id . '/edit'
                : null,
            'can_write' => $this->access->allows(NewsPermissions::COMMENTS_POST),
        ]);
    }
}
