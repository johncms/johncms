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

echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['access_rights'] . '</div>';
if (isset($_POST['submit'])) {
    // Записываем настройки в базу
    $stmt = $db->prepare('UPDATE `cms_settings` SET `val` = ? WHERE `key` = ?');
    $stmt->execute([(isset($_POST['reg']) ? intval($_POST['reg']) : 0), 'mod_reg']);
    $stmt->execute([(isset($_POST['forum']) ? intval($_POST['forum']) : 0), 'mod_forum']);
    $stmt->execute([(isset($_POST['guest']) ? intval($_POST['guest']) : 0), 'mod_guest']);
    $stmt->execute([(isset($_POST['lib']) ? intval($_POST['lib']) : 0), 'mod_lib']);
    $stmt->execute([(isset($_POST['gal']) ? intval($_POST['gal']) : 0), 'mod_gal']);
    $stmt->execute([(isset($_POST['down']) ? intval($_POST['down']) : 0), 'mod_down']);
    $stmt->execute([(isset($_POST['libcomm']) ? 1 : 0), 'mod_lib_comm']);
    $stmt->execute([(isset($_POST['galcomm']) ? 1 : 0), 'mod_gal_comm']);
    $stmt->execute([(isset($_POST['downcomm']) ? 1 : 0), 'mod_down_comm']);
    $stmt->execute([(isset($_POST['active']) ? intval($_POST['active']) : 0), 'active']);
    $stmt->execute([(isset($_POST['access']) ? intval($_POST['access']) : 0), 'site_access']);
    $stmt = $db->query("SELECT * FROM `cms_settings`");
    $set = array();
    while ($res = $stmt->fetch()) {
        $set[$res['key']] = $res['val'];
    }
    echo '<div class="rmenu">' . $lng['settings_saved'] . '</div>';
}

$color = array('red', 'yelow', 'green', 'gray');
echo '<form method="post" action="index.php?act=access">';

/*
-----------------------------------------------------------------
Управление доступом к Форуму
-----------------------------------------------------------------
*/
echo '<div class="menu"><p>' .
    '<h3><img src="../images/' . $color[$set['mod_forum']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['forum'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="forum" ' . ($set['mod_forum'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="1" name="forum" ' . ($set['mod_forum'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_authorised'] . '<br />' .
    '<input type="radio" value="3" name="forum" ' . ($set['mod_forum'] == 3 ? 'checked="checked"' : '') . '/>&#160;' . $lng['read_only'] . '<br />' .
    '<input type="radio" value="0" name="forum" ' . (!$set['mod_forum'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_disabled'] .
    '</div></p>';

/*
-----------------------------------------------------------------
Управление доступом к Гостевой
-----------------------------------------------------------------
*/
echo '<p><h3><img src="../images/' . $color[$set['mod_guest']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['guestbook'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="guest" ' . ($set['mod_guest'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled_for_guests'] . '<br />' .
    '<input type="radio" value="1" name="guest" ' . ($set['mod_guest'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="0" name="guest" ' . (!$set['mod_guest'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_disabled'] .
    '</div></p>';

/*
-----------------------------------------------------------------
Управление доступом к Библиотеке
-----------------------------------------------------------------
*/
echo '<p><h3><img src="../images/' . $color[$set['mod_lib']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['library'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="lib" ' . ($set['mod_lib'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="1" name="lib" ' . ($set['mod_lib'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_authorised'] . '<br />' .
    '<input type="radio" value="0" name="lib" ' . (!$set['mod_lib'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_disabled'] . '<br />' .
    '<input name="libcomm" type="checkbox" value="1" ' . ($set['mod_lib_comm'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['comments'] .
    '</div></p>';

/*
-----------------------------------------------------------------
Управление доступом к Галерее
-----------------------------------------------------------------
*/
echo '<p><h3><img src="../images/' . $color[$set['mod_gal']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['gallery'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="gal" ' . ($set['mod_gal'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="1" name="gal" ' . ($set['mod_gal'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_authorised'] . '<br />' .
    '<input type="radio" value="0" name="gal" ' . (!$set['mod_gal'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_disabled'] . '<br />' .
    '<input name="galcomm" type="checkbox" value="1" ' . ($set['mod_gal_comm'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['comments'] .
    '</div></p>';

/*
-----------------------------------------------------------------
Управление доступом к Загрузкам
-----------------------------------------------------------------
*/
echo '<p><h3><img src="../images/' . $color[$set['mod_down']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['downloads'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="down" ' . ($set['mod_down'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="1" name="down" ' . ($set['mod_down'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_authorised'] . '<br />' .
    '<input type="radio" value="0" name="down" ' . (!$set['mod_down'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_disabled'] . '<br />' .
    '<input name="downcomm" type="checkbox" value="1" ' . ($set['mod_down_comm'] ? 'checked="checked"' : '') . ' />&#160;' . $lng['comments'] .
    '</div></p>';

/*
-----------------------------------------------------------------
Управление доступом к Активу сайта (списки юзеров и т.д.)
-----------------------------------------------------------------
*/
echo '<p><h3><img src="../images/' . $color[$set['active'] + 1] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['community'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="1" name="active" ' . ($set['active'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="0" name="active" ' . (!$set['active'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_authorised'] . '<br />' .
    '</div></p></div>';

/*
-----------------------------------------------------------------
Управление доступом к Регистрации
-----------------------------------------------------------------
*/
echo '<div class="gmenu"><h3><img src="../images/' . $color[$set['mod_reg']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['registration'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input type="radio" value="2" name="reg" ' . ($set['mod_reg'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input type="radio" value="1" name="reg" ' . ($set['mod_reg'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_with_moderation'] . '<br />' .
    '<input type="radio" value="0" name="reg" ' . (!$set['mod_reg'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_disabled'] .
    '</div></div>';

/*
-----------------------------------------------------------------
Управление доступом к Сайту (Закрытие сайта)
-----------------------------------------------------------------
*/
echo '<div class="rmenu">' .
    '<h3><img src="../images/' . $color[$set['site_access']] . '.gif" width="16" height="16" class="left"/>&#160;' . $lng['site_access'] . '</h3>' .
    '<div style="font-size: x-small">' .
    '<input class="btn btn-large" type="radio" value="2" name="access" ' . ($set['site_access'] == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['access_enabled'] . '<br />' .
    '<input class="btn btn-large" type="radio" value="1" name="access" ' . ($set['site_access'] == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['site_closed_except_adm'] . '<br />' .
    '<input class="btn btn-large" type="radio" value="0" name="access" ' . (!$set['site_access'] ? 'checked="checked"' : '') . '/>&#160;' . $lng['site_closed_except_sv'] . '<br />' .
    '</div></div>';

echo '<div class="phdr"><small>' . $lng['access_help'] . '</small></div>' .
    '<p><input type="submit" name="submit" id="button" value="' . $lng['save'] . '" /></p>' .
    '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p></form>';
