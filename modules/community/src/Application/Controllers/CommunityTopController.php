<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Community\Application\UseCases\ViewTopUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class CommunityTopController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private Request $request,
        private ViewTopUseCase $viewTopUseCase,
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

        $route = $this->request->attributes->all();
        $mod = (string) ($route['mod'] ?? '');
        $result = $this->viewTopUseCase->execute($mod);

        $this->navChain->add($result->pageTitle);

        return $this->render->render(
            'community::top',
            [
                'title'      => $result->title,
                'page_title' => $result->pageTitle,
                'data'       => [
                    'total'      => $result->total,
                    'active_tab' => $result->activeTab,
                    'tabs'       => $result->tabs,
                    'list'       => $result->list,
                ],
            ]
        );
    }
}
