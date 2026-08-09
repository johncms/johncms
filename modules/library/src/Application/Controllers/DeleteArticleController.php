<?php

declare(strict_types=1);

namespace Johncms\Modules\Library\Application\Controllers;

use Johncms\Modules\Library\Domain\Models\LibraryText;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Johncms\Modules\Library\Application\Services\Tree;
use Johncms\Modules\Library\Application\Services\Utils;
use Symfony\Component\HttpFoundation\Response;

final readonly class DeleteArticleController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
    ) {
    }

    public function __invoke(Request $request, int $id): ViewResponse
    {
        if (! ($this->currentUser->rights > 4)) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => __('Delete'),
                    'type'    => 'alert-danger',
                    'message' => __('Access forbidden'),
                ],
                Response::HTTP_FORBIDDEN
            );
        }

        $article = LibraryText::query()->find($id);

        if ($article === null) {
            return new ViewResponse('@theme/pages/result.twig', [
                'title'   => __('Delete'),
                'type'    => 'alert-danger',
                'message' => __('Articles do not exist'),
            ]);
        }

        $this->navChain->add(__('Library'), '/library/');
        $dirNav = new Tree($article->cat_id);
        $dirNav->processNavPanel();
        $dirNav->printNavPanel();
        $this->navChain->add($article->name, $article->url);
        $this->navChain->add(__('Delete Article'));

        $deleted = false;

        if ($request->query->has('yes')) {
            Utils::unlinkImages($id);
            $article->delete();
            $deleted = true;
        }

        return new ViewResponse('@library/public/delete-article.twig', [
            'title'       => __('Delete Article'),
            'page_title'  => __('Delete Article'),
            'id'          => $id,
            'article_url' => $article->url,
            'name'        => $article->name,
            'deleted'     => $deleted,
        ]);
    }
}
