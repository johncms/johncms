<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LogoutController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private Session $session,
        private NavChain $navChain,
        private User $currentUser,
    ) {
        $this->controllerContext->initModule('login');
    }

    public function __invoke(): Response
    {
        if (! $this->currentUser->isValid()) {
            return new RedirectResponse('/login/');
        }

        if ($this->request->hasBody('logout')) {
            $this->session->invalidate();
            $response = new RedirectResponse('/');
            $expire = time() - 3600;
            $response->headers->setCookie(Cookie::create('cuid', '', $expire, '/', null, false, false, false, null));
            $response->headers->setCookie(Cookie::create('cups', '', $expire, '/', null, false, false, false, null));
            return $response;
        }

        $config = config('johncms');
        $referer = $this->request->server->filter('HTTP_REFERER', $config['homeurl'], FILTER_SANITIZE_SPECIAL_CHARS);

        $this->navChain->add(__('Personal'), '/profile/account');
        $this->navChain->add(__('Logout'));

        return new Response($this->render->render('login::logout', ['referer' => $referer]));
    }
}
