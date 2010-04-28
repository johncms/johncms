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
if ($rights != 9)
    die('Error: restricted access');

$set_af = isset($set['antiflood']) ? unserialize($set['antiflood']) : array ();
echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Настройка антифлуда</div>';
if (isset($_POST['submit']) || isset($_POST['save'])) {
    // Принимаем данные из формы
    $set_af['mode'] = isset($_POST['mode']) && $_POST['mode'] > 0 && $_POST['mode'] < 5 ? intval($_POST['mode']) : 1;
    $set_af['day'] = isset($_POST['day']) ? intval($_POST['day']) : 10;
    $set_af['night'] = isset($_POST['night']) ? intval($_POST['night']) : 30;
    $set_af['dayfrom'] = isset($_POST['dayfrom']) ? intval($_POST['dayfrom']) : 10;
    $set_af['dayto'] = isset($_POST['dayto']) ? intval($_POST['dayto']) : 22;
    // Проверяем правильность ввода данных
    if ($set_af['day'] < 4)
        $set_af['day'] = 4;
    if ($set_af['day'] > 300)
        $set_af['day'] = 300;
    if ($set_af['night'] < 4)
        $set_af['night'] = 4;
    if ($set_af['night'] > 300)
        $set_af['night'] = 300;
    if ($set_af['dayfrom'] < 6)
        $set_af['dayfrom'] = 6;
    if ($set_af['dayfrom'] > 12)
        $set_af['dayfrom'] = 12;
    if ($set_af['dayto'] < 17)
        $set_af['dayto'] = 17;
    if ($set_af['dayto'] > 23)
        $set_af['dayto'] = 23;
    mysql_query("UPDATE `cms_settings` SET `val` = '" . serialize($set_af) . "' WHERE `key` = 'antiflood' LIMIT 1");
    echo '<div class="rmenu">Сайт настроен</div>';
} elseif (empty($set_af) || isset($_GET['reset'])) {
    // Устанавливаем настройки по умолчанию (если не заданы в системе)
    echo '<div class="rmenu">Установлены настройки по умолчанию</div>';
    $set_af['mode'] = 2;
    $set_af['day'] = 10;
    $set_af['night'] = 30;
    $set_af['dayfrom'] = 10;
    $set_af['dayto'] = 22;
    @mysql_query("DELETE FROM `cms_settings` WHERE `key` = 'antiflood' LIMIT 1");
    mysql_query("INSERT INTO `cms_settings` SET `key` = 'antiflood', `val` = '" . serialize($set_af) . "'");
}
echo '<form action="index.php?act=sys_flood" method="post">';
echo '<div class="gmenu"><p><h3>Режим работы</h3><table cellspacing="2">
<tr><td valign="top"><input type="radio" name="mode" value="3" ' . ($set_af['mode'] == 3 ? 'checked="checked"' : '') . '/></td><td><b>День</b></td></tr>
<tr><td valign="top"><input type="radio" name="mode" value="4" ' . ($set_af['mode'] == 4 ? 'checked="checked"' : '') . '/></td><td><b>Ночь</b></td></tr>
<tr><td valign="top"><input type="radio" name="mode" value="2" ' . ($set_af['mode'] == 2 ? 'checked="checked"' : '') . '/></td><td><b>День / Ночь</b><br /><small>Автоматический переход с дневного на ночной режим, согласно заданному в настройках времени</small></td></tr>
<tr><td valign="top"><input type="radio" name="mode" value="1" ' . ($set_af['mode'] == 1 ? 'checked="checked"' : '') . '/></td><td><b>Адаптивный</b><br /><small>Если на сайте есть кто-то из Администрации, система работает в &quot;дневном&quot; режиме, иначе переходит в &quot;ночной&quot;</small></td></tr>
</table></p><p><input type="submit" name="save" value="Установить"/></p></div>';
echo '<div class="menu"><p><h3>Антифлуд (секунд)</h3>';
echo '<input name="day" size="3" value="' . $set_af['day'] . '" maxlength="3" />&nbsp;Днем<br />';
echo '<input name="night" size="3" value="' . $set_af['night'] . '" maxlength="3" />&nbsp;Ночью';
echo '<br /><small>Минимум 4, максимум 300 секунд</small></p>';
echo '<p><h3>Дневной режим</h3>';
echo '<input name="dayfrom" size="2" value="' . $set_af['dayfrom'] . '" maxlength="2" style="text-align:right"/>:00&nbsp;Начало дня <span class="gray">(6-12)</span><br />';
echo '<input name="dayto" size="2" value="' . $set_af['dayto'] . '" maxlength="2" style="text-align:right"/>:00&nbsp;Конец дня <span class="gray">(17-23)</span>';
echo '</p><p><input type="submit" name="submit" value="Запомнить"/></p></div></form>';
echo '<div class="phdr"><a href="index.php?act=sys_flood&amp;reset">Сброс настроек</a></div>';
echo '<p><a href="index.php">Админ панель</a></p>';

?>