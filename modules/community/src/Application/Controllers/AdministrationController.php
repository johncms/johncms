<?php

declare(strict_types=1);

namespace Johncms\Modules\Community\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Community\Application\UseCases\ViewAdministrationUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class AdministrationController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private User $currentUser,
        private ViewAdministrationUseCase $viewAdministrationUseCase,
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

        $result = $this->viewAdministrationUseCase->execute($this->currentUser->config->kmess);
        $this->navChain->add($result->pageTitle);

        return $this->render->render(
            'community::administration',
            [
                'pagination' => $result->pagination,
                'title'      => $result->title,
                'page_title' => $result->pageTitle,
                'total'      => $result->total,
                'list'       => $result->list,
            ]
        );
    }
}
