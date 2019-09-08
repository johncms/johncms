<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = _t('Settings');
require('../system/head.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\UserConfig $userConfig */
$userConfig = $systemUser->getConfig();

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

// Проверяем права доступа
if ($user['id'] != $systemUser->id) {
    echo $tools->displayError(_t('Access forbidden'));
    require('../system/end.php');
    exit;
}

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

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

        $set_mail_user = unserialize($systemUser->set_mail);

        if (isset($_POST['submit'])) {
            $set_mail_user['access'] = isset($_POST['access']) && $_POST['access'] >= 0 && $_POST['access'] <= 2 ? abs(intval($_POST['access'])) : 0;
            $db->prepare('UPDATE `users` SET `set_mail` = ? WHERE `id` = ?')->execute([
                serialize($set_mail_user),
                $systemUser->id,
            ]);
        }

        echo '<form method="post" action="?act=settings&amp;mod=mail">' .
            '<div class="menu">' .
            '<strong>' . _t('Who can write you?') . '</strong><br />' .
            '<input type="radio" value="0" name="access" ' . (!$set_mail_user['access'] ? 'checked="checked"' : '') . '/>&#160;' . _t('All can write') . '<br />' .
            '<input type="radio" value="1" name="access" ' . ($set_mail_user['access'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . _t('Only my contacts') .
            '<br><p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
            '<div class="phdr">&#160;</div>';
        break;

    case 'forum':
        // Настройки Форума
        echo '<div class="phdr"><b>' . _t('Settings') . '</b> | ' . _t('Forum') . '</div>' .
            '<div class="topmenu">' . implode(' | ', $menu) . '</div>';
        $set_forum = [];
        $set_forum = unserialize($systemUser->set_forum);

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
                $systemUser->id,
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
                $systemUser->id,
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
            $set_user = $userConfig->getArrayCopy();

            // Записываем новые настройки, заданные пользователем
            $set_user['timeshift'] = isset($_POST['timeshift']) ? intval($_POST['timeshift']) : 0;
            $set_user['directUrl'] = isset($_POST['directUrl']);
            $set_user['youtube'] = isset($_POST['youtube']);
            $set_user['fieldHeight'] = isset($_POST['fieldHeight']) ? abs(intval($_POST['fieldHeight'])) : 3;
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

            if ($set_user['fieldHeight'] < 1) {
                $set_user['fieldHeight'] = 1;
            } elseif ($set_user['fieldHeight'] > 9) {
                $set_user['fieldHeight'] = 9;
            }

            // Устанавливаем скин
            foreach (glob('../theme/*/*.css') as $val) {
                $theme_list[] = array_pop(explode('/', dirname($val)));
            }

            $set_user['skin'] = isset($_POST['skin']) && in_array($_POST['skin'], $theme_list) ? htmlspecialchars(trim($_POST['skin'])) : $config['skindef'];

            // Устанавливаем язык
            $lng_select = isset($_POST['iso']) ? trim($_POST['iso']) : false;

            if ($lng_select && array_key_exists($lng_select, $config->lng_list)) {
                $set_user['lng'] = $lng_select;
                $_SESSION['lng'] = $lng_select;
            }

            // Записываем настройки
            $db->prepare('UPDATE `users` SET `set_user` = ? WHERE `id` = ?')->execute([serialize($set_user), $systemUser->id]);
            $_SESSION['set_ok'] = 1;
            header('Location: ?act=settings');
            exit;
        } elseif (isset($_GET['reset'])) {
            // Задаем настройки по-умолчанию
            $db->exec("UPDATE `users` SET `set_user` = '' WHERE `id` = " . $systemUser->id);
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
            '<input type="text" name="timeshift" size="2" maxlength="3" value="' . $userConfig->timeshift . '"/> ' . _t('Shift of time') . ' (+-12)<br />' .
            '<span style="font-weight:bold; background-color:#CCC">' . date("H:i",
                time() + ($config['timeshift'] + $userConfig->timeshift) * 3600) . '</span> ' . _t('System time') .
            '</p><p><h3>' . _t('System Functions') . '</h3>' .
            '<input name="directUrl" type="checkbox" value="1" ' . ($userConfig->directUrl ? 'checked="checked"' : '') . ' />&#160;' . _t('Direct URL') . '<br />' .
            '<input name="youtube" type="checkbox" value="1" ' . ($userConfig->youtube ? 'checked="checked"' : '') . ' />&#160;' . _t('Youtube Player') . '<br />' .
            '</p><p><h3>' . _t('Text entering') . '</h3>' .
            '<input type="text" name="fieldHeight" size="2" maxlength="1" value="' . $userConfig->fieldHeight . '"/> ' . _t('Height of field') . ' (1-9)<br />';

        echo '</p><p><h3>' . _t('Appearance') . '</h3>';
        // Выбор темы оформления
        echo '<select name="skin">';

        foreach (glob('../theme/*/*.css') as $val) {
            $dir = explode('/', dirname($val));
            $theme = array_pop($dir);
            echo '<option' . ($userConfig->skin == $theme ? ' selected="selected">' : '>') . $theme . '</option>';
        }

        echo '</select> ' . _t('Theme') . '</p>';
        echo '<p><input type="text" name="kmess" size="2" maxlength="2" value="' . $userConfig->kmess . '"/> ' . _t('Size of Lists') . ' (5-99)' .
            '</p>';

        // Выбор языка
        if (count($config->lng_list) > 1) {
            echo '<p><h3>' . _t('Select Language') . '</h3>';
            $user_lng = isset($userConfig['lng']) ? $userConfig['lng'] : $config->lng;

            foreach ($config->lng_list as $key => $val) {
                echo '<div><input type="radio" value="' . $key . '" name="iso" ' . ($key == $user_lng ? 'checked="checked"' : '') . '/>&#160;' .
                    $tools->getFlag($key) . $val .
                    ($key == $config['lng'] ? ' <small class="red">[' . _t('Site Default') . ']</small>' : '') .
                    '</div>';
            }

            echo '</p>';
        }

        echo '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p></div></form>' .
            '<div class="phdr"><a href="?act=settings&amp;reset">' . _t('Reset Settings') . '</a></div>';
}
