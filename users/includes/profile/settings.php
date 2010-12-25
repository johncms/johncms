<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
$lng_set = $core->load_lng('set');
$textl = $lng['settings'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Проверяем права доступа
-----------------------------------------------------------------
*/
if ($user['id'] != $user_id) {
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}

$menu = array (
    (!$mod ? '<b>' . $lng['common_settings'] . '</b>' : '<a href="profile.php?act=settings">' . $lng['common_settings'] . '</a>'),
    ($mod == 'forum' ? '<b>' . $lng['forum'] . '</b>' : '<a href="profile.php?act=settings&amp;mod=forum">' . $lng['forum'] . '</a>'),
);

/*
-----------------------------------------------------------------
Пользовательские настройки
-----------------------------------------------------------------
*/
switch ($mod) {
    case 'forum':
        /*
        -----------------------------------------------------------------
        Настройки Форума
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>' . $lng['settings'] . '</b> | ' . $lng['forum'] . '</div>' .
            '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        $set_forum = array ();
        $set_forum = unserialize($datauser['set_forum']);
        if (isset($_POST['submit'])) {
            $set_forum['farea'] = isset($_POST['farea']) ? 1 : 0;
            $set_forum['upfp'] = isset($_POST['upfp']) ? 1 : 0;
            $set_forum['postclip'] = isset($_POST['postclip']) ? intval($_POST['postclip']) : 1;
            $set_forum['postcut'] = isset($_POST['postcut']) ? intval($_POST['postcut']) : 1;
            if ($set_forum['postclip'] < 0 || $set_forum['postclip'] > 2)
                $set_forum['postclip'] = 1;
            if ($set_forum['postcut'] < 0 || $set_forum['postcut'] > 3)
                $set_forum['postcut'] = 1;
            mysql_query("UPDATE `users` SET `set_forum` = '" . mysql_real_escape_string(serialize($set_forum)) . "' WHERE `id` = '$user_id'");
            echo '<div class="gmenu">' . $lng['settings_saved'] . '</div>';
        }
        if (isset($_GET['reset']) || empty($set_forum)) {
            $set_forum = array ();
            $set_forum['farea'] = 0;
            $set_forum['upfp'] = 0;
            $set_forum['postclip'] = 1;
            $set_forum['postcut'] = 2;
            mysql_query("UPDATE `users` SET `set_forum` = '" . mysql_real_escape_string(serialize($set_forum)) . "' WHERE `id` = '$user_id'");
            echo '<div class="rmenu">' . $lng['settings_default'] . '</div>';
        }
        echo '<form action="profile.php?act=settings&amp;mod=forum" method="post">' .
            '<div class="menu"><p><h3>' . $lng_set['main_settings'] . '</h3>' .
            '<input name="upfp" type="checkbox" value="1" ' . ($set_forum['upfp'] ? 'checked="checked"' : '') . ' />&#160;' . $lng_set['sorting_return'] . '<br/>' .
            '<input name="farea" type="checkbox" value="1" ' . ($set_forum['farea'] ? 'checked="checked"' : '') . ' />&#160;' . $lng_set['field_on'] . '<br/>' .
            '</p><p><h3>' . $lng_set['clip_first_post'] . '</h3>' .
            '<input type="radio" value="2" name="postclip" ' . ($set_forum['postclip'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['always'] . '<br />' .
            '<input type="radio" value="1" name="postclip" ' . ($set_forum['postclip'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['in_not_read'] . '<br />' .
            '<input type="radio" value="0" name="postclip" ' . (!$set_forum['postclip'] ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['never'] .
            '</p><p><h3>' . $lng_set['scrap_of_posts'] . '</h3>' .
            '<input type="radio" value="1" name="postcut" ' . ($set_forum['postcut'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['500_symbols'] . '<br />' .
            '<input type="radio" value="2" name="postcut" ' . ($set_forum['postcut'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['1000_symbols'] . '<br />' .
            '<input type="radio" value="3" name="postcut" ' . ($set_forum['postcut'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['3000_symbols'] . '<br />' .
            '<input type="radio" value="0" name="postcut" ' . (!$set_forum['postcut'] ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['not_to_cut_off'] . '<br />' .
            '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
            '<div class="phdr"><a href="profile.php?act=settings&amp;mod=forum&amp;reset">' . $lng['reset_settings'] . '</a></div>' .
            '<p><a href="../forum/index.php">' . $lng['to_forum'] . '</a></p>';
        break;

    default:
        /*
        -----------------------------------------------------------------
        Общие настройки
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>' . $lng['settings'] . '</b> | ' . $lng['common_settings'] . '</div>' .
            '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        $set_user = array ();
        $set_user = unserialize($datauser['set_user']);
        if (isset($_POST['submit'])) {
            $set_user['sdvig'] = isset($_POST['sdvig']) ? intval($_POST['sdvig']) : 0;
            $set_user['avatar'] = isset($_POST['avatar']) ? 1 : 0;
            $set_user['smileys'] = isset($_POST['smileys']) ? 1 : 0;
            $set_user['translit'] = isset($_POST['translit']) ? 1 : 0;
            $set_user['digest'] = isset($_POST['digest']) ? 1 : 0;
            $set_user['field_w'] = isset($_POST['field_w']) ? abs(intval($_POST['field_w'])) : 20;
            $set_user['field_h'] = isset($_POST['field_h']) ? abs(intval($_POST['field_h'])) : 3;
            $set_user['kmess'] = isset($_POST['kmess']) ? abs(intval($_POST['kmess'])) : 10;
            $set_user['quick_go'] = isset($_POST['quick_go']) ? 1 : 0;
            $set_user['gzip'] = isset($_POST['gzip']) ? 1 : 0;
            $set_user['online'] = isset($_POST['online']) ? 1 : 0;
            $set_user['movings'] = isset($_POST['movings']) ? 1 : 0;
            if ($set_user['sdvig'] < -12)
                $set_user['sdvig'] = -12;
            elseif ($set_user['sdvig'] > 12)
                $set_user['sdvig'] = 12;
            if ($set_user['kmess'] < 5)
                $set_user['kmess'] = 5;
            elseif ($set_user['kmess'] > 99)
                $set_user['kmess'] = 99;
            if ($set_user['field_w'] < 10)
                $set_user['field_w'] = 10;
            elseif ($set_user['field_w'] > 80)
                $set_user['field_w'] = 80;
            if ($set_user['field_h'] < 1)
                $set_user['field_h'] = 1;
            elseif ($set_user['field_h'] > 9)
                $set_user['field_h'] = 9;
            $set_user['skin'] = isset($_POST['skin']) ? functions::check($_POST['skin']) : 'default';
            $arr = array ();
            $dir = opendir('../theme');
            while ($skindef = readdir($dir)) {
                if (($skindef != '.') && ($skindef != '..') && ($skindef != '.svn'))
                    $arr[] = str_replace('.css', '', $skindef);
            }
            closedir($dir);
            if (!in_array($set_user['skin'], $arr))
                $set_user['skin'] = 'default';
            // Устанавливаем язык
            $lng_select = isset($_POST['lng']) ? intval($_POST['lng']) : false;
            if ($lng_select && $lng_select != $core->language_id) {
                $req = mysql_query("SELECT * FROM `cms_lng_list` WHERE `id` = '$lng_select'");
                if (mysql_num_rows($req)) {
                    $core->language_id = $lng_select;
                    $lng = $core->load_lng();
                    //$res = mysql_fetch_assoc($req);
                    //echo '<div class="gmenu">' . $lng['language_set'] . ': <b>' . $res['default'] . '</b></div>';
                }
            }
            // Записываем настройки в базу
            mysql_query("UPDATE `users` SET
                `set_user` = '" . mysql_real_escape_string(serialize($set_user)) . "',
                `set_language` = '" . $core->language_id . "'
                WHERE `id` = '$user_id'");
            echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
        }
        if (isset($_GET['reset']) || empty($set_user)) {
            $set_user = array ();
            $set_user['avatar'] = 1;
            $set_user['smileys'] = 1;
            $set_user['translit'] = 1;
            $set_user['quick_go'] = 1;
            $set_user['gzip'] = 1;
            $set_user['online'] = 1;
            $set_user['movings'] = 1;
            $set_user['digest'] = 0;
            $set_user['field_w'] = 20;
            $set_user['field_h'] = 3;
            $set_user['sdvig'] = 0;
            $set_user['kmess'] = 10;
            $set_user['skin'] = 'default';
            mysql_query("UPDATE `users` SET
                `set_user` = '" . mysql_real_escape_string(serialize($set_user)) . "',
                `set_language` = '0'
                WHERE `id` = '$user_id'
            ");
            $language = $set['language'];
            $lng = $core->load_lng();
            echo '<div class="rmenu">' . $lng['settings_default'] . '</div>';
        }
        // Форма ввода настроек
        echo '<form action="profile.php?act=settings" method="post" >' .
            '<div class="menu"><p><h3>' . $lng['settings_clock'] . '</h3>' .
            '<input type="text" name="sdvig" size="2" maxlength="3" value="' . $set_user['sdvig'] . '"/> ' . $lng['settings_clock_shift'] . ' (+-12)<br />' .
            '<span style="font-weight:bold; background-color:#CCC">' . date("H:i", $realtime + $set_user['sdvig'] * 3600) . '</span> ' . $lng['system_time'] .
            '</p><p><h3>' . $lng['system_functions'] . '</h3>' .
            '<input name="avatar" type="checkbox" value="1" ' . ($set_user['avatar'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['avatars'] . '<br/>' .
            '<input name="smileys" type="checkbox" value="1" ' . ($set_user['smileys'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['smileys'] . '<br/>' .
            '<input name="translit" type="checkbox" value="1" ' . ($set_user['translit'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['translit'] . '<br/>' .
            '<input name="digest" type="checkbox" value="1" ' . ($set_user['digest'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['digest'] .
            '</p><p><h3>' . $lng['text_input'] . '</h3>' .
            '<input type="text" name="field_w" size="2" maxlength="2" value="' . $set_user['field_w'] . '"/> ' . $lng['field_width'] . ' (10-80)<br />' .
            '<input type="text" name="field_h" size="2" maxlength="1" value="' . $set_user['field_h'] . '"/> ' . $lng['field_height'] . ' (1-9)<br />' .
            '</p><p><h3>' . $lng['apperance'] . '</h3>' .
            '<input type="text" name="kmess" size="2" maxlength="2" value="' . $set_user['kmess'] . '"/> ' . $lng['lines_on_page'] . ' (5-99)<br />' .
            '<input name="quick_go" type="checkbox" value="1" ' . ($set_user['quick_go'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['quick_jump'] . '<br />' .
            '<input name="gzip" type="checkbox" value="1" ' . ($set_user['gzip'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['gzip_show'] . '<br />' .
            '<input name="online" type="checkbox" value="1" ' . ($set_user['online'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['time_online'] . '<br />' .
            '<input name="movings" type="checkbox" value="1" ' . ($set_user['movings'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['transitions_counter'] .
            '</p><p><h3>' . $lng['design_template'] . '</h3><select name="skin">';
        // Выбор темы оформления
        $dir = opendir('../theme');
        while ($skindef = readdir($dir)) {
            if (($skindef != '.') && ($skindef != '..') && ($skindef != '.svn')) {
                $skindef = str_replace('.css', '', $skindef);
                echo '<option' . ($set_user['skin'] == $skindef ? ' selected="selected">' : '>') . $skindef . '</option>';
            }
        }
        closedir($dir);
        echo '</select></p>';
        // Выбор языка
        $req = mysql_query("SELECT * FROM `cms_lng_list` ORDER BY `name` ASC");
        if (mysql_num_rows($req) > 1) {
            echo '<p><h3>' . $lng['language_select'] . '</h3>';
            while ($res = mysql_fetch_assoc($req)) {
                echo '<div><input type="radio" value="' . $res['id'] . '" name="lng" ' . ($res['id'] == $core->language_id ? 'checked="checked"' : '') . '/>&#160;' . $res['name'] . '</div>';
            }
            echo '</p>';
        }
        echo '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
            '<div class="phdr"><a href="profile.php?act=settings&amp;reset">' . $lng['reset_settings'] . '</a></div>';
}
?>