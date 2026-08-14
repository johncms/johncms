<?php

declare(strict_types=1);

namespace Johncms\Modules\Login\Application\Controllers;

use Johncms\Auth\Session\SignInManager;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class LogoutController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private SignInManager $signInManager,
    ) {
    }

    public function __invoke(Request $request): RedirectResponse|ViewResponse
    {
        if (! $this->currentUser->isValid()) {
            return new RedirectResponse('/login/');
        }

        if ($request->hasBody('logout')) {
            // Closes the session on the server, not just in the browser: a cookie that was
            // copied elsewhere stops working too, which the old cookie pair could never do.
            $this->signInManager->signOut($request);

            return new RedirectResponse('/');
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
