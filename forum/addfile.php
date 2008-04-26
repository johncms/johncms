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

defined('_IN_JOHNCMS') or die ('Error: restricted access');

require_once ("../incfiles/head.php");
if (empty($_GET['id']))
{
    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$id = intval(check($_GET['id']));
if (empty($_SESSION['uid']))
{
    echo "Вы не авторизованы!<br/>";
    require_once ("../incfiles/end.php");
    exit;
}

$typ = mysql_query("select `id`, `type`, `from`, `refid` from `forum` where `id`= '" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($ms[from] != $login)
{
    echo "Ошибка!<br/>";
    require_once ("../incfiles/end.php");
    exit;
}

switch ($ms[type])
{
    case "m":

        if (isset($_POST['submit']))
        {
            $fname = $_FILES['fail']['name'];
            $fsize = $_FILES['fail']['size'];
            if ($fname != "")
            {
                $tfl = strtolower(format($fname));
                $df = array("vbs", "asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                if (in_array($tfl, $df))
                {

                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if ($fsize >= 1024 * $flsz)
                {

                    echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if (eregi("[^a-z0-9.()+_-]", $fname))
                {

                    echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess"))
                {

                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if (file_exists("files/$fname"))
                {
                    $fname = "$realtime.$fname";
                }
                if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "./files/$fname")) == true)
                {
                    $ch = $fname;
                    @chmod("$ch", 0777);
                    @chmod("files/$ch", 0777);
                    echo "Файл прикреплен!<br/>";
                } else
                {
                    echo "Ошибка при прикреплении файла<br/>";
                }
            }
            if (!empty($_POST['fail1']))
            {
                $uploaddir = "./files";
                $uploadedfile = $_POST['fail1'];
                if (strlen($uploadedfile) > 0)
                {
                    $array = explode('file=', $uploadedfile);
                    $tmp_name = $array[0];
                    $filebase64 = $array[1];
                }
                $tfl = strtolower(format($tmp_name));
                $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                if (in_array($tfl, $df))
                {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (strlen(base64_decode($filebase64)) >= 1024 * $flsz)
                {
                    echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if (eregi("[^a-z0-9.()+_-]", $tmp_name))
                {
                    echo "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/php/i", $tmp_name)) or (preg_match("/.pl/i", $tmp_name)) or ($tmp_name == ".htaccess"))
                {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=addfile&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if (strlen($filebase64) > 0)
                {
                    $fname = $tmp_name;
                    if (file_exists("files/$fname"))
                    {
                        $fname = "$realtime.$fname";
                    }
                    $FileName = "$uploaddir/$fname";
                    $filedata = base64_decode($filebase64);
                    $fid = @fopen($FileName, "wb");

                    if ($fid)
                    {
                        if (flock($fid, LOCK_EX))
                        {
                            fwrite($fid, $filedata);
                            flock($fid, LOCK_UN);
                        }
                        fclose($fid);
                    }
                    if (file_exists($FileName) && filesize($FileName) == strlen($filedata))
                    {
                        echo 'Файл ', $tmp_name, ' успешно прикреплён';
                        $ch = $fname;
                    } else
                    {
                        echo 'Ошибка при прикреплении файла ', $tmp_name, '';
                    }
                }
            }
            mysql_query("update `forum` set  attach='" . $ch . "' where id='" . $id . "';");
            $pa = mysql_query("select `id` from `forum` where type='m' and refid= '" . $ms['refid'] . "';");
            $pa2 = mysql_num_rows($pa);
            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                $page = 1;
            } else
            {
                $page = ceil($pa2 / $kmess);
            }
            echo "<br/><a href='index.php?id=" . $ms[refid] . "&amp;page=" . $page . "'>Продолжить</a><br/>";
        } else
        {
            echo "Добавление файла (max. $flsz kb)<br/><form action='index.php?act=addfile&amp;id=" . $id . "' method='post' enctype='multipart/form-data'>";
            if (!eregi("Opera/8.01", $agent))
            {
                echo "<input type='file' name='fail'/><br/>";
            } else
            {
                echo "	<input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
        }
        break;
    default:
        echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";

        break;
}

?>