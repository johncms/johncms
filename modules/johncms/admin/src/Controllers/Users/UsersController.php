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
use Johncms\Admin\Forms\CreateUserForm;
use Johncms\Admin\Resources\Users\UserResource;
use Johncms\Controller\BaseAdminController;
use Johncms\Exceptions\ValidationException;
use Johncms\Http\Request;
use Johncms\Http\Response\RedirectResponse;
use Johncms\Users\Role;
use Johncms\Users\User;
use Johncms\Users\UserManager;
use Throwable;

class UsersController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Users'));
        $roles = Role::query()->get();
        return $this->render->render('admin::users/user_list', [
            'data' => [
                'roles'         => $roles->toJson(),
                'createUserUrl' => route('admin.createUser'),
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

    public function create(CreateUserForm $createUserForm): string
    {
        $this->metaTagManager->setAll(__('Create User'));
        return $this->render->render('admin::users/create_user', [
            'data' => [
                'formFields'       => $createUserForm->getFormFields(),
                'validationErrors' => $createUserForm->getValidationErrors(),
                'storeUrl'         => route('admin.storeUser'),
                'backUrl'          => route('admin.users'),
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function store(UserManager $userManager, CreateUserForm $createUserForm): string | RedirectResponse
    {
        try {
            // Validate the form
            $createUserForm->validate();
            $fields = $createUserForm->getRequestValues();
            $fields['confirmed'] = true;
            $fields['email_confirmed'] = true;
            $userManager->create($fields);
            return (new RedirectResponse(route('admin.users')));
        } catch (ValidationException $validationException) {
            // Redirect to the registration form if the form is invalid
            return (new RedirectResponse(route('admin.createUser')))
                ->withPost()
                ->withValidationErrors($validationException->getErrors());
        }
    }
}
