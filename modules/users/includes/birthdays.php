<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

$total = $db->query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1'")->fetchColumn();
$req = $db->query("SELECT * FROM `users` WHERE `dayb` = '" . date('j', time()) . "' AND `monthb` = '" . date('n', time()) . "' AND `preg` = '1' LIMIT ${start}, " . $user->config->kmess);

echo $view->render('users::users', [
    'pagination' => $tools->displayPagination('?act=users&amp;', $start, $total, $user->config->kmess),
    'title'      => _t('Birthdays'),
    'total'      => $total,
    'list'       =>
        function () use ($req) {
            while ($res = $req->fetch()) {
                yield $res;
            }
        },
]);
