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
echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['site_settings'] . '</div>';
if (isset($_POST['submit'])) {
    /*
    -----------------------------------------------------------------
    Сохраняем настройки системы
    -----------------------------------------------------------------
    */
    $stmt = $db->prepare("UPDATE `cms_settings` SET `val`= ? WHERE `key` = ?");
    $theme_list = [];
    foreach (glob('../theme/*/*.css') as $val) {
        $dir = explode('/', dirname($val));
        $theme_list[] = array_pop($dir);
    }
    if (isset($_POST['skindef']) && in_array($_POST['skindef'], $theme_list)) {
        $stmt->execute([$_POST['skindef'], 'skindef']);
    }
    $stmt->execute([htmlspecialchars($_POST['madm']), 'email']);
    $stmt->execute([intval($_POST['timeshift']), 'timeshift']);
    $stmt->execute([trim($_POST['copyright']), 'copyright']);
    $stmt->execute([preg_replace("#/$#", '', _e(trim($_POST['homeurl']))), 'homeurl']);
    $stmt->execute([intval($_POST['flsz']), 'flsz']);
    $stmt->execute([(isset($_POST['gz']) ? 1 : 0), 'gzip']);
    $stmt->execute([trim($_POST['meta_key']), 'meta_key']);
    $stmt->execute([trim($_POST['meta_desc']), 'meta_desc']);
    $stmt = $db->query("SELECT * FROM `cms_settings`");
    $set = array ();
    while ($res = $stmt->fetch()) {
        $set[$res['key']] = $res['val'];
    }
    echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
}
/*
-----------------------------------------------------------------
Форма ввода параметров системы
-----------------------------------------------------------------
*/
echo '<form action="index.php?act=settings" method="post"><div class="menu">';
// Общие настройки
echo '<p>' .
    '<h3>' . $lng['common_settings'] . '</h3>' .
    $lng['site_url'] . ':<br/>' . '<input type="text" name="homeurl" value="' . $set['homeurl'] . '"/><br/>' .
    $lng['site_copyright'] . ':<br/>' . '<input type="text" name="copyright" value="' . _e($set['copyright']) . '"/><br/>' .
    $lng['site_email'] . ':<br/>' . '<input name="madm" maxlength="50" value="' . htmlentities($set['email']) . '"/><br />' .
    $lng['file_maxsize'] . ' (kb):<br />' . '<input type="text" name="flsz" value="' . intval($set['flsz']) . '"/><br />' .
    '<input name="gz" type="checkbox" value="1" ' . ($set['gzip'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['gzip_compress'] .
    '</p>';
// Настройка времени
echo '<p>' .
    '<h3>' . $lng['clock_settings'] . '</h3>' .
    '<input type="text" name="timeshift" size="2" maxlength="3" value="' . $set['timeshift'] . '"/> ' . $lng['time_shift'] . ' (+-12)<br />' .
    '<span style="font-weight:bold; background-color:#C0FFC0">' . date("H:i", time() + $set['timeshift'] * 3600) . '</span> ' . $lng['system_time'] .
    '<br /><span style="font-weight:bold; background-color:#FFC0C0">' . date("H:i") . '</span> ' . $lng['server_time'] .
    '</p>';
// META тэги
echo '<p>' .
    '<h3>' . $lng['meta_tags'] . '</h3>' .
    '&#160;' . $lng['meta_keywords'] . ':<br />&#160;<textarea rows="' . $set_user['field_h'] . '" name="meta_key">' . _e($set['meta_key']) . '</textarea><br />' .
    '&#160;' . $lng['meta_description'] . ':<br />&#160;<textarea rows="' . $set_user['field_h'] . '" name="meta_desc">' . _e($set['meta_desc']) . '</textarea>' .
    '</p>';
// Выбор темы оформления
echo '<p><h3>' . $lng['design_template'] . '</h3>&#160;<select name="skindef">';
foreach (glob('../theme/*/*.css') as $val) {
    $dir = explode('/', dirname($val));
    $theme = array_pop($dir);
    echo '<option' . ($set['skindef'] == $theme ? ' selected="selected">' : '>') . $theme . '</option>';
}
echo '</select>' .
    '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '"/></p></div></form>' .
    '<div class="phdr">&#160;</div>' .
    '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
?>
