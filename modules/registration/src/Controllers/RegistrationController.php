<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Registration\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\RedirectResponse;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\UserManager;
use Registration\Forms\RegistrationForm;
use Throwable;

class RegistrationController extends BaseController
{
    protected string $module_name = 'registration';

    /**
     * @throws Throwable
     */
    public function index(): string
    {
        $this->render->addData(
            [
                'title'       => __('Registration'),
                'page_title'  => __('Registration'),
                'keywords'    => __('Registration'),
                'description' => __('Registration'),
            ]
        );
        $this->nav_chain->add(__('Registration'), route('registration.index'));

        $registrationForm = new RegistrationForm();

        $data = [
            'formFields'       => $registrationForm->getFormFields(),
            'validationErrors' => $registrationForm->getValidationErrors(),
            'storeUrl'         => route('registration.store'),
        ];

        return $this->render->render('registration::index', ['data' => $data]);
    }

    public function store(UserManager $userManager): RedirectResponse
    {
        $registrationForm = new RegistrationForm();
        try {
            // Validate the form
            $registrationForm->validate();
            // Create user
            $user = $userManager->create($registrationForm->getRequestValues());
            // Authorize the user
            $session_provider = di(SessionAuthProvider::class);
            $session_provider->store($user);
            return (new RedirectResponse(route('homepage.index')));
        } catch (ValidationException $validationException) {
            // Redirect to the registration form if the form is invalid
            return (new RedirectResponse(route('registration.index')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }
}
