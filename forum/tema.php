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
$delf = opendir("temtemp");
while ($tt = readdir($delf))
{
    if ($tt != "." && $tt != ".." && $tt != "index.php")
    {
        $tm[] = $tt;
    }
}
closedir($delf);
$totalt = count($tm);
for ($it = 0; $it < $totalt; $it++)
{
    $filtime[$it] = filemtime("temtemp/$tm[$it]");
    $tim = time();
    $ftime1 = $tim - 300;
    if ($filtime[$it] < $ftime1)
    {
        unlink("temtemp/$tm[$it]");
    }
}
if (empty($_GET['id']))
{
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$id = intval(check($_GET['id']));
$type = mysql_query("select * from `forum` where id= '" . $id . "';");
$type1 = mysql_fetch_array($type);
$tip = $type1[type];
if ($tip != "t")
{
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
if (isset($_POST['submit']))
{
    $tema = mysql_query("select * from `forum` where type='m' and refid= '" . $id . "' order by time;");
    $mod = check(trim($_POST['mod']));
    switch ($mod)
    {
        case "txt":
            $text = "$type1[text]\r\n";
            while ($arr = mysql_fetch_array($tema))
            {
                $arr[text] = str_replace("[c]", "Цитата:{", $arr[text]);
                $arr[text] = str_replace("[/c]", "}-Ответ:", $arr[text]);
                $arr[text] = str_replace("&quot;", "\"", $arr[text]);
                $arr[text] = str_replace("[l]", "", $arr[text]);
                $arr[text] = str_replace("[l/]", "-", $arr[text]);
                $arr[text] = str_replace("[/l]", "", $arr[text]);
                if (!empty($arr[to]))
                {
                    $stroka = "$arr[from](" . date("d.m.Y/H:i", $arr[time]) . ")-$arr[to], $arr[text]\r\n";
                } else
                {
                    $stroka = "$arr[from](" . date("d.m.Y/H:i", $arr[time]) . ")-$arr[text]\r\n";
                }
                $text = "$text$stroka";
            }
            $num = "$realtime$id";
            $fp = fopen("temtemp/$num.txt", "a+");
            flock($fp, LOCK_EX);
            fputs($fp, "$text\r\n");
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            @chmod("$fp", 0777);
            @chmod("temtemp/$num.txt", 0777);

            echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
            break;
            ##
        case "xml":
            $text = "<?xml version='1.0' encoding='utf-8'?>
<!DOCTYPE html PUBLIC '-//WAPFORUM//DTD XHTML Mobile 1.0//EN' 
'http://www.wapforum.org/DTD/xhtml-mobile10.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='ru'>
<head>
<meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/><link rel='shortcut icon' href='favicon.ico' />
      <title>
      Форум
      </title>
<style type='text/css'>

body { font-weight: normal; font-family: Arial; font-size: 12px; color: #99FF99; background-color: #000000}
a:link { text-decoration: underline; color : #D3ECFF}
a:active { text-decoration: underline; color : #2F3528 }
a:visited { text-decoration: underline; color : #31F7D4}
a:hover { text-decoration: none; font-size: 12px; color : #E4F992 }
div { margin: 1px 0px 1px 0px; padding: 0px 0px 0px 0px;font-size: 14px; font-weight: normal;}  
table { margin: 1px 1px 1px 1px; padding: 1px 1px 1px 1px; font-size: 13px; font-weight: normal;}

.a {background-color: #000022;  text-align: left; font-size: 13px;font-weight: normal; color: #99FF99; border-left:1px solid #FCFCFC; border-right:1px solid #FCFCFC; border-bottom:1px solid #FCFCFC; border-top:1px solid #FCFCFC;}
.b {background-color: #000033;  text-align: left; font-size: 12px; color: #D9F51E; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
.c {background-color: #000044;  text-align: left; font-size: 12px;  border-left:0px solid #FCFCFC; border-right:0px solid #FCFCFC; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
.d {background-color: $fon;  text-align: left; font-size: 12px; color: olive; border-left:0px solid #FCFCFC; border-right:0px solid #FCFCFC; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
</style>
      </head>
      <body><div class = 'a' ><center><b>Форум</b></center><br/><b>$type1[text]</b><hr/>";
            $i = 1;
            while ($arr = mysql_fetch_array($tema))
            {
                $d = $i / 2;
                $d1 = ceil($d);
                $d2 = $d1 - $d;
                $d3 = ceil($d2);
                if ($d3 == 0)
                {
                    $div = "<div class='b'>";
                } else
                {
                    $div = "<div class='c'>";
                }
                $arr[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $arr[text]);
                $arr[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $arr[text]);

                if (stristr($arr[text], "<a href="))
                {
                    $arr[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                        "<a href='\\1\\3'>\\3</a>", $arr[text]);
                } else
                {
                    $arr[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $arr[text]);
                }
                if (!empty($arr[to]))
                {
                    $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[to], $arr[text]</div>";
                } else
                {
                    $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[text]</div>";
                }
                $text = "$text $stroka";
                ++$i;
            }
            $text = "$text<center><b>$copyright</b></center><br/></div></body></html>";
            $num = "$realtime$id";
            $fp = fopen("temtemp/$num.xml", "a+");
            flock($fp, LOCK_EX);
            fputs($fp, "$text\r\n");
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            @chmod("$fp", 0777);
            @chmod("temtemp/$num.xml", 0777);
            echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
            break;

        case "htm":
            $text = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'><html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<link rel='shortcut icon' href='favicon.ico'><title>Форум</title>
<style type='text/css'>
body { font-weight: normal; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #FFFFFF; background-color: #000000 }
a:link { text-decoration: underline; color : #999999 }
a:active { text-decoration: underline; color : #FFFFFF }
a:visited { text-decoration: underline; color : #333333 }
a:hover { text-decoration: none; font-size: 14px; color : #FFFFFF }
div { margin: 1px 0px 1px 0px; padding: 5px 5px 5px 5px;}  
table { margin: 1px 0px 1px 0px; padding: 1px 1px 1px 1px; font-size: 13px;}
.a {margin: 0px; border-top: 7px solid #000046; border-left: 7px solid #000034; border-right: 7px solid #000034; border-bottom: 7px solid #000015; padding: 5px; vertical-align: middle; background-color: #000022;  text-align: center; font-size: 15px; color: #FFFFFF;} 
.b {margin: 0px; border-top: 7px solid #000055; border-left: 7px solid #000049; border-right: 7px solid #000049; border-bottom: 7px solid #000019; padding: 5px; vertical-align: middle; background-color: #000033;  text-align: left; font-size: 13px; color: #FFFFFF; }
.c {margin: 0px; border-top: 7px solid #000077; border-left: 7px solid #000059; border-right: 7px solid #000059; border-bottom: 7px solid #000029; padding: 5px; vertical-align: middle; background-color: #000049;  text-align: left; font-size: 13px; color: #FFFFFF; }
.d {background-color: $fon;  text-align: left; font-size: 13px; color: olive; border-left:0px solid #FCFCFC; border-right:0px solid #FCFCFC; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
</style></head>
      <body><div class = 'a' ><center><b>Форум</b></center><br/><b>$type1[text]</b><br/>";
            $i = 1;
            while ($arr = mysql_fetch_array($tema))
            {
                $d = $i / 2;
                $d1 = ceil($d);
                $d2 = $d1 - $d;
                $d3 = ceil($d2);
                if ($d3 == 0)
                {
                    $div = "<div class='b'>";
                } else
                {
                    $div = "<div class='c'>";
                }
                $arr[text] = tegi($arr[text]);
                if (!empty($arr[to]))
                {
                    $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[to], $arr[text]</div>";
                } else
                {
                    $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[text]</div>";
                }
                $text = "$text $stroka";
                ++$i;
            }
            $text = "$text<center><b>$copyright</b></center><br/></div></body></html>";
            $num = "$realtime$id";
            $fp = fopen("temtemp/$num.htm", "a+");
            flock($fp, LOCK_EX);
            fputs($fp, "$text\r\n");
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            @chmod("$fp", 0777);
            @chmod("temtemp/$num.htm", 0777);
            echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
            break;
    }
} else
{
    echo "Выберите формат<br/><form action='?act=tema&amp;id=" . $id . "' method='post'><br/><select name='mod'>
	<option value='txt'>.txt</option>
	<option value='xml'>.xml</option>
	<option value='htm'>.htm</option>
	</select><br/>
<input type='submit' name='submit' value='Ok!'/><br/></form>";
}

?>