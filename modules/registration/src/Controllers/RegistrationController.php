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
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Registration\Forms\RegistrationForm;
use Throwable;

class RegistrationController extends BaseController
{
    protected string $module_name = 'registration';

    public function __construct()
    {
        // If the user is authorized, we redirect him to the homepage
        $user = di(User::class);
        if ($user !== null) {
            redirect(route('homepage.index'));
        }
        parent::__construct();
    }

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
            'moderation'       => config('registration.moderation', false),
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
            $sessionProvider = di(SessionAuthProvider::class);
            $sessionProvider->store($user);
            return (new RedirectResponse(route('homepage.index')));
        } catch (ValidationException $validationException) {
            // Redirect to the registration form if the form is invalid
            return (new RedirectResponse(route('registration.index')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }
}
