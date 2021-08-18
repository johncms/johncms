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
use Johncms\Http\Request;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\User;
use Registration\Forms\RegistrationForm;
use Registration\Services\UserRegistrationService;
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

    public function store(UserRegistrationService $registrationService): RedirectResponse
    {
        $registrationForm = new RegistrationForm();
        try {
            // Validate the form
            $registrationForm->validate();
            $registrationService->registerUser($registrationForm->getRequestValues());

            return (new RedirectResponse(route('homepage.index')));
        } catch (ValidationException $validationException) {
            // Redirect to the registration form if the form is invalid
            return (new RedirectResponse(route('registration.index')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }

    public function confirmEmail(UserRegistrationService $registrationService, Request $request): string|RedirectResponse
    {
        $userId = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $code = (string) $request->getQuery('code', '');
        $this->nav_chain->add(__('Registration'), route('registration.index'));
        try {
            $confirmUser = (new User())->find($userId);
            $registrationService->confirmEmail($confirmUser, $code);
            // Authorize the user
            $sessionProvider = di(SessionAuthProvider::class);
            $sessionProvider->store($confirmUser);
            return (new RedirectResponse(route('homepage.index')));
        } catch (RuntimeException $exception) {
            return $this->render->render('system::pages/result', [
                'title'   => __('Confirmation of registration'),
                'type'    => 'alert-danger',
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
