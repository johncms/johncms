<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Johncms\Auth\Controllers;

use Johncms\Auth\Forms\LoginForm;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\RedirectResponse;
use Johncms\Http\Session;
use Johncms\Users\AuthProviders\CookiesAuthProvider;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\UserManager;
use Throwable;

class LoginController extends BaseController
{
    protected string $module_name = 'johncms/auth';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('Login'));
        $this->navChain->add(__('Login'), route('login.index'));
    }

    /**
     * @throws Throwable
     */
    public function index(Session $session): string
    {
        $registrationForm = new LoginForm();

        $data = [
            'formFields'       => $registrationForm->getFormFields(),
            'validationErrors' => $registrationForm->getValidationErrors(),
            'storeUrl'         => route('login.authorize'),
            'registrationUrl'  => route('registration.index'),
            'authError'        => $session->getFlash('authError'),
        ];

        return $this->render->render('auth::login_form', ['data' => $data]);
    }

    public function authorize(
        UserManager $userManager,
        Session $session,
        SessionAuthProvider $sessionAuthProvider,
        CookiesAuthProvider $cookiesAuthProvider
    ): RedirectResponse {
        $registrationForm = new LoginForm();
        try {
            // Validate the form
            $registrationForm->validate();
            $values = $registrationForm->getRequestValues();

            try {
                // Try to check credentials and authorize the user
                $user = $userManager->checkCredentials($values['login'], $values['password']);
                if ($values['remember']) {
                    $cookiesAuthProvider->store($user);
                }
                $sessionAuthProvider->store($user);
                return (new RedirectResponse(route('homepage.index')));
            } catch (Throwable $exception) {
                $session->flash('authError', $exception->getMessage());
                return (new RedirectResponse(route('login.index')))->withPost();
            }
        } catch (ValidationException $validationException) {
            // Redirect to the login form if the form is invalid
            return (new RedirectResponse(route('login.index')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }
}
