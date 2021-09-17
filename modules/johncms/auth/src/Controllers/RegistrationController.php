<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Auth\Controllers;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Auth\Forms\RegistrationForm;
use Johncms\Auth\Services\UserRegistrationService;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\RedirectResponse;
use Johncms\Http\Request;
use Johncms\Users\AuthProviders\SessionAuthProvider;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\User;
use Throwable;

class RegistrationController extends BaseController
{
    protected string $module_name = 'johncms/auth';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('Registration'));
        $this->navChain->add(__('Registration'), route('registration.index'));
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
            'tosUrl'           => config('johncms.terms_of_service_url', ''),
            'privacyUrl'       => config('johncms.privacy_policy_url', ''),
            'cookieUrl'        => config('johncms.cookie_policy_url', ''),
        ];

        return $this->render->render('auth::index', ['data' => $data]);
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

            return $this->render->render('auth::registration_result', [
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

        $this->metaTagManager->setAll(__('Confirmation of registration'));

        try {
            $confirmUser = (new User())->findOrFail($userId);
            $registrationService->confirmEmail($confirmUser, $code);
            // Authorize the user
            $sessionProvider = di(SessionAuthProvider::class);
            $sessionProvider->store($confirmUser);

            return $this->render->render('registration::email_confirmed', ['user' => $confirmUser]);
        } catch (RuntimeException $exception) {
            return $this->render->render('system::pages/result', [
                'type'    => 'alert-danger',
                'message' => $exception->getMessage(),
            ]);
        } catch (ModelNotFoundException) {
            return $this->render->render('system::pages/result', [
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
