<?php

declare(strict_types=1);

namespace Johncms\Online\Controllers;

use Johncms\Controller\BaseController;
use Johncms\Online\Resources\UserResource;
use Johncms\Users\User;

class OnlineController extends BaseController
{
    protected string $moduleName = 'johncms/online';

    /**
     * Authorized online users
     */
    public function index(): string
    {
        $users = User::query()->online()->paginate();
        $userResource = UserResource::createFromCollection($users);
        return $this->render->render('online::users', [
            'data' => [
                'users'      => $userResource->getItems(),
                'pagination' => $users->render(),
                'total'      => $users->total(),
                'filters'    => [],
            ],
        ]);
    }

    public function history(): string
    {
        return '';
    }

    public function guests(): string
    {
        return '';
    }

    public function ipActivity(): string
    {
        return '';
    }
}
