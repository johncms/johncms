<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.1.0                     30.05.2008                             //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 7)
    die('Error: restricted access');
if ($rights == 9 && $do == 'clean') {
    if (isset($_GET['yes'])) {
        mysql_query("TRUNCATE TABLE `karma_users`");
        mysql_query("OPTIMIZE TABLE `karma_users`");
        mysql_query("UPDATE `users` SET `karma`='0', `plus_minus`='0|0'");
        mysql_query("OPTIMIZE TABLE `users`");
        echo '<div class="gmenu">Карма сброшена</div>';
    }
    else {
        echo '<div class="rmenu"><p>Вы действительно хотите cбросить карму?<br/>';
        echo '<a href="index.php?act=mod_karma&amp;do=clean&amp;yes">Удалить</a> | <a href="index.php?act=mod_karma">Отмена</a></p></div>';
    }
}
echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Карма</div>';
$settings = unserialize($set['karma']);
if (isset($_POST['submit'])) {
    $settings['karma_points'] = isset($_POST['karma_points']) ? abs(intval($_POST['karma_points'])) : 0;
    $settings['karma_time'] = isset($_POST['karma_time']) ? abs(intval($_POST['karma_time'])) : 0;
    $settings['forum'] = isset($_POST['forum']) ? abs(intval($_POST['forum'])) : 0;
    $settings['time'] = isset($_POST['time']) ? abs(intval($_POST['time'])) : 0;
    $settings['on'] = isset($_POST['on']) ? 1 : 0;
    $settings['adm'] = isset($_POST['adm']) ? 1 : 0;
    $settings['karma_time'] = $settings['time'] ? $settings['karma_time'] * 3600 : $settings['karma_time'] * 86400;
    mysql_query("UPDATE `cms_settings` SET `val` = '" . mysql_real_escape_string(serialize($settings)) . "' WHERE `key` = 'karma'");
    echo '<div class="rmenu">Настройки сохранены</div>';
}
$settings['karma_time'] = $settings['time'] ? $settings['karma_time'] / 3600 : $settings['karma_time'] / 86400;
echo '<form action="index.php?act=mod_karma" method="post"><div class="menu">';
echo '<p><h3>Голосов в сутки</h3><input type="text" name="karma_points" value="' . $settings['karma_points'] . '"/></p>';
echo '<p><h3>Ограничения для голосования</h3><input type="text" name="forum" value="' . $settings['forum'] . '" size="4"/>&nbsp;Постов на форуме<br />
        <input type="text" name="karma_time" value="' . $settings['karma_time'] . '" size="4"/>&nbsp;Провел на сайте<br />';
echo '&nbsp;<input name="time" type="radio" value="1"' . ($settings['time'] ? ' checked="checked"' : '') . '/>&nbsp;Часов<br />';
echo '&nbsp;<input name="time" type="radio" value="0"' . (!$settings['time'] ? ' checked="checked"' : '') . '/>&nbsp;Дней</p>';
echo '<p><h3>Основные настройки</h3><input type="checkbox" name="on"' . ($settings['on'] ? ' checked="checked"' : '') . '/> Включить модуль<br />';
echo '<input type="checkbox" name="adm"' . ($settings['adm'] ? ' checked="checked"' : '') . '/> Запретить голосовать за администрацию</p>';
echo '<p><input type="submit" value="Запомнить" name="submit" /></p></div>';
echo '</form><div class="phdr">' . ($rights == 9 ? '<a href="index.php?act=mod_karma&amp;do=clean">Сбросить карму</a>' : '<br />') . '</div>';
echo '<p><a href="index.php">Админ панель</a></p>';

?>