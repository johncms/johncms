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
session_name("SESID");
session_start();
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
            $nadm = check($_POST['nadm']);
            $nadm2 = check($_POST['nadm2']);
            $madm = htmlspecialchars($_POST['madm']);
            $sdv = check($_POST['sdvigclock']);
            $cop = check($_POST['copyright']);
            $url = check($_POST['homeurl']);
            $ext = check($_POST['rashstr']);
            $gz = intval(check($_POST['gz']));
            $gbk = intval(check($_POST['gbk']));
            $admp = check($_POST['admp']);
            $fm = intval(check($_POST['fm']));
            $rm = intval(check($_POST['rm']));
            $fsz = intval(check($_POST['flsz']));
            mysql_query("update `settings` set  nickadmina2='" . $nadm2 . "', nickadmina='" . $nadm . "', emailadmina='" . $madm . "', sdvigclock='" . $sdv . "',  copyright='" . $cop . "', homeurl='" . $url . "', rashstr='" . $ext . "', gzip='" . $gz .
                "' ,admp='" . $admp . "', fmod='" . $fm . "', flsz='" . $fsz . "',gb='" . $gbk . "', rmod='" . $rm . "' where id='1';");
            header("location: set.php?set");
            break;

        default:
            require_once ("../incfiles/head.php");
            if (isset($_GET[set]))
            {
                echo "<div style='color: red'>Сайт настроен</div>";
            }
            echo '<b>АДМИН ПАНЕЛЬ</b><br />Настройка системы<hr/>';
            echo '<br />Время на сервере: ' . date("H.i(d/m/Y)") . '<br /><br />';
            $setdata = array("rashstr" => "Расширение страниц:");

            echo "<form method='post' action='set.php?act=set'>";
            if ($dostsadm == 1)
            {
                echo "Ник админа:<br/>
     <input name='nadm' maxlength='50' value='" . $nickadmina . "'/><br/>";
                echo "Ник 2-го админа:<br/>
     <input name='nadm2' maxlength='50' value='" . $nickadmina2 . "'/><br/>";

                echo "е-mail админа:<br/>
     <input name='madm' maxlength='50' value='" . $emailadmina . "'/><br/>";
            } else
            {
                echo "<input name='nadm' type='hidden' value='" . $nickadmina . "'/>
     <input name='nadm2' type='hidden' value='" . $nickadmina2 . "'/>
     <input name='madm' type='hidden' value='" . $emailadmina . "'/>";
            }
            echo "Временной сдвиг:<br/><input type='text' name='sdvigclock' value='" . $sdvigclock . "'/><br/>";
            echo "Ваш копирайт:<br/><input type='text' name='copyright' value='" . $copyright . "'/><br/>";
            echo "Главная сайта без слэша в конце:<br/><input type='text' name='homeurl' value='" . $home . "'/><br/>";
            echo "Макс.допустимый размер файлов(кб.):<br/><input type='text' name='flsz' value='" . $flsz . "'/><br/>";
            echo "Папка с админкой:<br/><input type='text' name='admp' value='" . $admp . "'/><br/>";
            echo "Расширение страниц:<br/><input type='text' name='rashstr' value='" . $ras_pages . "'/><br/>";
            echo "Включить gzip сжатие:<br/>Да";
            if ($gzip == "1")
            {
                echo "<input name='gz' type='radio' value='1' checked='checked'/>";
            } else
            {
                echo "<input name='gz' type='radio' value='1' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($gzip == "0")
            {
                echo "<input name='gz' type='radio' value='0' checked='checked' />";
            } else
            {
                echo "<input name='gz' type='radio' value='0'/>";
            }
            echo "Нет<br/>";
            echo "Включить подтверждение регистрации:<br/>Да";
            if ($rmod == "1")
            {
                echo "<input name='rm' type='radio' value='1' checked='checked'/>";
            } else
            {
                echo "<input name='rm' type='radio' value='1' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($rmod == "0")
            {
                echo "<input name='rm' type='radio' value='0' checked='checked' />";
            } else
            {
                echo "<input name='rm' type='radio' value='0'/>";
            }
            echo "Нет<br/>";
            echo "Включить премодерацию форума:<br/>Да";
            if ($fmod == "1")
            {
                echo "<input name='fm' type='radio' value='1' checked='checked'/>";
            } else
            {
                echo "<input name='fm' type='radio' value='1' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($fmod == "0")
            {
                echo "<input name='fm' type='radio' value='0' checked='checked' />";
            } else
            {
                echo "<input name='fm' type='radio' value='0'/>";
            }
            echo "Нет<br/>";
            echo "Открыть гостевую для добавления постов гостями:<br/>Да";
            if ($gb == "1")
            {
                echo "<input name='gbk' type='radio' value='1' checked='checked'/>";
            } else
            {
                echo "<input name='gbk' type='radio' value='1' />";
            }
            echo " &nbsp; &nbsp; ";
            if ($gb == "0")
            {
                echo "<input name='gbk' type='radio' value='0' checked='checked' />";
            } else
            {
                echo "<input name='gbk' type='radio' value='0'/>";
            }
            echo "Нет<br/>";

            echo '<br/><input value="Ok!" type="submit"/></form>';
            echo '<br /><a href="main.php">В админку</a><br/><br/>';
            break;
    }
} else
{
    header("Location: ../index.php?err");
}
include ("../incfiles/end.php");

?>