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
use Johncms\Settings\SiteSettings;
use Johncms\System\Legacy\Tools;
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
        $guests = GuestSession::query()->online()->paginate();
        $userResource = GuestResource::createFromCollection($guests);
        return $this->render->render('online::users', [
            'data' => [
                'users'      => $userResource->getItems(),
                'pagination' => $guests->render(),
                'total'      => $guests->total(),
                'filters'    => [],
            ],
        ]);
    }

    public function ipActivity(SiteSettings $siteSettings, ?User $user, IpLogger $ipLogger, Tools $tools, Request $request): string
    {
        $ip_array = array_count_values($ipLogger->getIpLog());
        $total = count($ip_array);
        $page = $request->getQuery('page', 1, FILTER_VALIDATE_INT);
        $start = $page * $siteSettings->getPerPage() - $siteSettings->getPerPage();

        if ($start >= $total) {
            // Исправляем запрос на несуществующую страницу
            $start = max(0, $total - (($total % $siteSettings->getPerPage()) == 0 ? $siteSettings->getPerPage() : ($total % $siteSettings->getPerPage())));
        }

        $end = $start + $siteSettings->getPerPage();

        if ($end > $total) {
            $end = $total;
        }

        arsort($ip_array);
        $i = 0;

        foreach ($ip_array as $key => $val) {
            $ip_list[$i] = [$key => $val];
            ++$i;
        }
        $items = [];
        if ($total && $user?->hasAnyRole()) {
            for ($i = $start; $i < $end; $i++) {
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

        $data['pagination'] = $tools->displayPagination('?', $start, $total, $siteSettings->getPerPage());
        $data['total'] = $total;
        $data['items'] = $items ?? [];

        return $this->render->render('online::ip', ['data' => $data]);
    }
}
