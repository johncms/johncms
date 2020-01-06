<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\Counters $counters
 * @var PDO $db
 * @var Johncms\System\View\Render $view
 */

$counters = di('counters');

$count_adm = $db->query('SELECT COUNT(*) FROM `users` WHERE `rights` > 0')->fetchColumn();
$birthDays = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j') . "' AND `monthb` = '" . date('n') . "' AND `preg` = '1'")->fetchColumn();

$data = [
    'counters' => [
        'usersCount' => $counters->users(),
        'adminCount' => $count_adm,
        'birthDays'  => $birthDays,
    ],
];

echo $view->render(
    'users::index',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
