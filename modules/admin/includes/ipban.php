<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

$title = __('Ban by IP');
$nav_chain->add($title, '/admin/ipban/');
$data = [];

if ($user->rights < 9) {
    echo $view->render(
        'system::pages/result',
        [
            'title'       => $title,
            'type'        => 'alert-danger',
            'message'     => __('Access denied'),
            'admin'       => true,
            'menu_item'   => 'ipban',
            'parent_menu' => 'sec_menu',
        ]
    );
    exit();
}

switch ($mod) {
    case 'new':
        // Баним IP адрес
        $title = __('Add Ban');

        if (isset($_POST['submit'])) {
            $error = '';
            $get_ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
            $ban_term = isset($_POST['term']) ? (int) ($_POST['term']) : 1;
            $ban_url = isset($_POST['url']) ? htmlentities(trim($_POST['url']), ENT_QUOTES, 'UTF-8') : '';
            $reason = isset($_POST['reason']) ? htmlentities(trim($_POST['reason']), ENT_QUOTES, 'UTF-8') : '';

            if (empty($get_ip)) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('Invalid IP'),
                        'admin'         => true,
                        'menu_item'     => 'ipban',
                        'parent_menu'   => 'sec_menu',
                        'back_url'      => '/admin/ipban/?mod=new',
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            $ip1 = 0;
            $ip2 = 0;
            $ipt1 = [];
            $ipt2 = [];

            if (strstr($get_ip, '-')) {
                // Обрабатываем диапазон адресов
                $mode = 1;
                $array = explode('-', $get_ip);
                $get_ip = trim($array[0]);

                if (! preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $get_ip)) {
                    $error[] = __('First IP is entered incorrectly');
                } else {
                    $ip1 = ip2long($get_ip);
                }

                $get_ip = trim($array[1]);

                if (! preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $get_ip)) {
                    $error[] = __('Second IP is entered incorrectly');
                } else {
                    $ip2 = ip2long($get_ip);
                }
            } elseif (strstr($get_ip, '*')) {
                // Обрабатываем адреса с маской
                $mode = 2;
                $array = explode('.', $get_ip);

                for ($i = 0; $i < 4; $i++) {
                    if (! isset($array[$i]) || $array[$i] == '*') {
                        $ipt1[$i] = '0';
                        $ipt2[$i] = '255';
                    } elseif (is_numeric($array[$i]) && $array[$i] >= 0 && $array[$i] <= 255) {
                        $ipt1[$i] = $array[$i];
                        $ipt2[$i] = $array[$i];
                    } else {
                        $error = __('Invalid IP');
                    }
                }

                $ip1 = ip2long($ipt1[0] . '.' . $ipt1[1] . '.' . $ipt1[2] . '.' . $ipt1[3]);
                $ip2 = ip2long($ipt2[0] . '.' . $ipt2[1] . '.' . $ipt2[2] . '.' . $ipt2[3]);
            } else {
                // Обрабатываем одиночный адрес
                $mode = 3;

                if (! preg_match('#^(?:(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])\.){3}(?:\d{1,2}|1\d\d|2[0-4]\d|25[0-5])$#', $get_ip)) {
                    $error = __('Invalid IP');
                } else {
                    $ip1 = ip2long($get_ip);
                    $ip2 = $ip1;
                }
            }

            if (! $error) {
                // Проверка на конфликты адресов
                $req = $db->query("SELECT * FROM `cms_ban_ip` WHERE ('${ip1}' BETWEEN `ip1` AND `ip2`) OR ('${ip2}' BETWEEN `ip1` AND `ip2`) OR (`ip1` >= '${ip1}' AND `ip2` <= '${ip2}')");
                $total = $req->rowCount();

                if ($total) {
                    $data['message'] = __('Address you entered conflicts with other who in the database');
                    while ($res = $req->fetch()) {
                        $get_ip = $res['ip1'] == $res['ip2'] ? long2ip((int) $res['ip1']) : long2ip((int) $res['ip1']) . ' - ' . long2ip((int) $res['ip2']);
                        $res['detail_url'] = '?mod=detail&amp;id=' . $res['id'];
                        $res['ips'] = $get_ip;

                        switch ($res['ban_type']) {
                            case 2:
                                $res['reason'] = __('Redirect');
                                break;

                            case 3:
                                $res['reason'] = __('Registration');
                                break;

                            default:
                                $res['reason'] = __('Block');
                        }

                        $items[] = $res;
                    }

                    $data['items'] = $items ?? [];
                    $data['total'] = $total;
                    $data['no_buttons'] = true;
                    $data['back_url'] = '/admin/ipban/?mod=new';

                    echo $view->render(
                        'admin::ipban',
                        [
                            'title'      => $title,
                            'page_title' => $title,
                            'data'       => $data,
                        ]
                    );
                    exit;
                }
            }

            // Проверяем, не попадает ли IP администратора в диапазон

            /** @var Johncms\System\Http\Environment $env */
            $env = di(Johncms\System\Http\Environment::class);

            if (($env->getIp() >= $ip1 && $env->getIp() <= $ip2) || ($env->getIpViaProxy() >= $ip1 && $env->getIpViaProxy() <= $ip2)) {
                $error = __('Ban impossible. Your own IP address in the range');
            }

            if (! $error) {
                // Окно подтверждения
                $ban_info = [];
                switch ($mode) {
                    case 1:
                        $ban_info['mode_name'] = __('Ban range address');
                        $ban_info['mode_value'] = long2ip((int) $ip1) . ' - ' . long2ip((int) $ip2);
                        break;

                    case 2:
                        $ban_info['mode_name'] = __('Ban on the subnet mask');
                        $ban_info['mode_value'] = long2ip((int) $ip1) . ' - ' . long2ip((int) $ip2);
                        break;

                    default:
                        $ban_info['mode_name'] = __('Ban IP address');
                        $ban_info['mode_value'] = long2ip((int) $ip1);
                }

                switch ($ban_term) {
                    case 2:
                        $ban_info['ban_type'] = __('Redirect');
                        $ban_info['ban_url'] = (empty($ban_url) ? __('Default') : $ban_url);
                        break;

                    case 3:
                        $ban_info['ban_type'] = __('Registration');
                        break;

                    default:
                        $ban_info['ban_type'] = __('Block');
                }

                $ban_info['ip1'] = $ip1;
                $ban_info['ip2'] = $ip2;
                $ban_info['ban_term'] = $ban_term;
                $ban_info['ban_url'] = $ban_url;
                $ban_info['reason'] = $reason;
                $ban_info['reason_display'] = (empty($reason) ? __('Not specified') : $reason);
                $data['ban_info'] = $ban_info;

                $data['form_action'] = '?act=ipban&amp;mod=insert';
                echo $view->render(
                    'admin::ipban_add_confirm',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                        'data'       => $data,
                    ]
                );
            } else {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => $error,
                        'admin'         => true,
                        'menu_item'     => 'ipban',
                        'parent_menu'   => 'sec_menu',
                        'back_url'      => '/admin/ipban/?mod=new',
                        'back_url_name' => __('Back'),
                    ]
                );
            }
        } else {
            // Форма ввода IP адреса для Бана
            $data['form_action'] = '?act=ipban&amp;mod=new';
            echo $view->render(
                'admin::ipban_add',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        break;

    case 'insert':
        // Проверяем адрес и вставляем в базу
        $ip1 = isset($_POST['ip1']) ? (int) ($_POST['ip1']) : '';
        $ip2 = isset($_POST['ip2']) ? (int) ($_POST['ip2']) : '';
        $ban_term = isset($_POST['term']) ? (int) ($_POST['term']) : 1;
        $ban_url = isset($_POST['url']) ? htmlspecialchars(trim($_POST['url'])) : '';
        $reason = isset($_POST['reason']) ? htmlspecialchars(trim($_POST['reason'])) : '';

        if (! $ip1 || ! $ip2) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-danger',
                    'message'       => __('Invalid IP'),
                    'admin'         => true,
                    'menu_item'     => 'ipban',
                    'parent_menu'   => 'sec_menu',
                    'back_url'      => '/admin/ipban/?mod=new',
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $db->prepare(
            '
          INSERT INTO `cms_ban_ip` SET
          `ip1` = ?,
          `ip2` = ?,
          `ban_type` = ?,
          `link` = ?,
          `who` = ?,
          `reason` = ?,
          `date` = ?
        '
        )->execute(
            [
                $ip1,
                $ip2,
                $ban_term,
                $ban_url,
                $user->name,
                $reason,
                time(),
            ]
        );

        header('Location: ?act=ipban');
        break;

    case 'clear':
        // Очистка таблицы банов по IP
        if (isset($_GET['yes'])) {
            $db->query('TRUNCATE TABLE `cms_ban_ip`');
            header('Location: ?act=ipban');
        } else {
            $data['message'] = __('Are you sure you wan to unban all IP?');
            $data['confirm_url'] = '?act=ipban&amp;mod=clear&amp;yes=yes';
            $data['confirm_url_name'] = __('Perform');
            $data['back_url'] = '/admin/ipban/';
            $data['back_url_name'] = __('Cancel');
            echo $view->render(
                'admin::ipban_confirm',
                [
                    'title'      => $title,
                    'page_title' => $title,
                    'data'       => $data,
                ]
            );
        }
        break;

    case 'detail':
        // Вывод подробностей заблокированного адреса
        $title = __('Ban details');

        if ($id) {
            // Поиск адреса по ссылке (ID)
            $req = $db->query("SELECT * FROM `cms_ban_ip` WHERE `id` = '${id}'");
            $get_ip = '';
        } elseif (isset($_POST['ip'])) {
            // Поиск адреса по запросу из формы
            $get_ip = ip2long($_POST['ip']);

            if (! $get_ip) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-danger',
                        'message'       => __('Invalid IP'),
                        'admin'         => true,
                        'menu_item'     => 'ipban',
                        'parent_menu'   => 'sec_menu',
                        'back_url'      => '/admin/ipban/?mod=new',
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            $req = $db->query("SELECT * FROM `cms_ban_ip` WHERE '${get_ip}' BETWEEN `ip1` AND `ip2` LIMIT 1");
        } else {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-danger',
                    'message'       => __('Invalid IP'),
                    'admin'         => true,
                    'menu_item'     => 'ipban',
                    'parent_menu'   => 'sec_menu',
                    'back_url'      => '/admin/ipban/?mod=new',
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        if (! $req->rowCount()) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => $title,
                    'type'          => 'alert-info',
                    'message'       => __('This address not in the database'),
                    'admin'         => true,
                    'menu_item'     => 'ipban',
                    'parent_menu'   => 'sec_menu',
                    'back_url'      => '/admin/ipban/',
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }
        $res = $req->fetch();
        $get_ip = $res['ip1'] == $res['ip2'] ? '<b>' . long2ip((int) $res['ip1']) . '</b>' : '[<b>' . long2ip((int) $res['ip1']) . '</b>] - [<b>' . long2ip((int) $res['ip2']) . '</b>]';
        $res['ips'] = $get_ip;
        switch ($res['ban_type']) {
            case 2:
                $res['ban_type'] = __('Redirect');
                break;

            case 3:
                $res['ban_type'] = __('Registration');
                break;

            default:
                $res['ban_type'] = __('Block');
        }

        if ($res['ban_type'] === 2) {
            $res['link'] = htmlspecialchars($res['link']);
        }

        $res['reason'] = (empty($res['reason']) ? __('Not specified') : htmlspecialchars($res['reason']));
        $res['display_date'] = date('d.m.Y', $res['date']);
        $res['display_time'] = date('H:i:s', $res['date']);
        $data['ban_info'] = $res;
        $data['delete_url'] = '?act=ipban&amp;mod=del&amp;id=' . $res['id'];
        $data['back_url'] = '/admin/ipban/';
        $data['back_url_name'] = __('Back');
        echo $view->render(
            'admin::ipban_detail',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
        break;

    case 'del':
        // Удаление выбранного IP из базы
        if ($id) {
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `cms_ban_ip` WHERE `id`='${id}'");
                $db->query('OPTIMIZE TABLE `cms_ban_ip`');
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => $title,
                        'type'          => 'alert-success',
                        'message'       => __('Ban has been successfully removed'),
                        'admin'         => true,
                        'menu_item'     => 'ipban',
                        'parent_menu'   => 'sec_menu',
                        'back_url'      => '/admin/ipban/',
                        'back_url_name' => __('Continue'),
                    ]
                );
            } else {
                $data['message'] = __('Are you sure you want to remove the ban?');
                $data['confirm_url'] = '?act=ipban&amp;mod=del&amp;id=' . $id . '&amp;yes=yes';
                $data['confirm_url_name'] = __('Delete');
                $data['back_url'] = '?act=ipban&amp;mod=detail&amp;id=' . $id;
                $data['back_url_name'] = __('Cancel');
                echo $view->render(
                    'admin::ipban_confirm',
                    [
                        'title'      => $title,
                        'page_title' => $title,
                        'data'       => $data,
                    ]
                );
            }
        }
        break;

    case 'search':
        // Форма поиска забаненного IP
        $title = __('Search');
        echo $view->render(
            'admin::ipban_search',
            [
                'title'      => $title,
                'page_title' => $title,
            ]
        );
        break;

    default:
        // Вывод общего списка забаненных IP
        $total = $db->query('SELECT COUNT(*) FROM `cms_ban_ip`')->fetchColumn();

        if ($total) {
            $page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
            $start = isset($_REQUEST['page']) ? $page * $user->config->kmess - $user->config->kmess : (isset($_GET['start']) ? abs((int) ($_GET['start'])) : 0);

            $req = $db->query('SELECT * FROM `cms_ban_ip` ORDER BY `id` ASC LIMIT ' . $start . ',' . $user->config->kmess);
            $items = [];
            while ($res = $req->fetch()) {
                $get_ip = $res['ip1'] == $res['ip2'] ? long2ip((int) $res['ip1']) : long2ip((int) $res['ip1']) . ' - ' . long2ip((int) $res['ip2']);
                $res['detail_url'] = '?mod=detail&amp;id=' . $res['id'];
                $res['ips'] = $get_ip;

                switch ($res['ban_type']) {
                    case 2:
                        $res['reason'] = __('Redirect');
                        break;

                    case 3:
                        $res['reason'] = __('Registration');
                        break;

                    default:
                        $res['reason'] = __('Block');
                }

                $items[] = $res;
            }
        }

        $data['items'] = $items ?? [];
        $data['total'] = $total;
        $data['back_url'] = '/admin/ipban/';
        if ($total > $user->config->kmess) {
            $data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);
        }

        echo $view->render(
            'admin::ipban',
            [
                'title'      => $title,
                'page_title' => $title,
                'data'       => $data,
            ]
        );
}
