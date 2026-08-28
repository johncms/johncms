<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\Controllers;

use Johncms\Http\View\ViewResponse;
use Johncms\Modules\Auth\Application\UseCases\ConfirmEmailChangeUseCase;
use Johncms\NavChain;

final readonly class ConfirmEmailChangeController
{
    public function __construct(
        private NavChain $navChain,
        private ConfirmEmailChangeUseCase $confirmEmailChange,
    ) {
    }

    public function __invoke(int $id, string $code): ViewResponse
    {
        $confirmed = $this->confirmEmailChange->run($id, $code);

        $title = __('Email confirmation');
        $this->navChain->add($title);

        return new ViewResponse(
            '@auth/public/email-change-confirmed.twig',
            [
                'title'      => $title,
                'page_title' => $title,
                'confirmed'  => $confirmed,
            ]
        );
    }
}
