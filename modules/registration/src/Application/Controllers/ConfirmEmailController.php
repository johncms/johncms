<?php

declare(strict_types=1);

namespace Johncms\Modules\Registration\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ConfirmEmailController
{
    public function __construct(
        private ControllerContext $controllerContext,
    ) {
        $this->controllerContext->initModule('registration');
    }

    public function __invoke(Request $request): ViewResponse
    {
        $id = $request->queryInt('id');
        $code = $request->queryParam('code', '');

        $confirmUser = null;
        if ($id > 0 && $code !== '') {
            $confirmUser = User::query()->find($id);
            if ($confirmUser !== null && ! $confirmUser->email_confirmed && $confirmUser->confirmation_code === $code) {
                $confirmUser->email_confirmed = true;
                $confirmUser->confirmation_code = null;
                $confirmUser->save();
            }
        }

        return new ViewResponse(
            '@registration/public/email-confirmed.twig',
            [
                'title'        => __('Email confirmation'),
                'page_title'   => __('Email confirmation'),
                'confirm_user' => $confirmUser,
            ]
        );
    }
}
