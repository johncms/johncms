<?php

declare(strict_types=1);

namespace Johncms\Modules\Profile\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Profile\Application\UseCases\ConfirmNewEmailUseCase;
use Johncms\NavChain;
use Johncms\System\View\Render;

final readonly class ConfirmNewEmailController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private ConfirmNewEmailUseCase $confirmNewEmailUseCase,
    ) {
        $this->controllerContext->initModule('profile');
    }

    public function __invoke(int $id, string $code): string
    {
        $confirmed = $this->confirmNewEmailUseCase->run($id, $code);

        $title = __('Email confirmation');
        $this->navChain->add($title);

        $this->render->addData([
            'title'      => $title,
            'page_title' => $title,
        ]);

        return $this->render->render(
            'profile::confirm_new_email',
            [
                'confirmed' => $confirmed,
            ]
        );
    }
}
