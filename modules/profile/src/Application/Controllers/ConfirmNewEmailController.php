<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Profile\Application\UseCases\ConfirmNewEmailUseCase;
use Johncms\NavChain;

final readonly class ConfirmNewEmailController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private NavChain $navChain,
        private ConfirmNewEmailUseCase $confirmNewEmailUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id, string $code): ViewResponse
    {
        $confirmed = $this->confirmNewEmailUseCase->run($id, $code);

        $title = __('Email confirmation');
        $this->navChain->add($title);

        return new ViewResponse(
            '@profile/public/confirm-new-email.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'confirmed'  => $confirmed,
            ]
        );
    }
}
