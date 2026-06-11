<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\ArticleTextRenderer;
use Johncms\Modules\Library\Application\Services\Hashtags;
use Johncms\Modules\Library\Application\Services\Rating;
use Johncms\Modules\Library\Application\Services\Tree;

final readonly class ArticleController
{
    public function __construct(
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
        private LibraryArticlePathService $articlePathService,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(string $libraryPath): string
    {
        $parsed = $this->articlePathService->parseArticlePath('/library/' . ltrim($libraryPath, '/'));
        if ($parsed === null) {
            pageNotFound();
        }

        $id = $parsed['articleId'];
        $article = LibraryText::query()->find($id);

        if ($article === null || (! $article->premod && ! ($this->currentUser->rights > 4))) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'   => __('Library'),
                'type'    => 'alert-danger',
                'message' => __('Articles do not exist'),
            ]);
        }

        if (! isset($_SESSION['lib']) || $_SESSION['lib'] !== $id) {
            $_SESSION['lib'] = $id;
            $article->increment('count_views');
        }

        $textRenderer = new ArticleTextRenderer();
        $pages        = $textRenderer->splitIntoPages((string) $article->text);
        $countPages   = count($pages);
        $page         = max(1, min((int) $this->request->getQuery('page', 1), $countPages));

        $text = $textRenderer->renderPage($pages[$page - 1], $this->currentUser->rights > 0);

        $isAdmin   = $this->currentUser->rights > 4;
        $moderMenu = $isAdmin || ($this->currentUser->isValid() && (int) $article->uploader_id === (int) $this->currentUser->id);

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($this->tools->checkout($article->name));

        $pageTitle = $this->tools->checkout($article->name);
        $meta      = new PageMeta($pageTitle . ' — ' . __('Library'), $page);
        $this->render->addData([
            'title'      => $meta->title,
            'page_title' => $pageTitle,
        ]);

        $tags        = null;
        $who         = null;
        $ratingVote  = null;
        $ratingView  = null;
        $cover       = null;

        if ($page === 1) {
            $tags = (new Hashtags($id))->getAllStatTags(1) ?: null;

            $rate       = new Rating($id);
            $ratingVote = $this->currentUser->isValid() ? $rate->printVote() : null;
            $ratingView = $rate->viewRate(1);

            $uploader = $article->uploader_id
                ? '<a href="' . config('johncms')['homeurl'] . '/profile/' . $article->uploader_id . '">' . $this->tools->checkout($article->uploader) . '</a>'
                : $this->tools->checkout($article->uploader);
            $who = $uploader . ' (' . $this->tools->displayDate($article->time) . ')';

            $cover = file_exists(UPLOAD_PATH . 'library/images/big/' . $id . '.png');
        }

        $articleUrl = $article->url;

        // One text page per pagination page: the current page is already clamped to the page count.
        $pagination = $this->paginationFactory->create($countPages, 1, 'page', $page);

        return $this->render->render('library::book', [
            'res'         => [
                'id'          => $article->id,
                'url'         => $articleUrl,
                'text'        => $text,
                'name'        => $pageTitle,
                'count_views' => $article->count_views,
                'comm_count'  => $article->comm_count,
                'comments'    => $article->comments,
            ],
            'page'        => $page,
            'count_pages' => $countPages,
            'tags'        => $tags,
            'who'         => $who,
            'ratingVote'  => $ratingVote,
            'ratingView'  => $ratingView,
            'cover'       => $cover,
            'moderMenu'   => $moderMenu,
            'pagination'  => $pagination->render(),
        ]);
    }
}
