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


$title = htmlspecialchars($user_data->name) . ': ' . __('Statistic');

$nav_chain->add(htmlspecialchars($user_data->name), '?user=' . $user_data->id);
$nav_chain->add(__('Statistic'));

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
