<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Modules\Community\Application\UseCases\ViewTopUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;

final readonly class CommunityTopController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private ViewTopUseCase $viewTopUseCase,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        $communityTitle = __('Community');
        $this->navChain->add($communityTitle, '/community/');

        $config = config('johncms');
        if (! $config['active'] && ! $this->currentUser->isValid()) {
            return new ViewResponse(
                '@theme/pages/result.twig',
                [
                    'title'   => $communityTitle,
                    'type'    => 'alert-danger',
                    'message' => __('For registered users only'),
                ]
            );
        }

        $route = $request->attributes->all();
        $mod = (string) ($route['mod'] ?? '');
        $result = $this->viewTopUseCase->execute($mod);

        $this->navChain->add($result->pageTitle);

        return new ViewResponse(
            '@community/public/top.twig',
            [
                'title'      => $result->title,
                'page_title' => $result->pageTitle,
                'total'      => $result->total,
                'active_tab' => $result->activeTab,
                'tabs'       => $result->tabs,
                'list'       => $result->list,
            ]
        );
    }
}
