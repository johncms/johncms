<?php

declare(strict_types=1);

namespace Johncms\Modules\Online\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\PageMeta;
use Johncms\Modules\Online\Application\FiltersBuilder;
use Johncms\NavChain;
use Johncms\System\Http\Environment;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class IpController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private Environment $env,
        private Request $request,
        private FiltersBuilder $filtersBuilder,
    ) {
        $this->controllerContext->initModule('online');
    }

    public function __invoke(): string
    {
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $pageTitle = __('IP Activity');
        $meta = new PageMeta($pageTitle . ' — ' . __('Online'), $page);

        $this->navChain->add(__('Online'), '/online/');

        $this->render->addData([
            'title'       => $meta->title,
            'page_title'  => $pageTitle,
            'description' => $meta->description,
        ]);

        $filters = $this->filtersBuilder->build('ip');

        $ipArray = array_count_values($this->env->getIpLog());
        $total = count($ipArray);
        $kmess = $this->currentUser->config->kmess;
        $start = $page * $kmess - $kmess;

        if ($start >= $total && $total > 0) {
            $start = max(0, $total - (($total % $kmess) === 0 ? $kmess : ($total % $kmess)));
        }

        $end = min($start + $kmess, $total);

        arsort($ipArray);
        $items = [];

        if ($total) {
            $currentIp = $this->env->getIp();
            $ipList = array_slice($ipArray, $start, $end - $start, true);

            foreach ($ipList as $ipLong => $count) {
                $ip = long2ip((int) $ipLong);
                $items[] = [
                    'ip'              => $ip,
                    'search_ip'       => '/admin/ip-search?ip=' . $ip,
                    'whois_ip'        => '/admin/ip-whois?ip=' . $ip,
                    'current_user_ip' => ((string) $ipLong === (string) $currentIp),
                    'count'           => $count,
                ];
            }
        }

        return $this->render->render('online::ip', [
            'data' => [
                'filters'    => $filters,
                'pagination' => $total > $kmess ? $this->tools->displayPagination('?', $start, $total, $kmess) : '',
                'total'      => $total,
                'items'      => $items,
            ],
        ]);
    }
}
