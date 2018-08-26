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
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['site_map'] . '</div>';

/*
-----------------------------------------------------------------
Настройки карты сайта
-----------------------------------------------------------------
*/
if (isset($_GET['reset'])) {
    // Задаем настройки по умолчанию
    $settings = array (
        'forum' => 1,
        'lib' => 1,
        'users' => 0,
        'browsers' => 0
    );
    $stmt = $db->prepare("UPDATE `cms_settings` SET
        `val` = ?
        WHERE `key` = 'sitemap'
    ");
    $stmt->execute([
        serialize($settings)
    ]);
    echo '<div class="rmenu"><p>' . $lng['settings_default'] . '</p></div>';
} elseif (isset($_POST['submit'])) {
    // Принимаем настройки из формы
    $settings['forum'] = isset($_POST['forum']);
    $settings['lib'] = isset($_POST['lib']);
    $settings['users'] = isset($_POST['users']) && $_POST['users'] == 1 ? 1 : 0;
    $settings['browsers'] = isset($_POST['browsers']) && $_POST['browsers'] == 1 ? 1 : 0;
    $stmt = $db->prepare("UPDATE `cms_settings` SET
        `val` = ?
        WHERE `key` = 'sitemap'
    ");
    $stmt->execute([
        serialize($settings)
    ]);
    echo '<div class="gmenu"><p>' . $lng['settings_saved'] . '</p></div>';
} else {
    // Получаем сохраненные настройки
    $settings = unserialize($set['sitemap']);
}

/*
-----------------------------------------------------------------
Форма ввода настроек
-----------------------------------------------------------------
*/
echo '<form action="index.php?act=sitemap" method="post"><div class="menu"><p>' .
    '<h3>' . $lng['include_in_map'] . '</h3>' .
    '<input name="forum" type="checkbox" value="1" ' . ($settings['forum'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['forum'] . '<br />' .
    '<input name="lib" type="checkbox" value="1" ' . ($settings['lib'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['library'] . '</p>' .
    '<p><h3>' . $lng['browsers'] . '</h3>' .
    '<input type="radio" value="1" name="browsers" ' . ($settings['browsers'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['show_all'] . '<br />' .
    '<input type="radio" value="0" name="browsers" ' . (!$settings['browsers'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['show_only_computers'] . '</p>' .
    '<p><h3>' . $lng['users'] . '</h3>' .
    '<input type="radio" value="1" name="users" ' . ($settings['users'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['show_all'] . '<br />' .
    '<input type="radio" value="0" name="users" ' . (!$settings['users'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['show_only_guests'] . '</p>' .
    '<p><input type="submit" value="' . $lng['save'] . '" name="submit" /></p>' .
    '</div></form>' .
    '<div class="phdr"><a href="index.php?act=sitemap&amp;reset">' . $lng['reset_settings'] . '</a></div>' .
    '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
