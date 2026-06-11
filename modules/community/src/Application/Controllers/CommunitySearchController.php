<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Http\Pagination\PaginationGuard;
use Johncms\Modules\Community\Application\UseCases\ViewSearchUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class CommunitySearchController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private Request $request,
        private ViewSearchUseCase $viewSearchUseCase,
        private PaginationFactory $paginationFactory,
        private PaginationGuard $paginationGuard,
    ) {
        $this->controllerContext->initModule('community');
    }

    public function __invoke(): string
    {
        $communityTitle = __('Community');
        $this->navChain->add($communityTitle, '/community/');

        $config = config('johncms');
        if (! $config['active'] && ! $this->currentUser->isValid()) {
            return $this->render->render(
                'system::pages/result',
                [
                    'title'   => $communityTitle,
                    'type'    => 'alert-danger',
                    'message' => __('For registered users only'),
                ]
            );
        }

        $pageTitle = __('User Search');
        $this->navChain->add($pageTitle);

        $search = trim(rawurldecode((string) $this->request->getQuery('search', '')));
        $errors = $this->viewSearchUseCase->validate($search);
        $hasSearch = $search !== '' && $errors === [];

        $pagination = $this->paginationFactory->create($hasSearch ? $this->viewSearchUseCase->count($search) : 0);

        $redirectUrl = $this->paginationGuard->redirectUrl($pagination);
        if ($redirectUrl !== null) {
            redirect($redirectUrl);
        }

        $total = $pagination->getTotal();
        $list = $hasSearch && $total > 0
            ? $this->viewSearchUseCase->getPage($search, $pagination->getPerPage(), $pagination->getOffset())
            : [];

        $meta = new PageMeta($pageTitle, $pagination->getCurrentPage());

        return $this->render->render(
            'community::search',
            [
                'title'       => $meta->title,
                'page_title'  => $pageTitle,
                'description' => $meta->description,
                'data'        => [
                    'search_query' => $search,
                    'errors'       => $errors,
                    'total'        => $total,
                    'list'         => $list,
                    'pagination'   => $pagination->render(),
                ],
            ]
        );
    }
}
