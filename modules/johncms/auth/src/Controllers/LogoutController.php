<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Auth\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Http\RedirectResponse;
use Johncms\Users\AuthProviders\CookiesAuthProvider;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Throwable;

class LogoutController extends BaseController
{
    protected string $module_name = 'johncms/auth';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('Logout'));
        $this->navChain->add(__('Logout'), route('logout.index'));
    }

    /**
     * @throws Throwable
     */
    public function index(): string
    {
        $data = [
            'confirmUrl' => route('logout.confirm'),
        ];
        return $this->render->render('auth::logout_form', ['data' => $data]);
    }

    public function confirm(SessionAuthProvider $sessionAuthProvider, CookiesAuthProvider $cookiesAuthProvider): RedirectResponse
    {
        $sessionAuthProvider->forget();
        $cookiesAuthProvider->forget();
        return (new RedirectResponse(route('homepage.index')));
    }
}
