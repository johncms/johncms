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

use GuzzleHttp\Psr7\UploadedFile;
use Johncms\Controller\BaseController;
use Johncms\Exceptions\PageNotFoundException;
use Johncms\Exceptions\ValidationException;
use Johncms\Files\FileInfo;
use Johncms\Files\FileStorage;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Http\Session;
use Johncms\Personal\Forms\ProfileForm;
use Johncms\Users\Exceptions\RuntimeException;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface;
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
    public function index(?User $user, int $id = 0): string|ResponseInterface
    {
        $userData = $user;
        if ($id && $id !== $user?->id) {
            $userData = User::query()->find($id);
            if ($userData === null) {
                throw new PageNotFoundException(__('The user was not found'));
            }
        } elseif (! $id) {
            $id = $user?->id;
        }

        return $this->render->render('personal::profile/index', [
            'data' => [
                'canEdit'        => ($id === $user?->id),
                'editProfileUrl' => route('personal.profile.edit', ['id' => $id]),
                'userData'       => $userData,
                'backButton'     => route('personal.index'),
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function edit(?User $user, int $id, Session $session): string|ResponseInterface
    {
        if (! $user || $user->id !== $id) {
            return status_page(403);
        }
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

    /**
     * @throws Throwable
     */
    public function store(?User $user, int $id, UserManager $userManager, Session $session): ResponseInterface|RedirectResponse
    {
        if (! $user || $user->id !== $id) {
            return status_page(403);
        }
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

    public function avatarUpload(Request $request, User $user, UserManager $userManager, FileStorage $fileStorage): array
    {
        try {
            /** @var UploadedFile[] $files */
            $files = $request->getUploadedFiles();
            $file_info = new FileInfo($files['avatar']->getClientFilename());
            if (! $file_info->isImage()) {
                return [
                    'error' => [
                        'message' => __('Only images are allowed'),
                    ],
                ];
            }

            $file = $fileStorage->saveFromRequest('avatar', 'users/avatar');
            $userManager->update($user->id, ['avatar_id' => $file->id]);
            return [
                'id'       => $file->id,
                'name'     => $file->name,
                'uploaded' => 1,
                'url'      => $file->url,
            ];
        } catch (Throwable $e) {
            return [
                'error' => [
                    'message' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * @throws FilesystemException
     */
    public function avatarDelete(User $user, UserManager $userManager): RedirectResponse
    {
        $userManager->update($user->id, ['avatar_id' => null]);
        return new RedirectResponse(route('personal.profile'));
    }
}
