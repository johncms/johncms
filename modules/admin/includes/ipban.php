<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNADM') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if ($user->rights < 9) {
    exit(__('Access denied'));
}

switch ($mod) {
    case 'new':
        // Баним IP адрес
        echo '<div class="phdr"><a href="?act=ipban"><b>' . __('Ban by IP') . '</b></a> | ' . __('Add Ban') . '</div>';

        if (isset($_POST['submit'])) {
            $error = '';
            $get_ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
            $ban_term = isset($_POST['term']) ? (int) ($_POST['term']) : 1;
            $ban_url = isset($_POST['url']) ? htmlentities(trim($_POST['url']), ENT_QUOTES, 'UTF-8') : '';
            $reason = isset($_POST['reason']) ? htmlentities(trim($_POST['reason']), ENT_QUOTES, 'UTF-8') : '';

            if (empty($get_ip)) {
                echo $tools->displayError(
                    __('Invalid IP'),
                    '<a href="?act=ipban&amp;mod=new">' . __('Back') . '</a>'
                );
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
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

                    $ip1 = ip2long($ipt1[0] . '.' . $ipt1[1] . '.' . $ipt1[2] . '.' . $ipt1[3]);
                    $ip2 = ip2long($ipt2[0] . '.' . $ipt2[1] . '.' . $ipt2[2] . '.' . $ipt2[3]);
                }
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
                    echo $tools->displayError(__('Address you entered conflicts with other who in the database'));
                    $i = 0;

                    while ($res = $req->fetch()) {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        $get_ip = $res['ip1'] == $res['ip2'] ? long2ip($res['ip1']) : long2ip($res['ip1']) . ' - ' . long2ip($res['ip2']);
                        echo '<a href="?act=ipban&amp;mod=detail&amp;id=' . $res['id'] . '">' . $get_ip . '</a> ';

                        switch ($res['ban_type']) {
                            case 2:
                                echo __('Redirect');
                                break;

                            case 3:
                                echo __('Registration');
                                break;

                            default:
                                echo '<b>' . __('Block') . '</b>';
                        }
                        echo '</div>';
                        ++$i;
                    }

                    echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';
                    echo '<p><a href="?act=ipban&amp;mod=new">' . __('Back') . '</a><br><a href="./">' . __('Admin Panel') . '</a></p>';
                    echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
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
                echo '<form action="?act=ipban&amp;mod=insert" method="post">';

                switch ($mode) {
                    case 1:
                        echo '<div class="menu"><p><h3>' . __('Ban range address') . '</h3>&nbsp;' . long2ip($ip1) . ' - ' . long2ip($ip2) . '</p>';
                        break;

                    case 2:
                        echo '<div class="menu"><p><h3>' . __('Ban on the subnet mask') . '</h3>' . long2ip($ip1) . ' - ' . long2ip($ip2) . '</p>';
                        break;

                    default:
                        echo '<div class="menu"><p><h3>' . __('Ban IP address') . '</h3>&nbsp;' . long2ip($ip1) . '</p>';
                }

                echo '<p><h3>' . __('Ban type') . ':</h3>&nbsp;';

                switch ($ban_term) {
                    case 2:
                        echo __('Redirect') . '</p><p><h3>' . __('Redirect URL') . ':</h3>&nbsp;' . (empty($ban_url) ? __('Default') : $ban_url);
                        break;

                    case 3:
                        echo __('Registration');
                        break;

                    default:
                        echo __('Block');
                }

                echo '</p><p><h3>' . __('Reason') . ':</h3>&nbsp;' . (empty($reason) ? __('Not specified') : $reason) . '</p>' .
                    '<input type="hidden" value="' . $ip1 . '" name="ip1" />' .
                    '<input type="hidden" value="' . $ip2 . '" name="ip2" />' .
                    '<input type="hidden" value="' . $ban_term . '" name="term" />' .
                    '<input type="hidden" value="' . $ban_url . '" name="url" />' .
                    '<input type="hidden" value="' . $reason . '" name="reason" />' .
                    '<p><input type="submit" name="submit" value=" ' . __('Add Ban') . ' "/></p>' .
                    '</div><div class="phdr"><small>' . __('Please, check up correctness of the input data') . '</small></div>' .
                    '</form>' .
                    '<p><a href="?act=ipban">' . __('Cancel') . '</a><br><a href="./">' . __('Admin Panel') . '</a></p>';
            } else {
                echo $tools->displayError(
                    $error,
                    '<a href="?act=ipban&amp;mod=new">' . __('Back') . '</a>'
                );
            }
        } else {
            // Форма ввода IP адреса для Бана
            echo '<form action="?act=ipban&amp;mod=new" method="post">' .
                '<div class="menu"><p><h3>' . __('IP address') . '</h3>' .
                '&nbsp;<input type="text" name="ip"/></p>' .
                '<p><h3>' . __('Ban type') . '</h3>' .
                '<input name="term" type="radio" value="1" checked="checked" />' . __('Block') . '<br>' .
                '<input name="term" type="radio" value="3" />' . __('Registration') . '<br>' .
                '<input name="term" type="radio" value="2" />' . __('Redirect') . '<br></p>' .
                '<p><h3>' . __('Redirect URL') . '</h3>' .
                '&nbsp;<input type="text" name="url"/><br>' .
                '<small>&nbsp;' . __('If the ban on Redirect, then specify the URL') . '</small></p>' .
                '<p><h3>' . __('Reason') . '</h3>' .
                '&nbsp;<textarea rows="' . $user->config->fieldHeight . '" name="reason"></textarea></small></p>' .
                '<p><input type="submit" name="submit" value=" ' . __('Add Ban') . ' "/></p></div>' .
                '<div class="phdr"><small>' . __('Example:<br><span class=\'red\'>10.5.7.1</span> - Ban one address<br><span class=\'red\'>10.5.7.1-10.5.7.100</span> - Ban range of address.<br><span class=\'red\'>10.5.*.*</span> - Ban on a mask. There will banned from the entrie subnet, begining with address 0 and ending with 255') . '</small></div>' . // phpcs:ignore
                '</form>' .
                '<p><a href="?act=ipban">' . __('Cancel') . '</a><br><a href="./">' . __('Admin Panel') . '</a></p>';
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
            echo $tools->displayError(
                __('Invalid IP'),
                '<a href="?act=ipban&amp;mod=new">' . __('Back') . '</a>'
            );
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
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
            echo '<div class="rmenu"><p>' . __('Are you sure you wan to unban all IP?') . '</p>' .
                '<p><a href="?act=ipban&amp;mod=clear&amp;yes=yes">' . __('Perform') . '</a> | ' .
                '<a href="?act=ipban">' . __('Cancel') . '</a></p></div>';
        }
        break;

    case 'detail':
        // Вывод подробностей заблокированного адреса
        echo '<div class="phdr"><a href="?act=ipban"><b>' . __('Ban by IP') . '</b></a> | ' . __('Ban details') . '</div>';

        if ($id) {
            // Поиск адреса по ссылке (ID)
            $req = $db->query("SELECT * FROM `cms_ban_ip` WHERE `id` = '${id}'");
            $get_ip = '';
        } elseif (isset($_POST['ip'])) {
            // Поиск адреса по запросу из формы
            $get_ip = ip2long($_POST['ip']);

            if (! $get_ip) {
                echo $tools->displayError(
                    __('Invalid IP'),
                    '<a href="?act=ipban&amp;mod=new">' . __('Back') . '</a>'
                );
                echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
                exit;
            }

            $req = $db->query("SELECT * FROM `cms_ban_ip` WHERE '${get_ip}' BETWEEN `ip1` AND `ip2` LIMIT 1");
        } else {
            echo $tools->displayError(
                __('Invalid IP'),
                '<a href="?act=ipban&amp;mod=new">' . __('Back') . '</a>'
            );
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
            exit;
        }

        if (! $req->rowCount()) {
            echo '<div class="menu"><p>' . __('This address not in the database') . '</p></div>';
            echo '<div class="phdr"><a href="?act=ipban">' . __('Back') . '</a></div>';
            echo $view->render('system::app/old_content', ['content' => ob_get_clean()]);
            exit;
        }
        $res = $req->fetch();
        $get_ip = $res['ip1'] == $res['ip2'] ? '<b>' . long2ip($res['ip1']) . '</b>' : '[<b>' . long2ip($res['ip1']) . '</b>] - [<b>' . long2ip($res['ip2']) . '</b>]';
        echo '<div class="rmenu"><p>' . $get_ip . '</p></div>';
        echo '<div class="menu"><p><h3>' . __('Ban type') . '</h3>&nbsp;';

        switch ($res['ban_type']) {
            case 2:
                echo __('Redirect');
                break;

            case 3:
                echo __('Registration');
                break;

            default:
                echo __('Block');
        }

        if ($res['ban_type'] == 2) {
            echo '<br>&nbsp;' . $res['link'];
        }

        echo '</p><p><h3>' . __('Reason') . '</h3>&nbsp;' . (empty($res['reason']) ? __('Not specified') : $res['reason']) . '</p></div>';
        echo '<div class="menu">' . __('Who applied the ban?') . ': <b>' . $res['who'] . '</b><br>';
        echo __('Date') . ': <b>' . date('d.m.Y', $res['date']) . '</b><br>';
        echo __('Time') . ': <b>' . date('H:i:s', $res['date']) . '</b></div>';
        echo '<div class="phdr"><a href="?act=ipban&amp;mod=del&amp;id=' . $res['id'] . '">' . __('Delete Ban') . '</a></div>';
        echo '<p><a href="?act=ipban">В список</a><br><a href="./">' . __('Admin Panel') . '</a></p>';

        break;

    case 'del':
        // Удаление выбранного IP из базы
        if ($id) {
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `cms_ban_ip` WHERE `id`='${id}'");
                $db->query('OPTIMIZE TABLE `cms_ban_ip`');
                echo '<p>' . __('Ban has been successfully removed') . '</p>';
                echo '<p><a href="?act=ipban">' . __('Continue') . '</a></p>';
            } else {
                echo '<p>' . __('re you sure to remove ban?') . '</p>' .
                    '<p><a href="?act=ipban&amp;mod=del&amp;id=' . $id . '&amp;yes=yes">' . __('Delete') . '</a> | ' .
                    '<a href="?act=ipban&amp;mod=detail&amp;id=' . $id . '">' . __('Cancel') . '</a></p>';
            }
        }
        break;

    case 'search':
        // Форма поиска забаненного IP
        echo '<div class="phdr"><a href="?act=ipban"><b>' . __('Ban by IP') . '</b></a> | ' . __('Search') . '</div>' .
            '<form action="?act=ipban&amp;mod=detail" method="post"><div class="menu"><p>' .
            '<h3>' . __('IP address') . ':</h3>' .
            '<input type="text" name="ip"/>' .
            '</p><p><input type="submit" name="submit" value="' . __('Search') . '"/>' .
            '</p></div><div class="phdr"><small>' . __('Enter a single address, mask and range are not allowed') . '</small></div>' .
            '</form>' .
            '<p><a href="?act=ipban">' . __('Back') . '</a><br><a href="./">' . __('Admin Panel') . '</a></p>';
        break;

    default:
        // Вывод общего списка забаненных IP
        echo '<div class="phdr"><a href="./"><b>' . __('Admin Panel') . '</b></a> | ' . __('Ban by IP') . '</div>';
        $total = $db->query('SELECT COUNT(*) FROM `cms_ban_ip`')->fetchColumn();

        if ($total) {
            $page = isset($_REQUEST['page']) && $_REQUEST['page'] > 0 ? (int) ($_REQUEST['page']) : 1;
            $start = isset($_REQUEST['page']) ? $page * $user->config->kmess - $user->config->kmess : (isset($_GET['start']) ? abs((int) ($_GET['start'])) : 0);

            $req = $db->query('SELECT * FROM `cms_ban_ip` ORDER BY `id` ASC LIMIT ' . $start . ',' . $user->config->kmess);
            $i = 0;

            while ($res = $req->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $get_ip = $res['ip1'] == $res['ip2'] ? long2ip($res['ip1']) : long2ip($res['ip1']) . ' - ' . long2ip($res['ip2']);
                echo '<a href="?act=ipban&amp;mod=detail&amp;id=' . $res['id'] . '">' . $get_ip . '</a> ';

                switch ($res['ban_type']) {
                    case 2:
                        echo __('Redirect');
                        break;

                    case 3:
                        echo __('Registration');
                        break;

                    default:
                        echo '<b>' . __('Block') . '</b>';
                }
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . __('The list is empty') . '</p></div>';
        }

        echo '<div class="rmenu"><form action="?act=ipban&amp;mod=new" method="post"><input type="submit" name="" value="' . __('Ban') . '" /></form></div>';
        echo '<div class="phdr">' . __('Total') . ': ' . $total . '</div>';

        if ($total > $user->config->kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?act=ipban&amp;', $start, $total, $user->config->kmess) . '</div>';
            echo '<p><form action="?act=ipban" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . __('To Page') . ' &gt;&gt;"/></form></p>';
        }

        echo '<p>';

        if ($total > 0) {
            echo '<a href="?act=ipban&amp;mod=search">' . __('Search') . '</a><br><a href="?act=ipban&amp;mod=clear">' . __('Unban all IP') . '</a><br>';
        }

        echo '<a href="./">' . __('Admin Panel') . '</a></p>';
}

echo $view->render(
    'system::app/old_content',
    [
        'title'   => __('Admin Panel'),
        'content' => ob_get_clean(),
    ]
);
