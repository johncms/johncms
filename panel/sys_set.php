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

echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Настройка системы</div>';
if (isset ($_POST['submit'])) {
    mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['skindef']) . "' WHERE `key` = 'skindef'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(htmlspecialchars($_POST['madm'])) . "' WHERE `key` = 'emailadmina'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['sdvigclock']) . "' WHERE `key` = 'sdvigclock'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['copyright']) . "' WHERE `key` = 'copyright'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['homeurl']) . "' WHERE `key` = 'homeurl'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['flsz']) . "' WHERE `key` = 'flsz'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['gz']) . "' WHERE `key` = 'gzip'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['fm']) . "' WHERE `key` = 'fmod'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['meta_key']) . "' WHERE `key` = 'meta_key'");
    mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['meta_desc']) . "' WHERE `key` = 'meta_desc'");
    $req = mysql_query("SELECT * FROM `cms_settings`");
    $set = array();
    while ($res = mysql_fetch_row($req)) $set[$res[0]] = $res[1];
    echo '<div class="rmenu">Сайт настроен</div>';
}
echo '<form action="index.php?act=sys_set" method="post"><div class="menu"><p>';
echo '<h3>Настройка часов</h3>';
echo '&nbsp;<input type="text" name="sdvigclock" size="2" maxlength="2" value="' . $set['sdvigclock'] . '"/> Сдвиг времени (+-12)<br />';
echo '&nbsp;<span style="font-weight:bold; background-color:#CCC">' . date("H:i") . '</span> Системное время';
echo '</p><p><h3>Функции системы</h3>';
echo '&nbsp;Адрес сайта без слэша в конце:<br/>&nbsp;<input type="text" name="homeurl" value="' . htmlentities($set['homeurl']) . '"/><br/>';
echo '&nbsp;Копирайт сайта:<br/>&nbsp;<input type="text" name="copyright" value="' . htmlentities($set['copyright'], ENT_QUOTES, 'UTF-8') . '"/><br/>';
echo '&nbsp;E-mail сайта:<br/>&nbsp;<input name="madm" maxlength="50" value="' . htmlentities($set['emailadmina']) . '"/><br />';
echo '&nbsp;Макс. размер файлов(кб.):<br />&nbsp;<input type="text" name="flsz" value="' . intval($set['flsz']) . '"/><br />';
echo '&nbsp;<input name="gz" type="checkbox" value="1" ' . ($set['gzip'] ? 'checked="checked"' : '') . ' />&nbsp;GZIP сжатие';
echo '</p><p><h3>META тэги</h3>';
echo '&nbsp;Ключевые слова:<br />&nbsp;<textarea cols="20" rows="4" name="meta_key">' . $set['meta_key'] . '</textarea><br />';
echo '&nbsp;Описание:<br />&nbsp;<textarea cols="20" rows="4" name="meta_desc">' . $set['meta_desc'] . '</textarea>';
echo '</p><p><h3>Тема оформления</h3>&nbsp;<select name="skindef">';
$dir = opendir('../theme');
while ($skindef = readdir($dir)) {
    if (($skindef != '.') && ($skindef != '..') && ($skindef != '.svn')) {
        $skindef = str_replace('.css', '', $skindef);
        echo '<option' . ($set['skindef'] == $skindef ? ' selected="selected">' : '>') . $skindef . '</option>';
    }
}
closedir($dir);
echo '</select>';
echo '</p><p><input type="submit" name="submit" value="Сохранить"/></p></div></form>';
echo '<div class="phdr">&nbsp;</div>';
echo '<p><a href="index.php">Админ панель</a></p>';

?>