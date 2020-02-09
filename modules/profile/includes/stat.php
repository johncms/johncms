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

$user_data = (array) $foundUser;

$title = $user_data['name'] . ': ' . __('Statistic');

$nav_chain->add($user_data['name'], '?user=' . $user_data['id']);
$nav_chain->add(__('Statistic'));

$user_data['total_on_site'] = $tools->timecount((int) $user_data['total_on_site']);
$user_data['last_visit'] = time() > $user_data['lastdate'] + 300 ? date('d.m.Y (H:i)', $user_data['lastdate']) : false;

$data = [
    'user' => $user_data,
];

echo $view->render(
    'profile::stat',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
