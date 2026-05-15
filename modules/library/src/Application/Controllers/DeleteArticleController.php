<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;

final readonly class DeleteArticleController
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
        if (! ($this->currentUser->rights > 4)) {
            http_response_code(403);
            return $this->render->render('system::pages/result', [
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => __('Access forbidden'),
            ]);
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            return $this->render->render('system::pages/result', [
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => __('Articles do not exist'),
            ]);
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($this->tools->checkout($article->name), '/library/?id=' . $id);
        $this->navChain->add(__('Delete Article'));

        $this->render->addData([
            'title'      => __('Delete Article'),
            'page_title' => __('Delete Article'),
        ]);

        $deleted = false;

        if ($this->request->getQuery('yes', null) !== null) {
            Utils::unlinkImages($id);
            $article->delete();
            $deleted = true;
        }

        return $this->render->render('library::delete_article', [
            'id'      => $id,
            'name'    => $article->name,
            'deleted' => $deleted,
        ]);
    }
}
