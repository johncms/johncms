<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class LogoutController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('login');
    }

    public function __invoke(): string
    {
        if (! $this->currentUser->isValid()) {
            header('Location: /login/');
            exit;
        }

        if ($this->request->getPost('logout') !== null) {
            $_SESSION = [];
            setcookie('cuid', '', time() - 3600, '/');
            setcookie('cups', '', time() - 3600, '/');
            header('Location: /');
            exit;
        }

        $config = config('johncms');
        $referer = $this->request->getServer('HTTP_REFERER', $config['homeurl'], FILTER_SANITIZE_SPECIAL_CHARS);

        $this->navChain->add(__('Personal'), '/profile/account');
        $this->navChain->add(__('Logout'));

        return $this->render->render('login::logout', ['referer' => $referer]);
    }
}
