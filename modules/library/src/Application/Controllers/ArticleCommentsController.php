<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Comments;
use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;

final readonly class ArticleCommentsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id): string
    {
        if (! $this->currentUser->isValid()) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'         => __('Comments'),
                'type'          => 'alert-danger',
                'message'       => __('Access forbidden'),
                'back_url'      => '/library/',
                'back_url_name' => __('Library'),
            ]);
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            http_response_code(404);
            return $this->render->render('system::pages/result', [
                'title'         => __('Comments'),
                'type'          => 'alert-danger',
                'message'       => __('Access forbidden'),
                'back_url'      => '/library/',
                'back_url_name' => __('Library'),
            ]);
        }

        $dir_nav = new Tree($article->cat_id);
        $dir_nav->processNavPanel();
        $dir_nav->printNavPanel();

        $articleName = $this->tools->checkout($article->name);
        $shortName = mb_strlen($article->name) > 30
            ? mb_substr($article->name, 0, 30) . '...'
            : $article->name;
        $documentTitle = $this->tools->checkout($shortName) . ' — ' . __('Comments') . ' — ' . __('Library');

        $this->navChain->add($articleName, '/library/?id=' . $id);
        $this->navChain->add(__('Comments'));

        global $mod, $start;
        $mod = $this->request->getQuery('mod', '');
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $start = isset($_REQUEST['page'])
            ? ($page - 1) * (int) $this->currentUser->config->kmess
            : (isset($_GET['start']) ? abs((int) $_GET['start']) : 0);

        $meta = new PageMeta($documentTitle, $page);

        ob_start();
        $comments = new Comments([
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
            'back_url'       => '/library/?id=' . $id,
        ]);
        $output = (string) ob_get_clean();

        if ($comments->added) {
            $article->increment('comm_count');
        }

        return $output;
    }
}
