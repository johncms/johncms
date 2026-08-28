<?php

declare(strict_types=1);

namespace Johncms\Modules\Auth\Application\Controllers;

use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ConfirmRegistrationEmailController
{
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
            '@auth/public/registration-email-confirmed.twig',
            [
                'title'        => __('Email confirmation'),
                'page_title'   => __('Email confirmation'),
                'confirm_user' => $confirmUser,
            ]
        );
    }
}
