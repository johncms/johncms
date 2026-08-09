<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class LogoutController
{
    public function __construct(
        private Session $session,
        private NavChain $navChain,
        private User $currentUser,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse|ViewResponse
    {
        if (! $this->currentUser->isValid()) {
            return new RedirectResponse('/login/');
        }

        if ($request->hasBody('logout')) {
            $this->session->invalidate();
            $response = new RedirectResponse('/');
            $expire = time() - 3600;
            $response->headers->setCookie(Cookie::create('cuid', '', $expire, '/', null, false, false, false, null));
            $response->headers->setCookie(Cookie::create('cups', '', $expire, '/', null, false, false, false, null));
            return $response;
        }

        $referer = $request->server->getString('HTTP_REFERER', (string) config('johncms.homeurl'));

        $this->navChain->add(__('Personal'), '/profile/account');
        $this->navChain->add(__('Logout'));

        return new ViewResponse(
            '@login/public/logout.twig',
            [
                'title'      => __('Logout'),
                'page_title' => __('Logout'),
                'referer'    => $referer,
            ]
        );
    }
}
