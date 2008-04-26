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
require_once ("mp3.php");
require_once ("../incfiles/head.php");
$delmp3 = opendir("$filesroot/mp3temp");
while ($muzd = readdir($delmp3))
{
    if ($muzd != "." && $muzd != ".." && $muzd != "index.php")
    {
        $mp[] = $muzd;
    }
}
closedir($delmp3);
$totalmp = count($mp);
for ($imp = 0; $imp < $totalmp; $imp++)
{
    $filtime[$imp] = filemtime("$filesroot/mp3temp/$mp[$imp]");
    $tim = time();
    $ftime1 = $tim - 300;
    if ($filtime[$imp] < $ftime1)
    {
        unlink("$filesroot/mp3temp/$mp[$imp]");
    }
}
$rand = rand(1, 999);
if (!empty($_POST['fid']))
{
    $fid = intval($_POST['fid']);
}
if (!empty($_GET['id']))
{
    $id = intval($_GET['id']);
    $muz = mysql_query("select * from `download` where type = 'file' and id = '" . $id . "';");
    $muz1 = mysql_fetch_array($muz);
    $mp3 = "$muz1[adres]/$muz1[name]";
    $mp3 = str_replace("../", "", $mp3);
    $mp3 = "$home/$mp3";
}
if (!isset($_POST['a']) || empty($_POST['a']))
{
    $_SESSION['rand'] = $rand;
    print "<form action='?act=cut' method='post'>";
    $id3 = new MP3_Id();
    $result = $id3->read("$muz1[adres]/$muz1[name]");
    $result = $id3->study();
    if (!empty($mp3))
    {
        echo "Нарезка файла $muz1[name]<br/><input type='hidden' name='url' value='" . $mp3 . "'/>";
    } else
    {
        echo "Ссылка на MP3:<br/><input type='text' title='Введите URL' name='url' value='http://'/><br/>";
        echo "<input type='submit' name='a' value='Инфо'/><br/>";
    }
    if (!empty($mp3) && $id3->getTag('bitrate') == "0")
    {
        echo "Не удалось распознать кодек<br/>Нарезка только по размеру<br/>";
    }
    echo "<input type='hidden' name='fid' value='" . $id . "'/>Способ нарезки:<br/>
<select title='Выберите способ' name='way'>
<option value='size'>по размеру</option>";
    if ($id3->getTag('bitrate') != 0)
    {
        echo "<option value='time'>по времени</option>";
    }
    echo "</select><br/>
Начать с (кб или сек.):<br/>
<input type='text' title='Начало фрагмента' name='s'/><br/>
<input type='hidden' name='rnd' value='" . $rand . "'/>
Закончить по (кб или сек.):<br/>
<input type='text' title='Окончание фрагмента' name='p'/><br/>
<input type='submit' name='a' value='Резать'/>
</form>";

    if (!empty($id))
    {
        echo "<a href='?act=view&amp;file=" . $id . "'>К файлу</a><br/>";
    }
} else
{
    $url = $_POST['url'];
    $a = check(trim($_POST['a']));
    $s = intval(trim($_POST['s']));
    $p = intval(trim($_POST['p']));
    $way = check(trim($_POST['way']));
    $error = 0;
    if (!eregi("^(http://)([a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z;]{2,3}))|(([0-9]{1,3}\.){3}([0-9]{1,3}))((/|\?)[a-z0-9~#%&'_\+=:;\?\.-])(.mp3)\$", $url))
    {
        print "Это не MP3!<br/>";
        $error = 1;
    }
    if ($a != "Инфо")
    {
        if (!isset($s) || empty($s))
        {
            print "Вы не ввели число начала!<br/> ";
            $error = 2;
        }
        if (!isset($p) || empty($p))
        {
            print "Вы не ввели число конца!<br/> ";
            $error = 2;
        }
        if ($error == 2)
        {
            echo "<a href='?act=cut&amp;id=" . $fid . "'>Исправить!</a><br/>";
        }
    }
    if ($error == 0)
    {
        $randint = rand(10000000, 99999999);
        $randintval = "$randint.mp3";
        $randintval = "$filesroot/mp3temp/$randintval";
        if (copy($url, $randintval))
        {
            if ($a == "Инфо")
            {
                if (!empty($_POST['fid']))
                {
                    $fid = intval($_POST['fid']);
                }
                $id3 = new MP3_Id();
                $result = $id3->read($randintval);
                $result = $id3->study();
                print $id3->getTag('mode') . "<br/>
<u>Размер:</u> " . round($id3->getTag('filesize') / 1024) . " Кб<br/>
<u>Битрейт:</u> " . $id3->getTag('bitrate') . " кбит/сек<br/>
<u>Длительность:</u> " . $id3->getTag('length') . "<br/>
<u>Частота дискретизации:</u> " . $id3->getTag('frequency') . " Гц<br/>
<a href='?act=cut&amp;id=" . $fid . "'>Назад</a><br/>";
            } else
            {
                $fp = fopen($randintval, "rb");
                $raz = filesize($randintval);
                if ($way == "size")
                {
                    $s = $s * 1024;
                    $p = $p * 1024;
                    if ($s > $raz || $s < 0)
                    {
                        $s = 0;
                    }
                    if ($p > $raz || $p < $s)
                    {
                        $p = $raz;
                    }
                } else
                {
                    $id3 = new MP3_Id();
                    $result = $id3->read($randintval);
                    $result = $id3->study();
                    $byterate = $id3->getTag('bitrate') / 8;
                    $secbit = $raz / 1024 / $byterate;
                    if ($s > $secbit || $s < 0)
                    {
                        $s = 0;
                    }
                    if ($p > $secbit || $p < $s)
                    {
                        $p = $secbit;
                    }
                    $s = $s * $byterate * 1024;
                    $p = $p * $byterate * 1024;
                }
                $p = $p - $s;
                fseek($fp, $s);
                $filefp = fread($fp, $p);
                fclose($fp);
                unlink($randintval);
                if (!empty($_SESSION['rand']))
                {
                    $fp = fopen($randintval, "xb");
                    if (!fwrite($fp, $filefp) === false)
                    {
                        print "Файл успешно нарезан!<br/>Ссылка активна 5 минут<br/>
<a href='?act=mp3&amp;r=" . $randint . "'>Скачать</a><br/>";


                        echo "<a href='?act=cut'>Еще!</a><br/>";
                        unset($_SESSION['rand']);
                    }
                } else
                {
                    print "Ошибка!<br/> <a href='?act=cut'>Назад</a><br/>";
                }
                fclose($fp);
            }
        } else
        {
            print "Не удалось считать файл! <a href='?act=cut'>Назад</a><br/>";
        }
    }
}
echo "<a href='?'>В загрузки</a><br/>";

?>