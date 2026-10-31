<?php

declare(strict_types=1);

namespace Johncms\Modules\Admin\Application\Controllers;

use Johncms\Auth\Session\SignInManager;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

final readonly class LogoutController
{
    public function __construct(private SignInManager $signInManager)
    {
    }

    public function confirm(Request $request): ViewResponse
    {
        return new ViewResponse(
            $request->isXmlHttpRequest() ? '@admin/logout-dialog.twig' : '@admin/logout.twig',
            ['title' => d__('system', 'Exit')],
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->signInManager->signOut($request);

        return new RedirectResponse('/admin/login');
    }
}
