<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\Authorization\CorePermissions;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Library\Application\Services\ArticleTextRenderer;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Application\Services\LibraryPermissions;
use Johncms\Modules\Library\Application\Services\Rating;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Users\User;
use Johncms\Utils\DateFormatterInterface;
use Symfony\Component\HttpFoundation\Response;

final readonly class ArticleController
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private Session $session,
        private NavChain $navChain,
        private DateFormatterInterface $dateFormatter,
        private User $currentUser,
        private LibraryArticlePathService $articlePathService,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
    }

    public function __invoke(string $libraryPath): ViewResponse
    {
        $parsed = $this->articlePathService->parseArticlePath('/library/' . ltrim($libraryPath, '/'));
        if ($parsed === null) {
            pageNotFound();
        }

        $id = $parsed['articleId'];
        $article = LibraryText::query()->find($id);

        if ($article === null || (! $article->premod && ! $this->accessChecker->allows(LibraryPermissions::MODERATE))) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Library'),
                    'type'    => 'alert-danger',
                    'message' => __('Articles do not exist'),
                ],
                Response::HTTP_NOT_FOUND
            );
        }

        if (! $this->session->has('lib') || $this->session->get('lib') !== $id) {
            $this->session->set('lib', $id);
            $article->increment('count_views');
        }

        $textRenderer = new ArticleTextRenderer();
        $pages        = $textRenderer->splitIntoPages((string) $article->text);
        $countPages   = count($pages);

        // One text page per pagination page. Canonicalize the ?page parameter
        // (strip page=1/junk, redirect out-of-range to the last page).
        $pagination = $this->paginationFactory->create($countPages, 1);
        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }
        $page = $pagination->getCurrentPage();

        $text = $textRenderer->renderPage($pages[$page - 1], $this->accessChecker->allows(CorePermissions::SMILIES_ADMIN_USE));

        $isAdmin   = $this->accessChecker->allows(LibraryPermissions::MODERATE);
        $moderMenu = $isAdmin || ($this->currentUser->isValid() && (int) $article->uploader_id === (int) $this->currentUser->id);

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($article->name);

        $pageTitle = $article->name;
        $meta      = new PageMeta($pageTitle . ' — ' . __('Library'), $page);

        $tags     = null;
        $uploader = null;
        $rating   = null;
        $userVote = null;
        $cover    = false;

        // Everything about the article itself belongs on its first page only.
        if ($page === 1) {
            $tags = (new Hashtags($id))->getTagLinks();

            $rate     = new Rating($id);
            $userVote = $this->currentUser->isValid() ? $rate->getUserVote() : null;
            $rating   = ['rate' => $rate->getRate(), 'votes' => $rate->getVotesCount()];

            $uploader = [
                'user_id' => $article->uploader_id,
                'name'    => $article->uploader,
                'date'    => $this->dateFormatter->format($article->time),
            ];

            $cover = file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png');
        }

        return new ViewResponse('@library/public/article.twig', [
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'id'          => $article->id,
            'url'         => $article->url,
            'text'        => $text,
            'page'        => $page,
            'count_pages' => $countPages,
            'count_views' => $article->count_views,
            'comments'    => (bool) $article->comments,
            'comm_count'  => $article->comm_count,
            'cover'       => $cover,
            'tags'        => $tags,
            'uploader'    => $uploader,
            'rating'      => $rating,
            'user_vote'   => $userVote,
            'moder_menu'  => $moderMenu,
            'pagination'  => $pagination->hasPages() ? $pagination->render() : null,
        ]);
    }
}
