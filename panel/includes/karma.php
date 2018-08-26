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
if ($rights < 7) {
    header('Location: ' . $set['homeurl'] . '/?err'); exit;
}

if ($rights == 9 && $do == 'clean') {
    if (isset($_GET['yes'])) {
        $db->exec("TRUNCATE TABLE `karma_users`");
        $db->exec("UPDATE `users` SET `karma_plus`='0', `karma_minus`='0'");
        $db->query("OPTIMIZE TABLE `karma_users`, `users`");
        echo '<div class="gmenu">' . $lng['karma_cleared'] . '</div>';
    } else {
        echo '<div class="rmenu"><p>' . $lng['karma_clear_confirmation'] . '<br/>' .
            '<a href="index.php?act=karma&amp;do=clean&amp;yes">' . $lng['delete'] . '</a> | ' .
            '<a href="index.php?act=karma">' . $lng['cancel'] . '</a></p></div>';
    }
}
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['karma'] . '</div>';
$settings = unserialize($set['karma']);
if (isset($_POST['submit'])) {
    $settings['karma_points'] = isset($_POST['karma_points']) ? abs(intval($_POST['karma_points'])) : 0;
    $settings['karma_time'] = isset($_POST['karma_time']) ? abs(intval($_POST['karma_time'])) : 0;
    $settings['forum'] = isset($_POST['forum']) ? abs(intval($_POST['forum'])) : 0;
    $settings['time'] = isset($_POST['time']) ? abs(intval($_POST['time'])) : 0;
    $settings['on'] = isset($_POST['on']) ? 1 : 0;
    $settings['adm'] = isset($_POST['adm']) ? 1 : 0;
    $settings['karma_time'] = $settings['time'] ? $settings['karma_time'] * 3600 : $settings['karma_time'] * 86400;
    $stmt = $db->prepare("UPDATE `cms_settings` SET `val` = ? WHERE `key` = 'karma'");
    $stmt->execute([
        serialize($settings)
    ]);
    echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
}
$settings['karma_time'] = $settings['time'] ? $settings['karma_time'] / 3600 : $settings['karma_time'] / 86400;
echo '<form action="index.php?act=karma" method="post"><div class="menu">' .
    '<p><h3>' . $lng['karma_votes_per_day'] . '</h3>' .
    '<input type="text" name="karma_points" value="' . $settings['karma_points'] . '"/></p>' .
    '<p><h3>' . $lng['karma_restrictions'] . '</h3>' .
    '<input type="text" name="forum" value="' . $settings['forum'] . '" size="4"/>&#160;' . $lng['forum_posts'] . '<br />' .
    '<input type="text" name="karma_time" value="' . $settings['karma_time'] . '" size="4"/>&#160;' . $lng['site_spent'] . '<br />' .
    '&#160;<input name="time" type="radio" value="1"' . ($settings['time'] ? ' checked="checked"' : '') . '/>&#160;' . $lng['hours'] . '<br />' .
    '&#160;<input name="time" type="radio" value="0"' . (!$settings['time'] ? ' checked="checked"' : '') . '/>&#160;' . $lng['days'] . '</p>' .
    '<p><h3>' . $lng['general_settings'] . '</h3>' .
    '<input type="checkbox" name="on"' . ($settings['on'] ? ' checked="checked"' : '') . '/> ' . $lng['module_on'] . '<br />' .
    '<input type="checkbox" name="adm"' . ($settings['adm'] ? ' checked="checked"' : '') . '/> ' . $lng['karma_admin_disable'] . '</p>' .
    '<p><input type="submit" value="' . $lng['save'] . '" name="submit" /></p></div>' .
    '</form><div class="phdr">' . ($rights == 9 ? '<a href="index.php?act=karma&amp;do=clean">' . $lng['karma_reset'] . '</a>' : '<br />') . '</div>' .
    '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
