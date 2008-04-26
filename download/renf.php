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

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once ("../incfiles/head.php");
if ($dostdmod == 1)
{
    if ($_GET['file'] == "")
    {
        echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $file = intval(trim($_GET['file']));
    $file1 = mysql_query("select * from `download` where type = 'file' and id = '" . $file . "';");
    $file2 = mysql_num_rows($file1);
    $adrfile = mysql_fetch_array($file1);
    if (($file1 == 0) || (!is_file("$adrfile[adres]/$adrfile[name]")))
    {
        echo "Ошибка при выборе файла<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $tf = format($adrfile[name]);
    $stn = str_replace("$tf", "", $adrfile[name]);
    if (isset($_POST['submit']))
    {
        if (!empty($_POST['newf']))
        {
            $newf = check(trim($_POST['newf']));
        } else
        {
            $newf = $stn;
        }
        if (eregi("[^a-z0-9.()+_-]", $newf))
        {
            echo "В новом названии файла <b>$newn</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=renf&amp;file=" . $file . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $rn = rename("$adrfile[adres]/$adrfile[name]", "$adrfile[adres]/$newf.$tf");
        if ($rn == true)
        {
            $ch = "$newf.$tf";
            echo "Файл переименован <br/>";
            mysql_query("update `download` set name='" . $ch . "' where id='" . $file . "';");
        }
    } else
    {
        echo "<form action='?act=renf&amp;file=" . $file . "' method='post'>";
        echo "Название(без расширения): <br/><input type='text' name='newf' value='" . $stn . "'/><br/>";
        echo "<input type='submit' name='submit' value='Изменить'/></form><br/>";
    }
} else
{
    echo "Нет доступа!";
}
echo "&#187;<a href='?act=view&amp;file=" . $file . "'>К файлу</a><br/>";

?>