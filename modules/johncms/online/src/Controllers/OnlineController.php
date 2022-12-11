<?php

declare(strict_types=1);

namespace Johncms\Online\Controllers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Johncms\Controller\BaseController;
use Johncms\Http\IpLogger;
use Johncms\Http\Request;
use Johncms\Online\Models\GuestSession;
use Johncms\Online\Resources\GuestResource;
use Johncms\Online\Resources\UserResource;
use Johncms\Users\User;
use Johncms\Utility\Pagination;

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
        return $this->render->render('johncms/online::users', [
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
        return $this->render->render('johncms/online::users', [
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
        $guests = GuestSession::query()->online()->paginate();
        $userResource = GuestResource::createFromCollection($guests);
        return $this->render->render('johncms/online::users', [
            'data' => [
                'users'      => $userResource->getItems(),
                'pagination' => $guests->render(),
                'total'      => $guests->total(),
                'filters'    => [],
            ],
        ]);
    }

    public function ipActivity(?User $user, IpLogger $ipLogger, Request $request): string
    {
        $ip_array = array_count_values($ipLogger->getIpLog());
        arsort($ip_array);
        $total = count($ip_array);

        $pagination = new Pagination($total);

        $i = 0;
        foreach ($ip_array as $key => $val) {
            $ip_list[$i] = [$key => $val];
            ++$i;
        }
        $items = [];
        if ($total && $user?->hasAnyRole()) {
            for ($i = $pagination->getOffset(); $i < $pagination->getLimit(); $i++) {
                $ipLong = key($ip_list[$i]);
                $ip = long2ip((int) $ipLong);

                $items[] = [
                    'ip'              => $ip,
                    'search_ip'       => '/admin/search_ip/?ip=' . $ip,
                    'whois_ip'        => '/admin/ip_whois/?ip=' . $ip,
                    'current_user_ip' => ($ip === $request->getIp()),
                    'count'           => $ip_list[$i][$ipLong],
                ];
            }
        }

        $data['pagination'] = $pagination->render();
        $data['total'] = $total;
        $data['items'] = $items ?? [];

        return $this->render->render('johncms/online::ip', ['data' => $data]);
    }
}
