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

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$title = __('Registration confirmation');
$nav_chain->add($title);

$data = [];

switch ($mod) {
    case 'approve':
        // Подтверждаем регистрацию выбранного пользователя
        if (! $id) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('Wrong data'),
                ]
            );
            exit;
        }

        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($user->name) . ' WHERE `id` = ' . $id);
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Registration is confirmed'),
                'back_url'      => '/admin/reg/',
                'back_url_name' => __('Continue'),
            ]
        );
        break;

    case 'massapprove':
        // Подтверждение всех регистраций
        $db->exec('UPDATE `users` SET `preg` = 1, `regadm` = ' . $db->quote($user->name) . ' WHERE `preg` = 0');
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('Registration is confirmed'),
                'back_url'      => '/admin/reg/',
                'back_url_name' => __('Continue'),
            ]
        );
        break;

    case 'del':
        // Удаляем регистрацию выбранного пользователя
        if (! $id) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('Wrong data'),
                ]
            );
            exit;
        }

        $req = $db->query("SELECT `id` FROM `users` WHERE `id` = '${id}' AND `preg` = '0'");
        if ($req->rowCount()) {
            $db->exec("DELETE FROM `users` WHERE `id` = '${id}'");
            $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '${id}' LIMIT 1");
        }
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('User deleted'),
                'back_url'      => '/admin/reg/',
                'back_url_name' => __('Continue'),
            ]
        );
        break;

    case 'massdel':
        $db->exec("DELETE FROM `users` WHERE `preg` = '0'");
        $db->query('OPTIMIZE TABLE `cms_users_iphistory` , `users`');
        echo $view->render(
            'system::pages/result',
            [
                'title'         => $title,
                'type'          => 'alert-success',
                'message'       => __('All unconfirmed registrations were removed'),
                'back_url'      => '/admin/reg/',
                'back_url_name' => __('Continue'),
            ]
        );
        break;

    case 'delip':
        // Удаляем все регистрации с заданным адресом IP
        $ip = isset($_GET['ip']) ? (int) ($_GET['ip']) : false;

        if ($ip) {
            $req = $db->query("SELECT `id` FROM `users` WHERE `preg` = '0' AND `ip` = '${ip}'");

            while ($res = $req->fetch()) {
                $db->exec("DELETE FROM `cms_users_iphistory` WHERE `user_id` = '" . $res['id'] . "'");
            }

            $db->exec("DELETE FROM `users` WHERE `preg` = '0' AND `ip` = '${ip}'");
            $db->query('OPTIMIZE TABLE `cms_users_iphistory` , `users`');
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-success',
                    'message'       => __('All unconfirmed registrations with selected IP were deleted'),
                    'back_url'      => '/admin/reg/',
                    'back_url_name' => __('Continue'),
                ]
            );
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'   => $title,
                    'type'    => 'alert-danger',
                    'message' => __('Wrong data'),
                ]
            );
            exit;
        }
        break;

    default:
        // Выводим список пользователей, ожидающих подтверждения регистрации
        $total = $db->query("SELECT COUNT(*) FROM `users` WHERE `preg` = '0'")->fetchColumn();

        if ($total) {
            $req = $db->query("SELECT * FROM `users` WHERE `preg` = '0' ORDER BY `id` DESC LIMIT " . $start . ',' . $user->config->kmess);
            $items = [];
            while ($res = $req->fetch()) {
                $res['buttons'] = [
                    [
                        'url'  => '?mod=approve&amp;id=' . $res['id'],
                        'name' => __('Approve'),
                    ],
                    [
                        'url'  => '?mod=del&amp;id=' . $res['id'],
                        'name' => __('Delete'),
                    ],
                    [
                        'url'  => '?mod=delip&amp;id=' . $res['id'],
                        'name' => __('Remove IP'),
                    ],
                ];
                $res['user_id'] = $res['id'];
                $user_properties = new UserProperties();
                $user_data = $user_properties->getFromArray($res);
                $res = array_merge($res, $user_data);
                $items[] = $res;
            }
        }

        $data['back_url'] = '/admin/';

        $data['total'] = $total;
        $data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);
        $data['items'] = $items ?? [];

        echo $view->render(
            'admin::reg_list',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
