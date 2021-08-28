<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

namespace Registration\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        parent::__construct();

        $this->render->addData(
            [
                'title'       => __('Registration'),
                'page_title'  => __('Registration'),
                'keywords'    => __('Registration'),
                'description' => __('Registration'),
            ]
        );
        $this->nav_chain->add(__('Registration'), route('registration.index'));
    }

    /**
     * @throws Throwable
     */
    public function index(): string
    {
        $registrationForm = new RegistrationForm();

        $data = [
            'formFields'       => $registrationForm->getFormFields(),
            'validationErrors' => $registrationForm->getValidationErrors(),
            'storeUrl'         => route('registration.store'),
            'moderation'       => config('registration.moderation', false),
        ];

        return $this->render->render('registration::index', ['data' => $data]);
    }

    /**
     * @throws Throwable
     */
    public function store(UserRegistrationService $registrationService): string|RedirectResponse
    {
        $registrationForm = new RegistrationForm();
        try {
            // Validate the form
            $registrationForm->validate();
            $user = $registrationService->registerUser($registrationForm->getRequestValues());

            return $this->render->render('registration::registration_result', [
                'moderation'         => $registrationService->moderation(),
                'email_confirmation' => $registrationService->emailConfirmation(),
                'user'               => $user,
            ]);
        } catch (ValidationException $validationException) {
            // Redirect to the registration form if the form is invalid
            return (new RedirectResponse(route('registration.index')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }

    /**
     * @throws Throwable
     */
    public function confirmEmail(UserRegistrationService $registrationService, Request $request): string
    {
        $userId = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
        $code = (string) $request->getQuery('code', '');
        try {
            $confirmUser = (new User())->findOrFail($userId);
            $registrationService->confirmEmail($confirmUser, $code);
            // Authorize the user
            $sessionProvider = di(SessionAuthProvider::class);
            $sessionProvider->store($confirmUser);

            return $this->render->render('registration::email_confirmed', [
                'title' => __('Confirmation of registration'),
                'user'  => $confirmUser,
            ]);
        } catch (RuntimeException $exception) {
            return $this->render->render('system::pages/result', [
                'title'   => __('Confirmation of registration'),
                'type'    => 'alert-danger',
                'message' => $exception->getMessage(),
            ]);
        } catch (ModelNotFoundException) {
            return $this->render->render('system::pages/result', [
                'title'   => __('Confirmation of registration'),
                'type'    => 'alert-danger',
                'message' => __("The user wasn't found"),
            ]);
        }
    }

    /**
     * @throws Throwable
     */
    public function registrationClosed(): string
    {
        if (! config('registration.closed')) {
            redirect(route('registration.index'));
        }
        return $this->render->render('system::pages/result', [
            'type'    => 'alert-danger',
            'message' => __('Registration is temporarily closed'),
        ]);
    }
}
