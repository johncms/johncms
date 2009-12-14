<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 7) {
    header('Location: http://gazenwagen.com/?err');
    exit;
}

////////////////////////////////////////////////////////////
// Установка прав доступа к подсистемам                   //
////////////////////////////////////////////////////////////
echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Права доступа</div>';
if (isset ($_POST['submit'])) {
    // Записываем настройки в базу
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['reg']) ? intval($_POST['reg']) : 0) . "' WHERE `key`='mod_reg'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['forum']) ? intval($_POST['forum']) : 0) . "' WHERE `key`='mod_forum'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['chat']) ? intval($_POST['chat']) : 0) . "' WHERE `key`='mod_chat'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['guest']) ? intval($_POST['guest']) : 0) . "' WHERE `key`='mod_guest'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['lib']) ? intval($_POST['lib']) : 0) . "' WHERE `key`='mod_lib'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['gal']) ? intval($_POST['gal']) : 0) . "' WHERE `key`='mod_gal'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['down']) ? intval($_POST['down']) : 0) . "' WHERE `key`='mod_down'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['libcomm']) ? intval($_POST['libcomm']) : 0) . "' WHERE `key`='mod_lib_comm'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['galcomm']) ? intval($_POST['galcomm']) : 0) . "' WHERE `key`='mod_gal_comm'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset ($_POST['downcomm']) ? intval($_POST['downcomm']) : 0) . "' WHERE `key`='mod_down_comm'");
    $req = mysql_query("SELECT * FROM `cms_settings`");
    $set = array();
    while ($res = mysql_fetch_row($req)) $set[$res[0]] = $res[1];
    mysql_free_result($req);
    echo '<div class="rmenu">Сайт настроен</div>';
}

////////////////////////////////////////////////////////////
// Выводим форму задания параметров                       //
////////////////////////////////////////////////////////////
echo '<form method="post" action="index.php?act=sys_access">';
// Доступ к форуму
if ($set['mod_forum'] == 2)
    $img = 'green';
elseif ($set['mod_forum'] == 1)
    $img = 'yelow';
else
    $img = 'red';
echo '<div class="menu"><p><h3><img src="../images/' . $img . '.gif" width="16" height="16" class="left"/>&nbsp;Форум</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="forum" ' . ($set['mod_forum'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;открыт<br />
<input type="radio" value="1" name="forum" ' . ($set['mod_forum'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;авторизованным<br />
<input type="radio" value="0" name="forum" ' . (!$set['mod_forum'] ? 'checked="checked"' : '') . '/>&nbsp;закрыт</div></p>';
// Доступ к Гостевой
if ($set['mod_guest'] == 2)
    $img = 'green';
elseif ($set['mod_guest'] == 1)
    $img = 'yelow';
else
    $img = 'red';
echo '<p><h3><img src="../images/' . $img . '.gif" width="16" height="16" class="left"/>&nbsp;Гостевая</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="guest" ' . ($set['mod_guest'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;могут писать гости<br />
<input type="radio" value="1" name="guest" ' . ($set['mod_guest'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;открыта<br />
<input type="radio" value="0" name="guest" ' . (!$set['mod_guest'] ? 'checked="checked"' : '') . '/>&nbsp;закрыта</div></p>';
// Доступ к Чату
echo '<p><h3><img src="../images/' . ($set['mod_chat'] ? 'green' : 'red') . '.gif" width="16" height="16" class="left"/>&nbsp;Чат</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="chat" ' . ($set['mod_chat'] ? 'checked="checked"' : '') . '/>&nbsp;открыт<br />
<input type="radio" value="0" name="chat" ' . (!$set['mod_chat'] ? 'checked="checked"' : '') . '/>&nbsp;закрыт</div></p>';
// Доступ к Библиотеке
if ($set['mod_lib'] == 2)
    $img = 'green';
elseif ($set['mod_lib'] == 1)
    $img = 'yelow';
else
    $img = 'red';
echo '<p><h3><img src="../images/' . $img . '.gif" width="16" height="16" class="left"/>&nbsp;Библиотека</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="lib" ' . ($set['mod_lib'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;открыта<br />
<input type="radio" value="1" name="lib" ' . ($set['mod_lib'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;авторизованным<br />
<input type="radio" value="0" name="lib" ' . (!$set['mod_lib'] ? 'checked="checked"' : '') . '/>&nbsp;закрыта<br />
<input name="libcomm" type="checkbox" value="1" ' . ($set['mod_lib_comm'] ? 'checked="checked"' : '') . ' />&nbsp;комментарии</div></p>';
// Доступ к Галерее
if ($set['mod_gal'] == 2)
    $img = 'green';
elseif ($set['mod_gal'] == 1)
    $img = 'yelow';
else
    $img = 'red';
echo '<p><h3><img src="../images/' . $img . '.gif" width="16" height="16" class="left"/>&nbsp;Галерея</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="gal" ' . ($set['mod_gal'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;открыта<br />
<input type="radio" value="1" name="gal" ' . ($set['mod_gal'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;авторизованным<br />
<input type="radio" value="0" name="gal" ' . (!$set['mod_gal'] ? 'checked="checked"' : '') . '/>&nbsp;закрыта<br />
<input name="galcomm" type="checkbox" value="1" ' . ($set['mod_gal_comm'] ? 'checked="checked"' : '') . ' />&nbsp;комментарии</div></p>';
// Доступ к Загрузкам
if ($set['mod_down'] == 2)
    $img = 'green';
elseif ($set['mod_down'] == 1)
    $img = 'yelow';
else
    $img = 'red';
echo '<p><h3><img src="../images/' . $img . '.gif" width="16" height="16" class="left"/>&nbsp;Загрузки</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="down" ' . ($set['mod_down'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;открыты<br />
<input type="radio" value="1" name="down" ' . ($set['mod_down'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;авторизованным<br />
<input type="radio" value="0" name="down" ' . (!$set['mod_down'] ? 'checked="checked"' : '') . '/>&nbsp;закрыты<br />
<input name="downcomm" type="checkbox" value="1" ' . ($set['mod_down_comm'] ? 'checked="checked"' : '') . ' />&nbsp;комментарии</div></p></div>';
// Доступ к Регистрации
if ($set['mod_reg'] == 2)
    $img = 'green';
elseif ($set['mod_reg'] == 1)
    $img = 'yelow';
else
    $img = 'red';
echo '<div class="gmenu"><h3><img src="../images/' . $img . '.gif" width="16" height="16" class="left"/>&nbsp;Регистрация</h3><div style="font-size: x-small;">
<input type="radio" value="2" name="reg" ' . ($set['mod_reg'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;открыта<br />
<input type="radio" value="1" name="reg" ' . ($set['mod_reg'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;с модерацией<br />
<input type="radio" value="0" name="reg" ' . (!$set['mod_reg'] ? 'checked="checked"' : '') . '/>&nbsp;закрыта</div></div>';
echo '<div class="phdr"><small>У Администратора всегда остается доступ ко всем закрытым модулям и комментариям.</small></div>';
echo '<p><input type="submit" name="submit" id="button" value="Запомнить" /></p>';
echo '<p><a href="index.php">Админ панель</a></p>';
echo '</form>';

?>