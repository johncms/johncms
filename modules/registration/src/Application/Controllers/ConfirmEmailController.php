<?php

declare(strict_types=1);

namespace Johncms\Modules\Registration\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ConfirmEmailController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
    ) {
        $this->controllerContext->initModule('registration');
    }

    public function __invoke(): string
    {
        $id = $this->request->queryInt('id');
        $code = $this->request->queryParam('code', '');

        $confirmUser = null;
        if ($id > 0 && $code !== '') {
            $confirmUser = User::query()->find($id);
            if ($confirmUser !== null && ! $confirmUser->email_confirmed && $confirmUser->confirmation_code === $code) {
                $confirmUser->email_confirmed = true;
                $confirmUser->confirmation_code = null;
                $confirmUser->save();
            }
        }

        return $this->render->render('registration::email_confirmed', ['confirm_user' => $confirmUser]);
    }
}
