<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
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

        $search = rawurldecode((string) $this->request->getQuery('search', ''));
        $result = $this->viewSearchUseCase->execute($search, $this->currentUser->config->kmess);
        $this->navChain->add($result->pageTitle);

        return $this->render->render(
            'community::community_search',
            [
                'title'      => $result->title,
                'page_title' => $result->pageTitle,
                'data'       => [
                    'search_query' => $result->searchQuery,
                    'errors'       => $result->errors,
                    'total'        => $result->total,
                    'list'         => $result->list,
                    'pagination'   => $result->pagination,
                ],
            ]
        );
    }
}
