<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Comments;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Library\Application\Services\LibraryArticlePathService;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Symfony\Component\HttpFoundation\Response;

final readonly class ArticleCommentsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private LibraryArticlePathService $articlePathService,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request, int $id): Response
    {
        if (! $this->currentUser->isValid()) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'         => __('Comments'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/library/',
                    'back_url_name' => __('Library'),
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'         => __('Comments'),
                    'type'          => 'alert-danger',
                    'message'       => __('Access forbidden'),
                    'back_url'      => '/library/',
                    'back_url_name' => __('Library'),
                ]),
                Response::HTTP_NOT_FOUND
            );
        }

        $dir_nav = new Tree($article->cat_id);
        $dir_nav->processNavPanel();
        $dir_nav->printNavPanel();

        $articleName = $article->name;
        $shortName = mb_strlen($article->name) > 30
            ? mb_substr($article->name, 0, 30) . '...'
            : $article->name;
        $documentTitle = $shortName . ' — ' . __('Comments') . ' — ' . __('Library');

        $articleUrl = $this->articlePathService->getArticleUrlById($id) ?? '/library/';
        $this->navChain->add($articleName, $articleUrl);
        $this->navChain->add(__('Comments'));

        $mod = $request->queryParam('mod', '');
        $page = max(1, $request->queryInt('page', 1));
        $start = $request->query->has('page')
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : abs($request->queryInt('start', 0));

        $meta = new PageMeta($documentTitle, $page);

        ob_start();
        $comments = new Comments([
            'mod'            => $mod,
            'start'          => $start,
            'comments_table' => 'cms_library_comments',
            'object_table'   => 'library_texts',
            'script'         => '/library/article/' . $id . '/comments',
            'sub_id'         => $id,
            'owner'          => $article->uploader_id,
            'owner_delete'   => true,
            'owner_reply'    => true,
            'owner_edit'     => false,
            'title'          => $meta->title,
            'page_title'     => __('Comments'),
            'back_url'       => $articleUrl,
        ]);
        $output = (string) ob_get_clean();

        if ($comments->added) {
            $article->increment('comm_count');
        }

        return new Response($output);
    }
}
