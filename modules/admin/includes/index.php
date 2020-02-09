<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

$data = [];
$counters = di('counters');

$data['last_day_users'] = $db->query('SELECT COUNT(*) FROM `users` WHERE `lastdate` > ' . (time() - 86400))->fetchColumn();
$data['forum_messages'] = $db->query('SELECT COUNT(*) FROM `forum_messages` WHERE `date` > ' . (time() - 86400))->fetchColumn();
$data['registered_users'] = $counters->usersCounters()['new'];

echo $view->render('admin::index', ['data' => $data]);
