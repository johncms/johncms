<?php

declare(strict_types=1);

namespace Johncms\Modules\News\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\Pagination;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\News\Application\Collections\NewsDiscussionServiceInterface;
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
        private NewsDiscussionServiceInterface $discussion,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
        private Session $session,
    ) {
    }

    public function index(Request $request, string $article_code, string $category = ''): ViewResponse
    {
        try {
            $article = $this->news->findByCode($category, $article_code) ?? throw new NewsItemNotFoundException();
        } catch (NewsItemNotFoundException) {
            throw new PageNotFoundException();
        }
        $pagination = $this->paginationFactory->create(total: $article->stats->comments, fragment: 'article-comments');
        $redirect = $this->paginationGuard->redirectUrl($pagination);
        if ($redirect !== null) {
            redirect($redirect);
        }
        $metadata = $article->metadata();
        $meta = new PageMeta($metadata['title'], $pagination->getCurrentPage(), $metadata['description']);
        // The live list asks for a page of the discussion: the article is not viewed again.
        if ($request->isXmlHttpRequest()) {
            return new ViewResponse('@news/public/comments-live.twig', ['title' => $meta->title] + $this->discussionData($article, $pagination));
        }

        $this->navChain->add(d__('news', 'News'), '/news/');
        $section = $article->record->sectionId !== null ? $this->news->section($article->record->sectionId) : null;
        if ($section !== null) {
            foreach ($this->news->breadcrumbs($section->record->id) as $crumb) {
                $this->navChain->add($crumb->record->name, $crumb->url);
            }
        }
        $this->navChain->add($article->record->name);
        $this->news->registerView($article);
        $related = $this->news->related($article, self::RELATED);
        $popular = $this->news->popular(self::POPULAR_DAYS, self::POPULAR, [
            $article->record->id,
            ...array_map(static fn (NewsArticleViewDTO $entry): int => $entry->record->id, $related),
        ]);
        return new ViewResponse('@news/public/article.twig', ['title' => $meta->title, 'description' => $meta->description] + $metadata + [
            'article' => $article->toTemplate(), 'current_section' => $section?->toTemplate(),
            'related' => array_map(static fn (NewsArticleViewDTO $entry): array => $entry->toTemplate(), $related),
            'popular' => array_map(static fn (NewsArticleViewDTO $entry): array => $entry->toTemplate(), $popular),
            'author_articles' => $article->author !== null ? $this->news->count(new NewsQueryDTO(authorId: $article->author->id)) : 0,
            'edit_url' => $this->access->allows(NewsPermissions::MANAGE)
                ? '/admin/collections/' . $article->record->collectionId . '/items/' . $article->record->id . '/edit'
                : null,
            'can_write' => $this->access->allows(NewsPermissions::COMMENTS_POST),
        ] + $this->discussionData($article, $pagination));
    }

    /**
     * The comments of the requested page and the result of the last action on them: the article
     * renders them, the live list swaps them.
     *
     * @return array<string, mixed>
     */
    private function discussionData(NewsArticleViewDTO $article, Pagination $pagination): array
    {
        return [
            'article_id' => $article->record->id,
            'comments' => $article->stats->comments > 0
                ? $this->discussion->articleComments($article, $pagination->getPerPage(), $pagination->getOffset())
                : [],
            'comments_total' => $article->stats->comments,
            'comments_offset' => $pagination->getOffset(),
            'comments_page' => $pagination->getCurrentPage(),
            'comments_pagination' => $pagination->hasPages() ? $pagination->render() : null,
            'comment_message' => $this->session->getFlash(CommentsController::MESSAGE),
            'comment_error' => $this->session->getFlash(CommentsController::ERROR),
            'comment_delete_error' => $this->session->getFlash(CommentsController::DELETE_ERROR),
            'comment_added' => $this->session->getFlash(CommentsController::ADDED),
            // The live list keeps the text in the editor, yet the flash is read so that it is not
            // put back into the form of the next page.
            'comment_text' => $this->session->getFlash(CommentsController::TEXT) ?? '',
        ];
    }
}
