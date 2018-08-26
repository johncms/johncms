<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');
$lng_set = core::load_lng('settings');
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

$menu = array(
    (!$mod ? '<b>' . $lng['common_settings'] . '</b>' : '<a href="profile.php?act=settings">' . $lng['common_settings'] . '</a>'),
    ($mod == 'forum' ? '<b>' . $lng['forum'] . '</b>' : '<a href="profile.php?act=settings&amp;mod=forum">' . $lng['forum'] . '</a>'),
    ($mod == 'mail' ? '<b>' . $lng['mail'] . '</b>' : '<a href="profile.php?act=settings&amp;mod=mail">' . $lng['mail'] . '</a>'),
);

/*
-----------------------------------------------------------------
Пользовательские настройки
-----------------------------------------------------------------
*/
switch ($mod) {
    case 'mail':
        echo '<div class="phdr"><b>' . $lng['settings'] . '</b> | ' . $lng['mail'] . '</div>' .
            '<div class="topmenu">' . functions::display_menu($menu) . '</div>';

        $set_mail_user = unserialize($datauser['set_mail']);
        if (isset($_POST['submit'])) {
            $set_mail_user['access'] = isset($_POST['access']) && $_POST['access'] >= 0 && $_POST['access'] <= 2 ? abs(intval($_POST['access'])) : 0;
            $stmt = $db->prepare("UPDATE `users` SET `set_mail` = ? WHERE `id` = '$user_id'");
            $stmt->execute([
                serialize($set_mail_user)
            ]);
        }

        echo '<form method="post" action="profile.php?act=settings&amp;mod=mail">' .
            '<div class="menu">' .
            '<strong>' . $lng_profile['write_messages'] . '</strong><br />' .
            '<input type="radio" value="0" name="access" ' . (!$set_mail_user['access'] ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['write_all'] . '<br />' .
            '<input type="radio" value="1" name="access" ' . ($set_mail_user['access'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['write_contacts'] . '<br />' .
            '<input type="radio" value="2" name="access" ' . ($set_mail_user['access'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng_profile['write_friends'] .
            '<br/><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
            '<div class="phdr">&#160;</div>';
        break;

    case 'forum':
        /*
        -----------------------------------------------------------------
        Настройки Форума
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>' . $lng['settings'] . '</b> | ' . $lng['forum'] . '</div>' .
            '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        $set_forum = array();
        $set_forum = unserialize($datauser['set_forum']);
        if (isset($_POST['submit'])) {
            $set_forum['farea'] = isset($_POST['farea']);
            $set_forum['upfp'] = isset($_POST['upfp']);
            $set_forum['preview'] = isset($_POST['preview']);
            $set_forum['postclip'] = isset($_POST['postclip']) ? intval($_POST['postclip']) : 1;
            if ($set_forum['postclip'] < 0 || $set_forum['postclip'] > 2) {
                $set_forum['postclip'] = 1;
            }
            $stmt = $db->prepare("UPDATE `users` SET `set_forum` = ? WHERE `id` = '$user_id'");
            $stmt->execute([
                serialize($set_forum)
            ]);
            echo '<div class="gmenu">' . $lng['settings_saved'] . '</div>';
        } elseif (isset($_GET['reset']) || empty($set_forum)) {
            $set_forum = array();
            $set_forum['farea'] = 0;
            $set_forum['upfp'] = 0;
            $set_forum['preview'] = 1;
            $set_forum['postclip'] = 1;
            $stmt = $db->prepare("UPDATE `users` SET `set_forum` = ? WHERE `id` = '$user_id'");
            $stmt->execute([
                serialize($set_forum)
            ]);
            echo '<div class="rmenu">' . $lng['settings_default'] . '</div>';
        }
        echo '<form action="profile.php?act=settings&amp;mod=forum" method="post">' .
            '<div class="menu"><p><h3>' . $lng_set['main_settings'] . '</h3>' .
            '<input name="upfp" type="checkbox" value="1" ' . ($set_forum['upfp'] ? 'checked="checked"' : '') . ' />&#160;' . $lng_set['sorting_return'] . '<br/>' .
            '<input name="farea" type="checkbox" value="1" ' . ($set_forum['farea'] ? 'checked="checked"' : '') . ' />&#160;' . $lng_set['field_on'] . '<br/>' .
            '<input name="preview" type="checkbox" value="1" ' . ($set_forum['preview'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['preview'] . '<br/>' .
            '</p><p><h3>' . $lng_set['clip_first_post'] . '</h3>' .
            '<input type="radio" value="2" name="postclip" ' . ($set_forum['postclip'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['always'] . '<br />' .
            '<input type="radio" value="1" name="postclip" ' . ($set_forum['postclip'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['in_not_read'] . '<br />' .
            '<input type="radio" value="0" name="postclip" ' . (!$set_forum['postclip'] ? 'checked="checked"' : '') . '/>&#160;' . $lng_set['never'] .
            '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
            '<div class="phdr"><a href="profile.php?act=settings&amp;mod=forum&amp;reset">' . $lng['reset_settings'] . '</a></div>';
        break;

    default:
        echo '<div class="phdr"><b>' . $lng['settings'] . '</b> | ' . $lng['common_settings'] . '</div>' .
            '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
        if (isset($_POST['submit'])) {
            /*
            -----------------------------------------------------------------
            Записываем новые настройки, заданные пользователем
            -----------------------------------------------------------------
            */
            $set_user['timeshift'] = isset($_POST['timeshift']) ? intval($_POST['timeshift']) : 0;
            $set_user['avatar'] = isset($_POST['avatar']);
            $set_user['smileys'] = isset($_POST['smileys']);
            $set_user['translit'] = isset($_POST['translit']);
            $set_user['direct_url'] = isset($_POST['direct_url']);
            $set_user['field_h'] = isset($_POST['field_h']) ? abs(intval($_POST['field_h'])) : 3;
            $set_user['kmess'] = isset($_POST['kmess']) ? abs(intval($_POST['kmess'])) : 10;
            if ($set_user['timeshift'] < -12) {
                $set_user['timeshift'] = -12;
            } elseif ($set_user['timeshift'] > 12) {
                $set_user['timeshift'] = 12;
            }
            if ($set_user['kmess'] < 5) {
                $set_user['kmess'] = 5;
            } elseif ($set_user['kmess'] > 99) {
                $set_user['kmess'] = 99;
            }
            if ($set_user['field_h'] < 1) {
                $set_user['field_h'] = 1;
            } elseif ($set_user['field_h'] > 9) {
                $set_user['field_h'] = 9;
            }

            // Устанавливаем скин
            $theme_list = [];
            foreach (glob('../theme/*/*.css') as $val) {
                $dir = explode('/', dirname($val));
                $theme_list[] = array_pop($dir);
            }
            $set_user['skin'] = isset($_POST['skin']) && in_array($_POST['skin'], $theme_list) ? $_POST['skin'] : $set['skindef'];

            // Устанавливаем язык
            $lng_select = isset($_POST['iso']) ? trim($_POST['iso']) : false;
            if ($lng_select && array_key_exists($lng_select, core::$lng_list)) {
                $set_user['lng'] = $lng_select;
                unset($_SESSION['lng']);
            }

            // Записываем настройки
            $stmt = $db->prepare("UPDATE `users` SET `set_user` = ? WHERE `id` = '$user_id'");
            $stmt->execute([
                serialize($set_user)
            ]);
            $_SESSION['set_ok'] = 1;
            header('Location: profile.php?act=settings'); exit;
        } elseif (isset($_GET['reset']) || empty($set_user)) {
            /*
            -----------------------------------------------------------------
            Задаем настройки по-умолчанию
            -----------------------------------------------------------------
            */
            $db->exec("UPDATE `users` SET `set_user` = '' WHERE `id` = '$user_id'");
            $_SESSION['reset_ok'] = 1;
            header('Location: profile.php?act=settings'); exit;
        }

        /*
        -----------------------------------------------------------------
        Форма ввода пользовательских настроек
        -----------------------------------------------------------------
        */
        if (isset($_SESSION['set_ok'])) {
            echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
            unset($_SESSION['set_ok']);
        }
        if (isset($_SESSION['reset_ok'])) {
            echo '<div class="rmenu">' . $lng['settings_default'] . '</div>';
            unset($_SESSION['reset_ok']);
        }
        echo '<form action="profile.php?act=settings" method="post" >' .
            '<div class="menu"><p><h3>' . $lng['settings_clock'] . '</h3>' .
            '<input type="text" name="timeshift" size="2" maxlength="3" value="' . core::$user_set['timeshift'] . '"/> ' . $lng['settings_clock_shift'] . ' (+-12)<br />' .
            '<span style="font-weight:bold; background-color:#CCC">' . date("H:i", time() + (core::$system_set['timeshift'] + core::$user_set['timeshift']) * 3600) . '</span> ' . $lng['system_time'] .
            '</p><p><h3>' . $lng['system_functions'] . '</h3>' .
            '<input name="direct_url" type="checkbox" value="1" ' . (core::$user_set['direct_url'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['direct_url'] . '<br />' .
            '<input name="avatar" type="checkbox" value="1" ' . (core::$user_set['avatar'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['avatars'] . '<br/>' .
            '<input name="smileys" type="checkbox" value="1" ' . (core::$user_set['smileys'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['smileys'] . '<br/>' .
            '</p><p><h3>' . $lng['text_input'] . '</h3>' .
            '<input type="text" name="field_h" size="2" maxlength="1" value="' . core::$user_set['field_h'] . '"/> ' . $lng['field_height'] . ' (1-9)<br />';
        if (core::$lng_iso == 'ru' || core::$lng_iso == 'uk') echo '<input name="translit" type="checkbox" value="1" ' . (core::$user_set['translit'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['translit'];
        echo '</p><p><h3>' . $lng['apperance'] . '</h3>' .
            '<input type="text" name="kmess" size="2" maxlength="2" value="' . core::$user_set['kmess'] . '"/> ' . $lng['lines_on_page'] . ' (5-99)' .
            '</p>';

        // Выбор темы оформления
        echo '<p><h3>' . $lng['design_template'] . '</h3><select name="skin">';
        foreach (glob('../theme/*/*.css') as $val) {
            $dir = explode('/', dirname($val));
            $theme = array_pop($dir);
            echo '<option' . (core::$user_set['skin'] == $theme ? ' selected="selected">' : '>') . $theme . '</option>';
        }
        echo '</select></p>';

        // Выбор языка
        if (count(core::$lng_list) > 1) {
            echo '<p><h3>' . $lng['language_select'] . '</h3>';
            $user_lng = isset(core::$user_set['lng']) ? core::$user_set['lng'] : core::$lng_iso;
            foreach (core::$lng_list as $key => $val) {
                echo '<div><input type="radio" value="' . $key . '" name="iso" ' . ($key == $user_lng ? 'checked="checked"' : '') . '/>&#160;' .
                    (file_exists('../images/flags/' . $key . '.gif') ? '<img src="../images/flags/' . $key . '.gif" alt=""/>&#160;' : '') .
                    $val .
                    ($key == core::$system_set['lng'] ? ' <small class="red">[' . $lng['default'] . ']</small>' : '') .
                    '</div>';
            }
            echo '</p>';
        }

        echo '<p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
            '<div class="phdr"><a href="profile.php?act=settings&amp;reset">' . $lng['reset_settings'] . '</a></div>';
}
