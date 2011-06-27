<?

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
if ($rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

switch ($mod) {
    case 'edit':
        /*
        -----------------------------------------------------------------
        Добавляем / редактируем ссылку
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=ads"><b>' . $lng['advertisement'] . '</b></a> | ' . ($id ? $lng['link_edit'] : $lng['link_add']) . '</div>';
        if ($id) {
            // Если ссылка редактироется, запрашиваем ее данные в базе
            $req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id'");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
            } else {
                echo functions::display_error($lng['error_wrong_data'], '<a href="index.php?act=ads">' . $lng['back'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
        } else {
            $res = array('link' => 'http://');
        }
        if (isset($_POST['submit'])) {
            $link = isset($_POST['link']) ? mysql_real_escape_string(trim($_POST['link'])) : '';
            $name = isset($_POST['name']) ? mysql_real_escape_string(trim($_POST['name'])) : '';
            $bold = isset($_POST['bold']);
            $italic = isset($_POST['italic']);
            $underline = isset($_POST['underline']);
            $show = isset($_POST['show']);
            $font = $font_1 + $font_2 + $font_3;
            $view = isset($_POST['view']) ? abs(intval($_POST['view'])) : 0;
            $day = isset($_POST['day']) ? abs(intval($_POST['day'])) : 0;
            $count = isset($_POST['count']) ? abs(intval($_POST['count'])) : 0;
            $day = isset($_POST['day']) ? abs(intval($_POST['day'])) : 0;
            $layout = isset($_POST['layout']) ? abs(intval($_POST['layout'])) : 0;
            $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
            $mesto = isset($_POST['mesto']) ? abs(intval($_POST['mesto'])) : 0;
            $color = isset($_POST['color']) ? mb_substr(trim($_POST['color']), 0, 6) : '';
            $error = array();
            if (!$link || !$name)
                $error[] = $lng['error_empty_fields'];
            if ($type > 3 || $type < 0)
                $type = 0;
            if (!$mesto) {
                $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `mesto` = '" . $mesto . "' AND `type` = '" . $type . "'"), 0);
                if ($total != 0)
                    $error[] = $lng['links_place_occupied'];
            }
            if ($color) {
                if (preg_match("/[^\da-fA-F_]+/", $color))
                    $error[] = $lng['error_wrong_symbols'];
                if (strlen($color) < 6)
                    $error[] = $lng['error_color'];
            }
            if ($error) {
                echo functions::display_error($error, '<a href="index.php?act=ads&amp;from=addlink">' . $lng['back'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
            if ($id) {
                // Обновляем ссылку после редактирования
                mysql_query("UPDATE `cms_ads` SET
                    `type` = '$type',
                    `view` = '$view',
                    `link` = '$link',
                    `name` = '$name',
                    `color` = '$color',
                    `count_link` = '$count',
                    `day` = '$day',
                    `layout` = '$layout',
                    `bold` = '$bold',
                    `show` = '$show',
                    `italic` = '$italic',
                    `underline` = '$underline'
                    WHERE `id` = '$id'
                ");
            } else {
                // Добавляем новую ссылку
                $req = mysql_query("SELECT `mesto` FROM `cms_ads` ORDER BY `mesto` DESC LIMIT 1");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $mesto = $res['mesto'] + 1;
                } else {
                    $mesto = 1;
                }
                mysql_query("INSERT INTO `cms_ads` SET
                    `type` = '$type',
                    `view` = '$view',
                    `mesto` = '$mesto',
                    `link` = '$link',
                    `name` = '$name',
                    `color` = '$color',
                    `count_link` = '$count',
                    `day` = '$day',
                    `layout` = '$layout',
                    `to` = '0',
                    `show` = '$show',
                    `time` = '" . time() . "',
                    `bold` = '$bold',
                    `italic` = '$italic',
                    `underline` = '$underline'
                ") or die (mysql_error());
            }
            mysql_query("UPDATE `users` SET `lastpost` = '" . time() . "' WHERE `id` = '$user_id'");
            echo '<div class="menu"><p>' . ($id ? $lng['link_edit_ok'] : $lng['link_add_ok']) . '<br />' .
                 '<a href="index.php?act=ads&amp;sort=' . $type . '">' . $lng['continue'] . '</a></p></div>';
        } else {
            // Форма добавления / изменения ссылки
            echo '<form action="index.php?act=ads&amp;mod=edit' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
                 '<div class="menu"><p><h3>' . $lng['link'] . '</h3>' .
                 '<input type="text" name="link" value="' . htmlentities($res['link'], ENT_QUOTES, 'UTF-8') . '"/><br />' .
                 '<input type="checkbox" name="show" ' . ($res['show'] ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['link_direct'] . '<br />' .
                 '<small>' . $lng['link_direct_help'] . '</small></p>' .
                 '<p><h3>' . $lng['title'] . '</h3>' .
                 '<input type="text" name="name" value="' . htmlentities($res['name'], ENT_QUOTES, 'UTF-8') . '"/><br />' .
                 '<small>' . $lng['link_add_name_help'] . '</small></p>' .
                 '<p><h3>' . $lng['color'] . '</h3>' .
                 '<input type="text" name="color" size="6" value="' . $res['color'] . '"/><br />' .
                 '<small>' . $lng['link_add_color_help'] . '</small></p>' .
                 '<p><h3>' . $lng['transitions'] . '</h3>' .
                 '<input type="text" name="count" size="6" value="' . $res['count_link'] . '"/><br />' .
                 '<small>' . $lng['link_add_trans_help'] . '</small></p>' .
                 '<p><h3>' . $lng['days'] . '</h3>' .
                 '<input type="text" name="day" size="6" value="' . $res['day'] . '"/><br />' .
                 '<small>' . $lng['link_add_days_help'] . '</small></p>' .
                 '</div><div class="gmenu">' .
                 '<p><h3>' . $lng['to_show'] . '</h3>' .
                 '<input type="radio" name="view" value="0" ' . (!$res['view'] ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['to_all'] . '<br />' .
                 '<input type="radio" name="view" value="1" ' . ($res['view'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['to_guest'] . '<br />' .
                 '<input type="radio" name="view" value="2" ' . ($res['view'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['to_users'] . '</p>' .
                 '<p><h3>' . $lng['arrangement'] . '</h3>' .
                 '<input type="radio" name="type" value="0" ' . (!$res['type'] ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['links_armt_over_logo'] . '<br />' .
                 '<input type="radio" name="type" value="1" ' . ($res['type'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['links_armt_under_usermenu'] . '<br />' .
                 '<input type="radio" name="type" value="2" ' . ($res['type'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['links_armt_over_counters'] . '<br />' .
                 '<input type="radio" name="type" value="3" ' . ($res['type'] == 3 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['links_armt_under_counters'] . '</p>' .
                 '<p><h3>' . $lng['placing'] . '</h3>' .
                 '<input type="radio" name="layout" value="0" ' . (!$res['layout'] ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['link_add_placing_all'] . '<br />' .
                 '<input type="radio" name="layout" value="1" ' . ($res['layout'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['link_add_placing_front'] . '<br />' .
                 '<input type="radio" name="layout" value="2" ' . ($res['layout'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;' . $lng['link_add_placing_child'] . '</p>' .
                 '<p><h3>' . $lng['links_allocation'] . '</h3>' .
                 '<input type="checkbox" name="bold" ' . ($res['bold'] ? 'checked="checked"' : '') . '/>&nbsp;<b>' . $lng['font_bold'] . '</b><br />' .
                 '<input type="checkbox" name="italic" ' . ($res['italic'] ? 'checked="checked"' : '') . '/>&nbsp;<i>' . $lng['font_italic'] . '</i><br />' .
                 '<input type="checkbox" name="underline" ' . ($res['underline'] ? 'checked="checked"' : '') . '/>&nbsp;<u>' . $lng['font_underline'] . '</u></p></div>' .
                 '<div class="phdr"><input type="submit" name="submit" value="' . ($id ? $lng['edit'] : $lng['add']) . '" /></div></form>' .
                 '<p><a href="index.php?act=ads">' . $lng['advertisement'] . '</a><br />' .
                 '<a href="index.php">' . $lng['admin_panel'] . '</a></p>';
        }
        break;

    case 'down':
        /*
        -----------------------------------------------------------------
        Перемещаем на позицию вниз
        -----------------------------------------------------------------
        */
        if ($id) {
            $req = mysql_query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '$id'");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
                $mesto = $res['mesto'];
                $req = mysql_query("SELECT * FROM `cms_ads` WHERE `mesto` > '$mesto' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` ASC");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '$mesto2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '$mesto' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: ' . getenv("HTTP_REFERER"));
        break;

    case 'up':
        /*
        -----------------------------------------------------------------
        Перемещаем на позицию вверх
        -----------------------------------------------------------------
        */
        if ($id) {
            $req = mysql_query("SELECT `mesto`, `type` FROM `cms_ads` WHERE `id` = '$id'");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
                $mesto = $res['mesto'];
                $req = mysql_query("SELECT * FROM `cms_ads` WHERE `mesto` < '$mesto' AND `type` = '" . $res['type'] . "' ORDER BY `mesto` DESC");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $id2 = $res['id'];
                    $mesto2 = $res['mesto'];
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '$mesto2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `cms_ads` SET `mesto` = '$mesto' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: ' . getenv("HTTP_REFERER") . '');
        break;

    case 'del':
        /*
        -----------------------------------------------------------------
        Удаляем ссылку
        -----------------------------------------------------------------
        */
        if ($id) {
            if (isset($_POST['submit'])) {
                mysql_query("DELETE FROM `cms_ads` WHERE `id` = '$id'");
                header('Location: ' . $_POST['ref']);
            } else {
                echo '<div class="phdr"><a href="index.php?act=ads"><b>' . $lng['advertisement'] . '</b></a> | ' . $lng['delete'] . '</div>' .
                     '<div class="rmenu"><form action="index.php?act=ads&amp;mod=del&amp;id=' . $id . '" method="post">' .
                     '<p>' . $lng['link_deletion_warning'] . '</p>' .
                     '<p><input type="submit" name="submit" value="' . $lng['delete'] . '" /></p>' .
                     '<input type="hidden" name="ref" value="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '" />' .
                     '</form></div>' .
                     '<div class="phdr"><a href="' . htmlspecialchars($_SERVER['HTTP_REFERER']) . '">' . $lng['cancel'] . '</a></div>';
            }
        }
        break;

    case 'clear':
        /*
        -----------------------------------------------------------------
        Очистка базы от неактивных ссылок
        -----------------------------------------------------------------
        */
        if (isset($_POST['submit'])) {
            mysql_query("DELETE FROM `cms_ads` WHERE `to` = '1'");
            mysql_query("OPTIMIZE TABLE `cms_ads`");
            header('location: index.php?act=ads');
        } else {
            echo '<div class="phdr"><a href="index.php?act=ads"><b>' . $lng['advertisement'] . '</b></a> | ' . $lng['links_delete_hidden'] . '</div>' .
                 '<div class="menu"><form method="post" action="index.php?act=ads&amp;mod=clear">' .
                 '<p>' . $lng['link_clear_warning'] . '</p>' .
                 '<p><input type="submit" name="submit" value="' . $lng['delete'] . '" />' .
                 '</p></form></div>' .
                 '<div class="phdr"><a href="index.php?act=ads">' . $lng['cancel'] . '</a></div>';
        }
        break;

    case 'show':
        /*
        -----------------------------------------------------------------
        Восстанавливаем / скрываем ссылку
        -----------------------------------------------------------------
        */
        if ($id) {
            $req = mysql_query("SELECT * FROM `cms_ads` WHERE `id` = '$id'");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                mysql_query("UPDATE `cms_ads` SET `to`='" . ($res['to'] ? 0 : 1) . "' WHERE `id` = '$id'");
            }
        }
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        break;

    default:
        /*
        -----------------------------------------------------------------
        Главное меню модуля управления рекламой
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['advertisement'] . '</div>';
        $array_type = array(
            $lng['links_armt_over_logo'],
            $lng['links_armt_under_usermenu'],
            $lng['links_armt_over_counters'],
            $lng['links_armt_under_counters']
        );
        $array_placing = array(
            $lng['link_add_placing_all'],
            $lng['link_add_placing_front'],
            $lng['link_add_placing_child']
        );
        $array_show = array(
            $lng['to_all'],
            $lng['to_guest'],
            $lng['to_users']
        );
        $type = isset($_GET['type']) ? intval($_GET['type']) : 0;
        $array_menu = array(
            (!$type ? $lng['links_armt_over_logo'] : '<a href="index.php?act=ads">' . $lng['links_armt_over_logo'] . '</a>'),
            ($type == 1 ? $lng['links_armt_under_usermenu'] : '<a href="index.php?act=ads&amp;type=1">' . $lng['links_armt_under_usermenu'] . '</a>'),
            ($type == 2 ? $lng['links_armt_over_counters'] : '<a href="index.php?act=ads&amp;type=2">' . $lng['links_armt_over_counters'] . '</a>'),
            ($type == 3 ? $lng['links_armt_under_counters'] : '<a href="index.php?act=ads&amp;type=3">' . $lng['links_armt_under_counters'] . '</a>')
        );
        echo '<div class="topmenu">' . functions::display_menu($array_menu) . '</div>';
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ads` WHERE `type` = '$type'"), 0);
        if ($total) {
            $req = mysql_query("SELECT * FROM `cms_ads` WHERE `type` = '$type' ORDER BY `mesto` ASC LIMIT $start,$kmess");
            $i = 0;
            while ($res = mysql_fetch_assoc($req)) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $name = str_replace('|', '; ', $res['name']);
                $name = htmlentities($name, ENT_QUOTES, 'UTF-8');
                // Если был задан цвет, то применяем
                if (!empty($res['color']))
                    $name = '<span style="color:#' . $res['color'] . '">' . $name . '</span>';
                // Если было задано начертание шрифта, то применяем
                $font = $res['bold'] ? 'font-weight: bold;' : false;
                $font .= $res['italic'] ? ' font-style:italic;' : false;
                $font .= $res['underline'] ? ' text-decoration:underline;' : false;
                if ($font)
                    $name = '<span style="' . $font . '">' . $name . '</span>';
                ////////////////////////////////////////////////////////////
                // Выводим рекламмную ссылку с атрибутами                 //
                ////////////////////////////////////////////////////////////
                echo '<p><img src="../images/' . ($res['to'] ? 'red' : 'green') . '.gif" width="16" height="16" class="left"/>&#160;' .
                     '<a href="' . htmlspecialchars($res['link']) . '">' . htmlspecialchars($res['link']) . '</a>&nbsp;[' . $res['count'] . ']<br />' . $name . '</p>';
                $menu = array(
                    '<a href="index.php?act=ads&amp;mod=up&amp;id=' . $res['id'] . '">' . $lng['up'] . '</a>',
                    '<a href="index.php?act=ads&amp;mod=down&amp;id=' . $res['id'] . '">' . $lng['down'] . '</a>',
                    '<a href="index.php?act=ads&amp;mod=edit&amp;id=' . $res['id'] . '">' . $lng['edit'] . '</a>',
                    '<a href="index.php?act=ads&amp;mod=del&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a>',
                    '<a href="index.php?act=ads&amp;mod=show&amp;id=' . $res['id'] . '">' . ($res['to'] ? $lng['to_show'] : $lng['hide']) . '</a>'
                );
                echo '<div class="sub">' .
                     '<div>' . functions::display_menu($menu) . '</div>' .
                     '<p><span class="gray">' . $lng['installation_date'] . ':</span> ' . functions::display_date($res['time']) . '<br />' .
                     '<span class="gray">' . $lng['placing'] . ':</span>&nbsp;' . $array_placing[$res['layout']] . '<br />' .
                     '<span class="gray">' . $lng['to_show'] . ':</span>&nbsp;' . $array_show[$res['view']];
                // Вычисляем условия договора на рекламу
                $agreement = array();
                $remains = array();
                if (!empty($res['count_link'])) {
                    $agreement[] = $res['count_link'] . ' ' . $lng['transitions_n'];
                    $remains_count = $res['count_link'] - $res['count'];
                    if ($remains_count > 0)
                        $remains[] = $remains_count . ' ' . $lng['transitions_n'];
                }
                if (!empty($res['day'])) {
                    $agreement[] = functions::timecount($res['day'] * 86400);
                    $remains_count = $res['day'] * 86400 - (time() - $res['time']);
                    if ($remains_count > 0)
                        $remains[] = functions::timecount($remains_count);
                }
                // Если был договор, то выводим описание
                if ($agreement) {
                    echo '<br /><span class="gray">' . $lng['agreement'] . ':</span>&nbsp;' . implode($agreement, ', ');
                    if ($remains)
                        echo '<br /><span class="gray">' . $lng['remains'] . ':</span> ' . implode($remains, ', ');
                }
                echo ($res['show'] ? '<br /><span class="red"><b>' . $lng['link_direct'] . '</b></span>' : '') . '</p></div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('index.php?act=ads&amp;type=' . $type . '&amp;', $start, $total, $kmess) . '</div>' .
                 '<p><form action="index.php?act=ads&amp;type=' . $type . '" method="post">' .
                 '<input type="text" name="page" size="2"/>' .
                 '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="index.php?act=ads&amp;mod=edit">' . $lng['link_add'] . '</a><br />' .
             '<a href="index.php?act=ads&amp;mod=clear">' . $lng['links_delete_hidden'] . '</a><br />' .
             '<a href="index.php">' . $lng['admin_panel'] . '</a></p>';
}
?>
