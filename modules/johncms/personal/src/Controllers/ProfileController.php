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
use Johncms\Personal\Forms\ProfileForm;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\UserManager;
use Throwable;

class ProfileController extends BaseController
{
    protected string $moduleName = 'johncms/personal';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('User Profile'));
        $this->navChain->add(__('Personal account'), route('personal.index'));
        $this->navChain->add(__('Profile'), route('personal.profile'));
    }

    /**
     * @throws Throwable
     */
    public function index(): string
    {
        return $this->render->render('personal::profile/index', [
            'data' => [
                'backButton' => route('personal.index'),
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function edit(int $id, Session $session): string
    {
        $profileForm = new ProfileForm($id);
        return $this->render->render('personal::profile/edit', [
            'data' => [
                'formFields'       => $profileForm->getFormFields(),
                'validationErrors' => $profileForm->getValidationErrors(),
                'success'          => $session->getFlash('success'),
                'errors'           => $session->getFlash('errors'),
                'storeUrl'         => route('personal.profile.store', ['id' => $id]),
                'backButton'       => route('personal.index'),
            ],
        ]);
    }

    public function store(int $id, UserManager $userManager, Session $session): RedirectResponse
    {
        $profileForm = new ProfileForm($id);
        try {
            // Validate the form
            $profileForm->validate();
            try {
                $userManager->update($id, $profileForm->getRequestValues());
                $session->flash('success', __('Profile changed successfully'));
                return new RedirectResponse(route('personal.profile.edit', ['id' => $id]));
            } catch (RuntimeException $exception) {
                $session->flash('errors', $exception->getMessage());
                return (new RedirectResponse(route('personal.profile.edit', ['id' => $id])))->withPost();
            }
        } catch (ValidationException $validationException) {
            // Redirect if the form is invalid
            return (new RedirectResponse(route('personal.profile.edit', ['id' => $id])))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }
}
