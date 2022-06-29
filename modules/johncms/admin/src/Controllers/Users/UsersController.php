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
use Johncms\Users\User;

class UsersController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Users'));
        return $this->render->render('admin::users/user_list');
    }

    public function userList(Request $request): array
    {
        $name = $request->getQuery('name');
        $users = User::query()
            ->when(! empty($name), function (Builder $builder) use ($name) {
                return $builder->where('name', 'like', '%' . $name . '%')
                    ->orWhere('login', 'like', '%' . $name . '%')
                    ->orWhere('email', 'like', '%' . $name . '%')
                    ->orWhere('phone', 'like', '%' . $name . '%');
            })
            ->paginate();
        $resource = UserResource::createFromCollection($users);
        return $resource->toArray();
    }
}
