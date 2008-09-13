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

define('_IN_JOHNCMS', 1);

$textl = 'Настройки сайта';
require_once ("../incfiles/core.php");

if ($dostadm == 1)
{
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "set":
            mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['nadm']) . "' WHERE `key`='nickadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(htmlspecialchars($_POST['madm'])) . "' WHERE `key`='emailadmina';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['nadm2']) . "' WHERE `key`='nickadmina2';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['sdvigclock']) . "' WHERE `key`='sdvigclock';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['copyright']) . "' WHERE `key`='copyright';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . check($_POST['homeurl']) . "' WHERE `key`='homeurl';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(trim($_POST['rashstr'])) . "' WHERE `key`='rashstr';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . mysql_real_escape_string(trim($_POST['admp'])) . "' WHERE `key`='admp';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['flsz']) . "' WHERE `key`='flsz';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['gz']) . "' WHERE `key`='gzip';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['fm']) . "' WHERE `key`='fmod';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['gb']) . "' WHERE `key`='gb';");
            mysql_query("UPDATE `cms_settings` SET `val`='" . intval($_POST['rm']) . "' WHERE `key`='rmod';");
            header("location: set.php?set");
            break;

        default:
            require_once ("../incfiles/head.php");
            echo '<div class="phdr">Настройка системы</div>';
            if (isset($_GET['set']))
            {
                echo "<div style='color: red'>Сайт настроен</div>";
            }
            echo '<p>Время на сервере: ' . date("H.i(d/m/Y)") . '</p>';
            $setdata = array("rashstr" => "Расширение страниц:");
            echo "<form method='post' action='set.php?act=set'>";
            if ($dostsadm == 1)
            {
                echo "Ник админа:<br/><input name='nadm' maxlength='50' value='" . $nickadmina . "'/><br/>";
                echo "Ник 2-го админа:<br/><input name='nadm2' maxlength='50' value='" . $nickadmina2 . "'/><br/>";
                echo "е-mail админа:<br/><input name='madm' maxlength='50' value='" . htmlentities($set['emailadmina']) . "'/><br/>";
            } else
            {
                echo "<input name='nadm' type='hidden' value='" . $nickadmina . "'/><input name='nadm2' type='hidden' value='" . $nickadmina2 . "'/><input name='madm' type='hidden' value='" . htmlentities($set['emailadmina']) . "'/>";
            }
            echo 'Временной сдвиг:<br/><input type="text" name="sdvigclock" value="' . intval($set['sdvigclock']) . '"/><br/>';
            echo 'Ваш копирайт:<br/><input type="text" name="copyright" value="' . htmlentities($set['copyright']) . '"/><br/>';
            echo 'Главная сайта без слэша в конце:<br/><input type="text" name="homeurl" value="' . htmlentities($set['homeurl']) . '"/><br/>';
            echo 'Макс.допустимый размер файлов(кб.):<br/><input type="text" name="flsz" value="' . intval($set['flsz']) . '"/><br/>';
            echo 'Папка с админкой:<br/><input type="text" name="admp" value="' . htmlentities($set['admp']) . '"/><br/>';
            echo 'Расширение страниц:<br/><input type="text" name="rashstr" value="' . htmlentities($set['rashstr']) . '"/><br/>';
            echo '<p><input name="gz" type="checkbox" value="1" ' . ($set['gzip'] ? 'checked="checked"' : '') . ' />&nbsp;GZIP сжатие<br/>';
            echo '<input name="rm" type="checkbox" value="1" ' . ($set['rmod'] ? 'checked="checked"' : '') . ' />&nbsp;мод. регистрации<br/>';
            echo '<input name="fm" type="checkbox" value="1" ' . ($set['fmod'] ? 'checked="checked"' : '') . ' />&nbsp;мод. форума<br/>';
            echo '<input name="gb" type="checkbox" value="1" ' . ($set['gb'] ? 'checked="checked"' : '') . ' />&nbsp;гостевая для гостей</p>';
            echo '<input value="Ok!" type="submit"/></form>';
            echo '<p><a href="main.php">В админку</a></p>';
            break;
    }
} else
{
    header("Location: ../index.php?err");
}

include ("../incfiles/end.php");

?>