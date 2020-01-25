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

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\System\View\Render $view
 */

$total = $db->query('SELECT COUNT(*) FROM `users` WHERE `preg` = 1')->fetchColumn();
$req = $db->query("SELECT `id`, `name`, `sex`, `lastdate`, `datereg`, `status`, `rights`, `ip`, `browser`, `rights` FROM `users` WHERE `preg` = 1 ORDER BY `datereg` DESC LIMIT ${start}, " . $user->config->kmess);

$nav_chain->add(__('List of users'));

echo $view->render(
    'users::users',
    [
        'pagination' => $tools->displayPagination('?', $start, $total, $user->config->kmess),
        'title'      => __('List of users'),
        'page_title' => __('List of users'),
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
