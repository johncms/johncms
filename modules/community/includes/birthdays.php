<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\UserProperties;

defined('_IN_JOHNCMS') || die('Error: restricted access');

$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();
$req = $db->query("SELECT * FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1' LIMIT ${start}, " . $user->config->kmess);

$nav_chain->add(__('Birthdays'));

echo $view->render(
    'users::users',
    [
        'pagination' => $tools->displayPagination('?', $start, $total, $user->config->kmess),
        'title'      => __('Birthdays'),
        'page_title' => __('Birthdays'),
        'total'      => $total,
        'list'       =>
            static function () use ($req, $user) {
                while ($res = $req->fetch()) {
                    $res['user_id'] = $res['id'];
                    $user_properties = new UserProperties();
                    $user_data = $user_properties->getFromArray($res);
                    $res = array_merge($res, $user_data);
                    yield $res;
                }
            },
    ]
);
