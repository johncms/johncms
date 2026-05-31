<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\UseCases\GetAccountUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class AccountController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private GetAccountUseCase $getAccountUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(): string
    {
        $account = $this->getAccountUseCase->execute();

        $title = __('My Account');
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::account',
            [
                'account' => $account,
            ]
        );
    }
}
