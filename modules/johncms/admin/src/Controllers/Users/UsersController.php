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
use Johncms\Admin\Resources\Users\UserResource;
use Johncms\Controller\BaseAdminController;
use Johncms\Http\Request;
use Johncms\Users\Role;
use Johncms\Users\User;

class UsersController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Users'));
        $roles = Role::query()->get();
        return $this->render->render('admin::users/user_list', ['roles' => $roles->toJson()]);
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
}
