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
    if (isset($_POST['submit']))
    {
        $scrname = $_FILES['screens']['name'];
        $scrsize = $_FILES['screens']['size'];
        $scsize = GetImageSize($_FILES['screens']['tmp_name']);
        $scwidth = $scsize[0];
        $scheight = $scsize[1];
        $ffot = strtolower($scrname);
        $dopras = array("gif", "jpg", "png");
        if ($scrname != "")
        {
            $formfot = format($ffot);
            if (!in_array($formfot, $dopras))
            {
                echo "Ошибка при загрузке скриншота.<br/><a href='?act=screen&amp;file=" . $file . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if ($scwidth > 320 || $scheight > 320)
            {
                echo "Размер картинки не должен превышать разрешения 320*320 px<br/><a href='?act=screen&amp;file=" . $file . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (eregi("[^a-z0-9.()+_-]", $scrname))
            {
                echo "В названии изображения $scrname присутствуют недопустимые символы<br/><a href='?act=screen&amp;file=" . $file . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $filnam = "$adrfile[name]";
            unlink("$screenroot/$adrfile[screen]");
            if ((move_uploaded_file($_FILES["screens"]["tmp_name"], "$screenroot/$filnam.$formfot")) == true)
            {
                $ch1 = "$filnam.$formfot";
                @chmod("$ch1", 0777);
                @chmod("$screenroot/$ch1", 0777);
                echo "Скриншот загружен!<br/>";
                mysql_query("update `download` set screen='" . $ch1 . "' where id='" . $file . "';");
            }
        }
        if (!empty($_POST['fail1']))
        {
            $uploaddir = "$screenroot";
            $uploadedfile = $_POST['fail1'];
            if (strlen($uploadedfile) > 0)
            {
                $array = explode('file=', $uploadedfile);
                $tmp_name = $array[0];
                $filebase64 = $array[1];
            }
            if (eregi("[^a-z0-9.()+_-]", $tmp_name))
            {
                echo "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=screen&amp;file=" . $file . "'>Повторить</a></div>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $ffot = strtolower($tmp_name);
            $dopras = array("gif", "jpg", "png");

            $formfot = format($ffot);
            if (!in_array($formfot, $dopras))
            {
                echo "Ошибка при загрузке скриншота.<br/><a href='?act=screen&amp;file=" . $file . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (strlen($filebase64) > 0)
            {
                unlink("$screenroot/$adrfile[screen]");
                $filnam = "$adrfile[name]";
                $FileName = "$uploaddir/$filnam.$formfot";
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
                    $sizsf = GetImageSize("$FileName");
                    $widthf = $sizsf[0];
                    $heightf = $sizsf[1];
                    if ($widthf > 320 || $heightf > 320)
                    {
                        echo "Размер картинки не должен превышать разрешения 320*320 px<br/><a href='?act=screen&amp;file=" . $file . "'>Повторить</a><br/>";
                        unlink("$FileName");
                        require_once ('../incfiles/end.php');
                        exit;
                    }
                    echo 'Скриншот загружен!<br/>';
                    $ch1 = "$filnam.$formfot";
                    mysql_query("update `download` set screen='" . $ch1 . "' where id='" . $file . "';");
                } else
                {
                    echo 'Ошибка при загрузке скриншота<br/>';
                }
            }
        }
    } else
    {
        if (!empty($adrfile[screen]))
        {
            echo "Заменить скриншот<br/>";
        } else
        {
            echo "Загрузить скриншот<br/>";
        }
        echo "<form action='?act=screen&amp;file=" . $file . "' method='post' enctype='multipart/form-data'>
         Выберите файл(max. 320*320):<br/>
         <input type='file' name='screens'/><hr/>
Для Opera Mini:<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл(</a><hr/>
<input type='submit' name='submit' value='Загрузить'/><br/>
         </form>";
    }
} else
{
    echo "Нет доступа!";
}
echo "&#187;<a href='?act=view&amp;file=" . $file . "'>К файлу</a><br/>";

?>