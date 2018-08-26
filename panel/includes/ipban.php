<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 9) {
    header('Location: ' . $set['homeurl'] . '/?err'); exit;
}

switch ($mod) {
    case 'new':
        /*
        -----------------------------------------------------------------
        Баним IP адрес
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=ipban"><b>' . $lng['ip_ban'] . '</b></a> | ' . $lng['ban_do'] . '</div>';
        if (isset($_POST['submit'])) {
            $error = '';
            $get_ip = isset($_POST['ip']) ? trim($_POST['ip']) : '';
            $ban_term = isset($_POST['term']) ? intval($_POST['term']) : 1;
            $ban_url = isset($_POST['url']) ? trim($_POST['url']) : '';
            $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
            if (empty($get_ip)) {
                echo functions::display_error($lng['error_address'], '<a href="index.php?act=ipban&amp;mod=new">' . $lng['back'] . '</a>');
                require_once('../incfiles/end.php');
                exit;
            }
            $ip1 = 0;
            $ip2 = 0;
            if (strstr($get_ip, '-')) {
                // Обрабатываем диапазон адресов
                $mode = 1;
                $array = explode('-', $get_ip);
                $get_ip = trim($array[0]);
                if (!core::ip_valid($get_ip)) {
                    $error[] = $lng['error_firstip'];
                } else {
                    $ip1 = ip2long($get_ip);
                }
                $get_ip = trim($array[1]);
                if (!core::ip_valid($get_ip)) {
                    $error[] = $lng['error_secondip'];
                } else {
                    $ip2 = ip2long($get_ip);
                }
            } elseif (strstr($get_ip, '*')) {
                // Обрабатываем адреса с маской
                $mode = 2;
                $array = explode('.', $get_ip);
                $ipt1 = $ipt2 = [];
                for ($i = 0; $i < 4; $i++) {
                    if (!isset($array[$i]) || $array[$i] == '*') {
                        $ipt1[$i] = '0';
                        $ipt2[$i] = '255';
                    } elseif (is_numeric($array[$i]) && $array[$i] >= 0 && $array[$i] <= 255) {
                        $ipt1[$i] = $array[$i];
                        $ipt2[$i] = $array[$i];
                    } else {
                        $error = $lng['error_address'];
                    }
                }
                if (!$error) {
                    $ip1 = ip2long($ipt1[0] . '.' . $ipt1[1] . '.' . $ipt1[2] . '.' . $ipt1[3]);
                    $ip2 = ip2long($ipt2[0] . '.' . $ipt2[1] . '.' . $ipt2[2] . '.' . $ipt2[3]);
                }
            } else {
                // Обрабатываем одиночный адрес
                $mode = 3;
                if (!core::ip_valid($get_ip)) {
                    $error = $lng['error_address'];
                } else {
                    $ip1 = ip2long($get_ip);
                    $ip2 = $ip1;
                }
            }
            if (!$error) {
                // Проверка на конфликты адресов
                $stmt = $db->query("SELECT * FROM `cms_ban_ip` WHERE ('$ip1' BETWEEN `ip1` AND `ip2`) OR ('$ip2' BETWEEN `ip1` AND `ip2`) OR (`ip1` >= '$ip1' AND `ip2` <= '$ip2')");
                $total = $stmt->rowCount();
                if ($total) {
                    echo functions::display_error($lng['ip_ban_conflict_address']);
                    $i = 0;
                    while ($res = $stmt->fetch()) {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        $get_ip = $res['ip1'] == $res['ip2'] ? long2ip($res['ip1']) : long2ip($res['ip1']) . ' - ' . long2ip($res['ip2']);
                        echo '<a href="index.php?act=ipban&amp;mod=detail&amp;id=' . $res['id'] . '">' . $get_ip . '</a> ';
                        switch ($res['ban_type']) {
                            case 2:
                                echo $lng['redirect'];
                                break;

                            case 3:
                                echo $lng['registration'];
                                break;

                            default:
                                echo '<b>' . $lng['blocking'] . '</b>';
                        }
                        echo '</div>';
                        ++$i;
                    }
                    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                    echo '<p><a href="index.php?act=ipban&amp;mod=new">' . $lng['back'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
                    require_once('../incfiles/end.php');
                    exit;
                }
            }
            // Проверяем, не попадает ли IP администратора в диапазон
            if ((core::$ip >= $ip1 && core::$ip <= $ip2) || (core::$ip_via_proxy >= $ip1 && core::$ip_via_proxy <= $ip2)) {
                $error = $lng['ip_ban_conflict_admin'];
            }
            if (!$error) {
                // Окно подтверждения
                echo '<form action="index.php?act=ipban&amp;mod=insert" method="post">';
                switch ($mode) {
                    case 1:
                        echo '<div class="menu"><p><h3>' . $lng['ip_ban_type1'] . '</h3>&nbsp;' . long2ip($ip1) . ' - ' . long2ip($ip2) . '</p>';
                        break;

                    case 2:
                        echo '<div class="menu"><p><h3>' . $lng['ip_ban_type2'] . '</h3>' . long2ip($ip1) . ' - ' . long2ip($ip2) . '</p>';
                        break;

                    default:
                        echo '<div class="menu"><p><h3>' . $lng['ip_ban_type3'] . '</h3>&nbsp;' . long2ip($ip1) . '</p>';
                }
                echo '<p><h3>' . $lng['ban_type'] . ':</h3>&nbsp;';
                switch ($ban_term) {
                    case 2:
                        echo $lng['redirect'] . '</p><p><h3>' . $lng['redirect_url'] . ':</h3>&nbsp;' . (empty($ban_url) ? $lng['default'] : _e($ban_url));
                        break;

                    case 3:
                        echo $lng['registration'];
                        break;

                    default:
                        echo $lng['blocking'];
                }
                echo '</p><p><h3>' . $lng['reason'] . ':</h3>&nbsp;' . (empty($reason) ? $lng['not_specified'] : _e($reason)) . '</p>' .
                    '<input type="hidden" value="' . $ip1 . '" name="ip1" />' .
                    '<input type="hidden" value="' . $ip2 . '" name="ip2" />' .
                    '<input type="hidden" value="' . $ban_term . '" name="term" />' .
                    '<input type="hidden" value="' . _e($ban_url) . '" name="url" />' .
                    '<input type="hidden" value="' . _e($reason) . '" name="reason" />' .
                    '<p><input type="submit" name="submit" value=" ' . $lng['ban_do'] . ' "/></p>' .
                    '</div><div class="phdr"><small>' . $lng['check_confirmation'] . '</small></div>' .
                    '</form>' .
                    '<p><a href="index.php?act=ipban">' . $lng['cancel'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
            } else {
                echo functions::display_error($error, '<a href="index.php?act=ipban&amp;mod=new">' . $lng['back'] . '</a>');
            }
        } else {
            // Форма ввода IP адреса для Бана
            echo '<form action="index.php?act=ipban&amp;mod=new" method="post">' .
                '<div class="menu"><p><h3>' . $lng['ip_address'] . ':</h3>' .
                '&nbsp;<input type="text" name="ip"/></p>' .
                '<p><h3>' . $lng['ban_type'] . ':</h3>' .
                '<input name="term" type="radio" value="1" checked="checked" />' . $lng['blocking'] . '<br />' .
                '<input name="term" type="radio" value="3" />' . $lng['registration'] . '<br />' .
                '<input name="term" type="radio" value="2" />' . $lng['redirect'] . '<br /></p>' .
                '<p><h3>' . $lng['redirect_url'] . '</h3>' .
                '&nbsp;<input type="text" name="url"/><br />' .
                '<small>&nbsp;' . $lng['not_mandatory_field'] . '<br />&nbsp;' . $lng['url_help'] . '</small></p>' .
                '<p><h3>' . $lng['reason'] . '</h3>' .
                '&nbsp;<textarea rows="' . core::$user_set['field_h'] . '" name="reason"></textarea>' .
                '<br /><small>&nbsp;' . $lng['not_mandatory_field'] . '</small></p>' .
                '<p><input type="submit" name="submit" value=" ' . $lng['ban_do'] . ' "/></p></div>' .
                '<div class="phdr"><small>' . $lng['ip_ban_help'] . '</small></div>' .
                '</form>' .
                '<p><a href="index.php?act=ipban">' . $lng['cancel'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
        }
        break;

    case 'insert':
        /*
        -----------------------------------------------------------------
        Проверяем адрес и вставляем в базу
        -----------------------------------------------------------------
        */
        $ip1 = isset($_POST['ip1']) ? intval($_POST['ip1']) : '';
        $ip2 = isset($_POST['ip2']) ? intval($_POST['ip2']) : '';
        $ban_term = isset($_POST['term']) ? intval($_POST['term']) : 1;
        $ban_url = isset($_POST['url']) ? trim($_POST['url']) : '';
        $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';
        if (!$ip1 || !$ip2) {
            echo functions::display_error($lng['error_address'], '<a href="index.php?act=ipban&amp;mod=new">' . $lng['back'] . '</a>');
            require_once('../incfiles/end.php');
            exit;
        }
        $stmt = $db->prepare("INSERT INTO `cms_ban_ip` SET
            `ip1` = '$ip1',
            `ip2` = '$ip2',
            `ban_type` = '$ban_term',
            `link` = ?,
            `who` = ?,
            `reason` = ?,
            `date` = '" . time() . "'
        ");
        $stmt->execute([
            $ban_url,
            $login,
            $reason
        ]);
        header('Location: index.php?act=ipban'); exit;
        break;

    case 'clear':
        /*
        -----------------------------------------------------------------
        Очистка таблицы банов по IP
        -----------------------------------------------------------------
        */
        if (isset($_GET['yes'])) {
            $db->exec("TRUNCATE TABLE `cms_ban_ip`");
            header('Location: index.php?act=ipban'); exit;
        } else {
            echo '<div class="rmenu"><p>' . $lng['ip_ban_clean_warning'] . '</p>' .
                '<p><a href="index.php?act=ipban&amp;mod=clear&amp;yes=yes">' . $lng['do'] . '</a> | ' .
                '<a href="index.php?act=ipban">' . $lng['cancel'] . '</a></p></div>';
        }
        break;

    case 'detail':
        /*
        -----------------------------------------------------------------
        Вывод подробностей заблокированного адреса
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=ipban"><b>' . $lng['ip_ban'] . '</b></a> | ' . $lng['ban_details'] . '</div>';
        if ($id) {
            // Поиск адреса по ссылке (ID)
            $stmt = $db->query("SELECT * FROM `cms_ban_ip` WHERE `id` = '$id' LIMIT 1");
            $get_ip = '';
        } elseif (isset($_POST['ip'])) {
            // Поиск адреса по запросу из формы
            $get_ip = ip2long($_POST['ip']);
            if (!$get_ip) {
                echo functions::display_error($lng['error_address'], '<a href="index.php?act=ipban&amp;mod=new">' . $lng['back'] . '</a>');
                require_once('../incfiles/end.php');
                exit;
            }
            $stmt = $db->query("SELECT * FROM `cms_ban_ip` WHERE '$get_ip' BETWEEN `ip1` AND `ip2` LIMIT 1");
        } else {
            echo functions::display_error($lng['error_address'], '<a href="index.php?act=ipban&amp;mod=new">' . $lng['back'] . '</a>');
            require_once('../incfiles/end.php');
            exit;
        }
        if (!$stmt->rowCount()) {
            echo '<div class="menu"><p>' . $lng['ip_search_notfound'] . '</p></div>';
            echo '<div class="phdr"><a href="index.php?act=ipban">' . $lng['back'] . '</a></div>';
            require_once('../incfiles/end.php');
            exit;
        } else {
            $res = $stmt->fetch();
            $get_ip = $res['ip1'] == $res['ip2'] ? '<b>' . long2ip($res['ip1']) . '</b>' : '[<b>' . long2ip($res['ip1']) . '</b>] - [<b>' . long2ip($res['ip2']) . '</b>]';
            echo '<div class="rmenu"><p>' . $get_ip . '</p></div>';
            echo '<div class="menu"><p><h3>' . $lng['ban_type'] . '</h3>&nbsp;';
            switch ($res['ban_type']) {
                case 2:
                    echo $lng['redirect'];
                    break;

                case 3:
                    echo $lng['registration'];
                    break;

                default:
                    echo $lng['blocking'];
            }
            if ($res['ban_type'] == 2)
                echo '<br />&nbsp;' . _e($res['link']);
            echo '</p><p><h3>' . $lng['reason'] . '</h3>&nbsp;' . (empty($res['reason']) ? $lng['not_specified'] : _e($res['reason'])) . '</p></div>';
            echo '<div class="menu">' . $lng['ban_who'] . ': <b>' . $res['who'] . '</b><br />';
            echo $lng['date'] . ': <b>' . date('d.m.Y', $res['date']) . '</b><br />';
            echo $lng['time'] . ': <b>' . date('H:i:s', $res['date']) . '</b></div>';
            echo '<div class="phdr"><a href="index.php?act=ipban&amp;mod=del&amp;id=' . $res['id'] . '">' . $lng['ip_ban_del'] . '</a></div>';
            echo '<p><a href="index.php?act=ipban">В список</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
        }
        break;

    case 'del':
        /*
        -----------------------------------------------------------------
        Удаление выбранного IP из базы
        -----------------------------------------------------------------
        */
        if ($id) {
            if (isset($_GET['yes'])) {
                $db->exec("DELETE FROM `cms_ban_ip` WHERE `id`='$id'");
                $db->query("OPTIMIZE TABLE `cms_ban_ip`");
                echo '<p>' . $lng['ban_del_confirmation'] . '</p>';
                echo '<p><a href="index.php?act=ipban">' . $lng['continue'] . '</a></p>';
            } else {
                echo '<p>' . $lng['ban_del_question'] . '</p>' .
                    '<p><a href="index.php?act=ipban&amp;mod=del&amp;id=' . $id . '&amp;yes=yes">' . $lng['delete'] . '</a> | ' .
                    '<a href="index.php?act=ipban&amp;mod=detail&amp;id=' . $id . '">' . $lng['cancel'] . '</a></p>';
            }
        }
        break;

    case 'search':
        /*
        -----------------------------------------------------------------
        Форма поиска забаненного IP
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=ipban"><b>' . $lng['ip_ban'] . '</b></a> | ' . $lng['search'] . '</div>' .
            '<form action="index.php?act=ipban&amp;mod=detail" method="post"><div class="menu"><p>' .
            '<h3>' . $lng['ip_address'] . ':</h3>' .
            '<input type="text" name="ip"/>' .
            '</p><p><input type="submit" name="submit" value="' . $lng['search'] . '"/>' .
            '</p></div><div class="phdr"><small>' . $lng['ip_ban_search_help'] . '</small></div>' .
            '</form>' .
            '<p><a href="index.php?act=ipban">' . $lng['back'] . '</a><br /><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Вывод общего списка забаненных IP
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['ip_ban'] . '</div>';
        $total = $db->query("SELECT COUNT(*) FROM `cms_ban_ip`")->fetchColumn();
        if ($total > 0) {
            $stmt = $db->query("SELECT * FROM `cms_ban_ip` ORDER BY `id` ASC LIMIT $start,$kmess");
            $i = 0;
            while ($res = $stmt->fetch()) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $get_ip = $res['ip1'] == $res['ip2'] ? long2ip($res['ip1']) : long2ip($res['ip1']) . ' - ' . long2ip($res['ip2']);
                echo '<a href="index.php?act=ipban&amp;mod=detail&amp;id=' . $res['id'] . '">' . $get_ip . '</a> ';
                switch ($res['ban_type']) {
                    case 2:
                        echo $lng['redirect'];
                        break;

                    case 3:
                        echo $lng['registration'];
                        break;

                    default:
                        echo '<b>' . $lng['blocking'] . '</b>';
                }
                echo '</div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        echo '<div class="rmenu"><form action="index.php?act=ipban&amp;mod=new" method="post"><input type="submit" name="" value="' . $lng['ip_ban_new'] . '" /></form></div>';
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?act=ipban&amp;', $start, $total, $kmess) . '</div>';
            echo '<p><form action="index.php?act=ipban" method="post"><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        echo '<p>';
        if ($total > 0)
            echo '<a href="index.php?act=ipban&amp;mod=search">' . $lng['search'] . '</a><br /><a href="index.php?act=ipban&amp;mod=clear">' . $lng['ip_ban_clean'] . '</a><br />';
        echo '<a href="index.php">' . $lng['admin_panel'] . '</a></p>';
}
?>
