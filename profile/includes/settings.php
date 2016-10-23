<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = _t('Settings');
require('../incfiles/head.php');

// Проверяем права доступа
if ($user['id'] != $user_id) {
    echo functions::display_error(_t('Access forbidden'));
    require('../incfiles/end.php');
    exit;
}

/** @var Interop\Container\ContainerInterface $container */
$container = App::getContainer();
$config = $container->get('config')['johncms'];

/** @var PDO $db */
$db = $container->get(PDO::class);

$menu = [
    (!$mod ? '<b>' . _t('General setting') . '</b>' : '<a href="?act=settings">' . _t('General setting') . '</a>'),
    ($mod == 'forum' ? '<b>' . _t('Forum') . '</b>' : '<a href="?act=settings&amp;mod=forum">' . _t('Forum') . '</a>'),
    ($mod == 'mail' ? '<b>' . _t('Mail') . '</b>' : '<a href="?act=settings&amp;mod=mail">' . _t('Mail') . '</a>'),
];

// Пользовательские настройки
switch ($mod) {
    case 'mail':
        echo '<div class="phdr"><b>' . _t('Settings') . '</b> | ' . _t('Mail') . '</div>' .
            '<div class="topmenu">' . implode(' | ', $menu) . '</div>';

        $set_mail_user = unserialize($datauser['set_mail']);

        if (isset($_POST['submit'])) {
            $set_mail_user['access'] = isset($_POST['access']) && $_POST['access'] >= 0 && $_POST['access'] <= 2 ? abs(intval($_POST['access'])) : 0;
            $db->prepare('UPDATE `users` SET `set_mail` = ? WHERE `id` = ?')->execute([
                serialize($set_mail_user),
                $user_id,
            ]);
        }

        echo '<form method="post" action="?act=settings&amp;mod=mail">' .
            '<div class="menu">' .
            '<strong>' . _t('Who can write you?') . '</strong><br />' .
            '<input type="radio" value="0" name="access" ' . (!$set_mail_user['access'] ? 'checked="checked"' : '') . '/>&#160;' . _t('All can write') . '<br />' .
            '<input type="radio" value="1" name="access" ' . ($set_mail_user['access'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only my contacts') . '<br />' .
            '<input type="radio" value="2" name="access" ' . ($set_mail_user['access'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only my friends') .
            '<br><p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
            '<div class="phdr">&#160;</div>';
        break;

    case 'forum':
        // Настройки Форума
        echo '<div class="phdr"><b>' . _t('Settings') . '</b> | ' . _t('Forum') . '</div>' .
            '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        $set_forum = [];
        $set_forum = unserialize($datauser['set_forum']);

        if (isset($_POST['submit'])) {
            $set_forum['farea'] = isset($_POST['farea']);
            $set_forum['upfp'] = isset($_POST['upfp']);
            $set_forum['preview'] = isset($_POST['preview']);
            $set_forum['postclip'] = isset($_POST['postclip']) ? intval($_POST['postclip']) : 1;

            if ($set_forum['postclip'] < 0 || $set_forum['postclip'] > 2) {
                $set_forum['postclip'] = 1;
            }

            $db->prepare('UPDATE `users` SET `set_forum` = ? WHERE `id` = ?')->execute([
                serialize($set_forum),
                $user_id,
            ]);

            echo '<div class="gmenu">' . _t('Settings saved successfully') . '</div>';
        }

        if (isset($_GET['reset']) || empty($set_forum)) {
            $set_forum = [];
            $set_forum['farea'] = 0;
            $set_forum['upfp'] = 0;
            $set_forum['preview'] = 1;
            $set_forum['postclip'] = 1;
            $db->prepare('UPDATE `users` SET `set_forum` = ? WHERE `id` = ?')->execute([
                serialize($set_forum),
                $user_id,
            ]);
            echo '<div class="rmenu">' . _t('Default settings are set') . '</div>';
        }

        echo '<form action="?act=settings&amp;mod=forum" method="post">' .
            '<div class="menu"><p><h3>' . _t('Basic settings') . '</h3>' .
            '<input name="upfp" type="checkbox" value="1" ' . ($set_forum['upfp'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Inverse sorting') . '<br>' .
            '<input name="farea" type="checkbox" value="1" ' . ($set_forum['farea'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Use the form of a quick answer') . '<br>' .
            '<input name="preview" type="checkbox" value="1" ' . ($set_forum['preview'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Preview of messages') . '<br>' .
            '</p><p><h3>' . _t('Attach first post') . '</h3>' .
            '<input type="radio" value="2" name="postclip" ' . ($set_forum['postclip'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . _t('Always') . '<br />' .
            '<input type="radio" value="1" name="postclip" ' . ($set_forum['postclip'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('In unread topics') . '<br />' .
            '<input type="radio" value="0" name="postclip" ' . (!$set_forum['postclip'] ? 'checked="checked"' : '') . '/>&#160;' . _t('Never') .
            '</p><p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
            '<div class="phdr"><a href="?act=settings&amp;mod=forum&amp;reset">' . _t('Reset settings') . '</a></div>';
        break;

    default:
        echo '<div class="phdr"><b>' . _t('Settings') . '</b> | ' . _t('General setting') . '</div>' .
            '<div class="topmenu">' . implode(' | ', $menu) . '</div>';

        if (isset($_POST['submit'])) {
            // Записываем новые настройки, заданные пользователем
            $set_user['timeshift'] = isset($_POST['timeshift']) ? intval($_POST['timeshift']) : 0;
            $set_user['avatar'] = isset($_POST['avatar']);
            $set_user['smileys'] = isset($_POST['smileys']);
            $set_user['translit'] = isset($_POST['translit']);
            $set_user['digest'] = isset($_POST['digest']);
            $set_user['direct_url'] = isset($_POST['direct_url']);
            $set_user['field_h'] = isset($_POST['field_h']) ? abs(intval($_POST['field_h'])) : 3;
            $set_user['kmess'] = isset($_POST['kmess']) ? abs(intval($_POST['kmess'])) : 10;
            $set_user['quick_go'] = isset($_POST['quick_go']);

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

            if ($set_user['field_w'] < 10) {
                $set_user['field_w'] = 10;
            } elseif ($set_user['field_w'] > 80) {
                $set_user['field_w'] = 80;
            }

            if ($set_user['field_h'] < 1) {
                $set_user['field_h'] = 1;
            } elseif ($set_user['field_h'] > 9) {
                $set_user['field_h'] = 9;
            }

            // Устанавливаем скин
            foreach (glob('../theme/*/*.css') as $val) {
                $theme_list[] = array_pop(explode('/', dirname($val)));
            }

            $set_user['skin'] = isset($_POST['skin']) && in_array($_POST['skin'], $theme_list) ? functions::check($_POST['skin']) : $config['skindef'];

            // Устанавливаем язык
            $lng_select = isset($_POST['iso']) ? trim($_POST['iso']) : false;

            if ($lng_select && array_key_exists($lng_select, core::$lng_list)) {
                $set_user['lng'] = $lng_select;
                unset($_SESSION['lng']);
            }

            // Записываем настройки
            $db->prepare('UPDATE `users` SET `set_user` = ? WHERE `id` = ?')->execute([serialize($set_user), $user_id]);
            $_SESSION['set_ok'] = 1;
            header('Location: ?act=settings');
            exit;
        } elseif (isset($_GET['reset']) || empty($set_user)) {
            // Задаем настройки по-умолчанию
            $db->exec("UPDATE `users` SET `set_user` = '' WHERE `id` = '$user_id'");
            $_SESSION['reset_ok'] = 1;
            header('Location: ?act=settings');
            exit;
        }

        // Форма ввода пользовательских настроек
        if (isset($_SESSION['set_ok'])) {
            echo '<div class="rmenu">' . _t('Settings saved successfully') . '</div>';
            unset($_SESSION['set_ok']);
        }

        if (isset($_SESSION['reset_ok'])) {
            echo '<div class="rmenu">' . _t('Default settings are set') . '</div>';
            unset($_SESSION['reset_ok']);
        }

        echo '<form action="?act=settings" method="post" >' .
            '<div class="menu"><p><h3>' . _t('Time settings') . '</h3>' .
            '<input type="text" name="timeshift" size="2" maxlength="3" value="' . core::$user_set['timeshift'] . '"/> ' . _t('Shift of time') . ' (+-12)<br />' .
            '<span style="font-weight:bold; background-color:#CCC">' . date("H:i",
                time() + ($config['timeshift'] + core::$user_set['timeshift']) * 3600) . '</span> ' . _t('System time') .
            '</p><p><h3>' . _t('System Functions') . '</h3>' .
            '<input name="direct_url" type="checkbox" value="1" ' . (core::$user_set['direct_url'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Direct URL') . '<br />' .
            '<input name="avatar" type="checkbox" value="1" ' . (core::$user_set['avatar'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Avatars') . '<br>' .
            '<input name="smileys" type="checkbox" value="1" ' . (core::$user_set['smileys'] ? 'checked="checked"' : '') . ' />&#160;' . _t('Smilies') . '<br>' .
            '</p><p><h3>' . _t('Text entering') . '</h3>' .
            '<input type="text" name="field_h" size="2" maxlength="1" value="' . core::$user_set['field_h'] . '"/> ' . _t('Height of field') . ' (1-9)<br />';

        echo '</p><p><h3>' . _t('Appearance') . '</h3>' .
            '<input type="text" name="kmess" size="2" maxlength="2" value="' . core::$user_set['kmess'] . '"/> ' . _t('Size of Lists') . ' (5-99)' .
            '</p>';

        // Выбор темы оформления
        echo '<p><h3>' . _t('Theme') . '</h3><select name="skin">';

        foreach (glob('../theme/*/*.css') as $val) {
            $dir = explode('/', dirname($val));
            $theme = array_pop($dir);
            echo '<option' . (core::$user_set['skin'] == $theme ? ' selected="selected">' : '>') . $theme . '</option>';
        }

        echo '</select></p>';

        // Выбор языка
        if (count(core::$lng_list) > 1) {
            echo '<p><h3>' . _t('Select Language') . '</h3>';
            $user_lng = isset(core::$user_set['lng']) ? core::$user_set['lng'] : core::$lng_iso;
            foreach (core::$lng_list as $key => $val) {
                echo '<div><input type="radio" value="' . $key . '" name="iso" ' . ($key == $user_lng ? 'checked="checked"' : '') . '/>&#160;' .
                    (file_exists('../images/flags/' . $key . '.gif') ? '<img src="../images/flags/' . $key . '.gif" alt=""/>&#160;' : '') .
                    $val .
                    ($key == $config['lng'] ? ' <small class="red">[' . _t('Site Default') . ']</small>' : '') .
                    '</div>';
            }
            echo '</p>';
        }

        echo '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
            '<div class="phdr"><a href="?act=settings&amp;reset">' . _t('Reset Settings') . '</a></div>';
}
