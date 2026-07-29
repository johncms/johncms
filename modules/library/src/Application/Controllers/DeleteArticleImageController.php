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

final readonly class DeleteArticleImageController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('library');
    }

    public function __invoke(Request $request, int $id): Response
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

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article?->cat_id ?? 0);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();

        $articleName = $article !== null ? $article->name : '';
        $this->navChain->add($articleName, '/library/?id=' . $id);
        $this->navChain->add(__('Delete Cover Image'));

        $this->render->addData([
            'title'      => __('Delete Cover Image'),
            'page_title' => __('Delete Cover Image'),
        ]);

        $deleted = false;

        if ($request->query->has('yes')) {
            Utils::unlinkImages($id);
            $deleted = true;
        }

        return new Response($this->render->render('library::delete_article_image', [
            'id'      => $id,
            'name'    => $article?->name ?? '',
            'deleted' => $deleted,
        ]));
    }
}
