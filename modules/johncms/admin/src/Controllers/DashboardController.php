<?php

declare(strict_types=1);

namespace Johncms\Admin\Controllers;

use Johncms\Controller\BaseAdminController;

class DashboardController extends BaseAdminController
{
    protected string $moduleName = 'johncms/admin';

    public function index(): string
    {
        $data = [];
        $counters = di('counters');

        $data['last_day_users'] = 0; //$db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 86400))->fetchColumn();
        $data['forum_messages'] = 0; //$db->query('SELECT COUNT(*) FROM `forum_messages` WHERE `date` > ' . (time() - 86400))->fetchColumn();
        $data['registered_users'] = $counters->usersCounters()['new'];

        return $this->render->render('johncms/admin::index', ['data' => $data]);
    }
}
