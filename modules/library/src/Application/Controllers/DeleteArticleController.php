<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteArticleController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Request $request,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(int $id): Response
    {
        if (! ($this->currentUser->rights > 4)) {
            return new Response(
                $this->render->render('system::pages/result', [
                    'title'   => __('Delete'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ]),
                Response::HTTP_FORBIDDEN
            );
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            return new Response($this->render->render('system::pages/result', [
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => __('Articles do not exist'),
            ]));
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($article->name, $article->url);
        $this->navChain->add(__('Delete Article'));

        $this->render->addData([
            'title'      => __('Delete Article'),
            'page_title' => __('Delete Article'),
        ]);

        $deleted = false;

        if ($this->request->query->has('yes')) {
            Utils::unlinkImages($id);
            $article->delete();
            $deleted = true;
        }

        return new Response($this->render->render('library::delete_article', [
            'id'          => $id,
            'article_url' => $article->url,
            'name'        => $article->name,
            'deleted'     => $deleted,
        ]));
    }
}
