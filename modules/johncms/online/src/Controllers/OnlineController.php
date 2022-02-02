<?php

declare(strict_types=1);

namespace Johncms\Online\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Controller\BaseController;
use Johncms\Online\Resources\UserResource;
use Johncms\Users\User;

class OnlineController extends BaseController
{
    protected string $moduleName = 'johncms/online';

    public function __construct()
    {
        parent::__construct();
        $this->metaTagManager->setAll(__('Who is online?'));
        $this->navChain->add(__('Who is online?'));
    }

    /**
     * Authorized online users
     */
    public function index(): string
    {
        $users = User::query()->with('activity')->online()->paginate();
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
        $users = User::query()->with('activity')->whereHas('activity', function (Builder $builder) {
            return $builder->where('last_visit', '<', Carbon::now()->subMinutes(5))
                ->where('last_visit', '>', Carbon::today()->subDays(3));
        })->paginate();
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

    public function guests(): string
    {
        return '';
    }

    public function ipActivity(): string
    {
        return '';
    }
}
