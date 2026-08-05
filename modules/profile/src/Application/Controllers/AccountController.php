<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\UseCases\GetAccountUseCase;
use Johncms\NavChain;

final readonly class AccountController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private GetAccountUseCase $getAccountUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(): ViewResponse
    {
        $account = $this->getAccountUseCase->execute();

        $title = __('My Account');
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/account.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'account'    => $account,
            ]
        );
    }
}
