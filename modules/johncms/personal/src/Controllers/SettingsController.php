<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Personal\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;
use Johncms\Personal\Forms\SettingsForm;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class SettingsController extends BaseController
{
    protected string $moduleName = 'johncms/personal';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('User Profile'));
        $this->navChain->add(__('Personal account'), route('personal.index'));
        $this->navChain->add(__('Settings'), route('personal.settings'));
    }

    /**
     * @throws Throwable
     */
    public function index(SettingsForm $settingsForm, Session $session): string|ResponseInterface
    {
        return $this->render->render('personal::settings', [
            'data' => [
                'formFields'       => $settingsForm->getFormFields(),
                'validationErrors' => $settingsForm->getValidationErrors(),
                'success'          => $session->getFlash('success'),
                'errors'           => $session->getFlash('errors'),
                'storeUrl'         => route('personal.settings.store'),
                'backButton'       => route('personal.index'),
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(SettingsForm $settingsForm, User $user, UserManager $userManager, Session $session): ResponseInterface|RedirectResponse
    {
        try {
            // Validate the form
            $settingsForm->validate();
            try {
                $userManager->update($user->id, ['settings' => $settingsForm->getRequestValues()]);
                $session->flash('success', __('Settings saved successfully'));
                return new RedirectResponse(route('personal.settings'));
            } catch (RuntimeException $exception) {
                $session->flash('errors', $exception->getMessage());
                return (new RedirectResponse(route('personal.settings')))->withPost();
            }
        } catch (ValidationException $validationException) {
            // Redirect if the form is invalid
            return (new RedirectResponse(route('personal.settings')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }
}
