<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

namespace Johncms\Admin\Controllers\Users;

use Illuminate\Database\Eloquent\Builder;
use Johncms\Admin\Forms\UserForm;
use Johncms\Admin\Resources\Users\UserResource;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Files\FileStorage;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Users\Role;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Psr\Http\Message\UploadedFileInterface;
use Throwable;

class UsersController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Users'));
        $roles = Role::query()->get();
        return $this->render->render('johncms/admin::users/user_list', [
            'data' => [
                'roles'         => $roles->toJson(),
                'createUserUrl' => route('admin.createUser'),
                'deleteUserUrl' => route('admin.deleteUser'),
            ],
        ]);
    }

    public function userList(Request $request): array
    {
        $name = $request->getQuery('name');
        $role = $request->getQuery('role');
        $unconfirmed = $request->getQuery('unconfirmed');
        $hasBan = $request->getQuery('hasBan');

        $users = User::query()
            ->when(! empty($name), function (Builder $builder) use ($name) {
                return $builder->where(function (Builder $builder) use ($name) {
                    return $builder->where('name', 'like', '%' . $name . '%')
                        ->orWhere('login', 'like', '%' . $name . '%')
                        ->orWhere('email', 'like', '%' . $name . '%')
                        ->orWhere('phone', 'like', '%' . $name . '%');
                });
            })
            ->when(! empty($role), function (Builder $builder) use ($role) {
                return $builder->whereHas('roles', function (Builder $builder) use ($role) {
                    return $builder->where('id', $role);
                });
            })
            ->when($unconfirmed === 'true', function (Builder $builder) {
                return $builder->unconfirmed();
            })
            ->when($hasBan === 'true', function (Builder $builder) use ($role) {
                return $builder->whereHas('bans', function (Builder $builder) {
                    return $builder->active();
                });
            })
            ->paginate();
        $resource = UserResource::createFromCollection($users);
        return $resource->toArray();
    }

    public function create(): string
    {
        $userForm = new UserForm();
        $this->metaTagManager->setAll(__('Create User'));
        return $this->render->render('johncms/admin::users/user_form', [
            'data' => [
                'formFields'       => $userForm->getFormFields(),
                'validationErrors' => $userForm->getValidationErrors(),
                'storeUrl'         => route('admin.storeUser'),
                'backUrl'          => route('admin.users'),
            ],
        ]);
    }

    public function edit(int $id): string
    {
        $user = User::query()->findOrFail($id);
        $userForm = new UserForm($user);
        $this->metaTagManager->setAll(__('Edit User'));
        return $this->render->render('johncms/admin::users/user_form', [
            'data' => [
                'formFields'       => $userForm->getFormFields(),
                'validationErrors' => $userForm->getValidationErrors(),
                'storeUrl'         => route('admin.storeUser'),
                'backUrl'          => route('admin.users'),
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(UserManager $userManager, FileStorage $fileStorage, Request $request): string | RedirectResponse
    {
        $userId = (int) $request->getPost('id', 0);
        $user = User::query()->findOrFail($userId);
        $userForm = new UserForm($user);
        try {
            // Validate the form
            $userForm->validate();
            $fields = $userForm->getRequestValues();
            if (
                is_object($fields['avatar'])
                && is_subclass_of($fields['avatar'], UploadedFileInterface::class)
                && $fields['avatar']->getError() === UPLOAD_ERR_OK
            ) {
                try {
                    $fields['avatar_id'] = $fileStorage->saveUploadedFile($fields['avatar'], 'users/avatar')->id;
                    unset($fields['avatar']);
                } catch (Throwable) {
                }
            }

            $fields['confirmed'] = true;
            $fields['email_confirmed'] = true;

            if ($userId) {
                $userManager->update($userId, $fields);
            } else {
                $userManager->create($fields);
            }

            return (new RedirectResponse(route('admin.users')));
        } catch (ValidationException $validationException) {
            // Redirect to the registration form if the form is invalid
            if (! empty($userId)) {
                $redirectUrl = route('admin.editUser', ['id' => $userId]);
            } else {
                $redirectUrl = route('admin.createUser');
            }
            return (new RedirectResponse($redirectUrl))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }

    public function delete(UserManager $userManager, Request $request): array
    {
        $userId = $request->getJson('id', 0, FILTER_VALIDATE_INT);
        return ['deleteResult' => $userManager->delete($userId)];
    }
}
