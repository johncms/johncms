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

use Johncms\Admin\Resources\Users\UserResource;
use Johncms\Controller\BaseAdminController;
use Johncms\Users\User;

class UsersController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $this->metaTagManager->setAll(__('List of Users'));
        return $this->render->render('admin::users/user_list');
    }

    public function userList(): array
    {
        $users = User::query()->paginate();
        $resource = UserResource::createFromCollection($users);
        return $resource->toArray();
    }
}
