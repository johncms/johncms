<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\UseCases\GetAccountUseCase;
use Johncms\NavChain;

final readonly class AccountController
{
    public function __construct(
        private NavChain $navChain,
        private GetAccountUseCase $getAccountUseCase,
    ) {
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
