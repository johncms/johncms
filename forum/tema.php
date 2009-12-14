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

require_once ('../incfiles/head.php');
$delf = opendir("temtemp");
while ($tt = readdir($delf)) {
    if ($tt != "." && $tt != ".." && $tt != 'index.php') {
        $tm[] = $tt;
    }
}
closedir($delf);
$totalt = count($tm);
for ($it = 0; $it < $totalt; $it++) {
    $filtime[$it] = filemtime("temtemp/$tm[$it]");
    $tim = time();
    $ftime1 = $tim - 300;
    if ($filtime[$it] < $ftime1) {
        unlink("temtemp/$tm[$it]");
    }
}
if (empty ($_GET['id'])) {
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$type = mysql_query("select * from `forum` where id= '" . $id . "';");
$type1 = mysql_fetch_array($type);
$tip = $type1['type'];
if ($tip != "t") {
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
if (isset ($_POST['submit'])) {
    $tema = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close` != '1'") . " ORDER BY `id` ASC");
    $mod = intval($_POST['mod']);
    switch ($mod) {
        case 1 :
            ////////////////////////////////////////////////////////////
            // Сохраняем тему в текстовом формате                     //
            ////////////////////////////////////////////////////////////
            $text = $type1['text'] . "\r\n\r\n";
            while ($arr = mysql_fetch_assoc($tema)) {
                $txt_tmp = str_replace("[c]", "Цитата:{", $arr['text']);
                $txt_tmp = str_replace("[/c]", "}-Ответ:", $txt_tmp);
                $txt_tmp = str_replace("&quot;", "\"", $txt_tmp);
                $txt_tmp = str_replace("[l]", "", $txt_tmp);
                $txt_tmp = str_replace("[l/]", "-", $txt_tmp);
                $txt_tmp = str_replace("[/l]", "", $txt_tmp);
                $stroka = $arr['from'] . '(' . date("d.m.Y/H:i", $arr['time']) . ")\r\n" . $txt_tmp . "\r\n\r\n";
                $text .= $stroka;
            }
            $num = "$realtime$id";
            $fp = fopen("temtemp/$num.txt", "a+");
            flock($fp, LOCK_EX);
            fputs($fp, "$text\r\n");
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            @ chmod("$fp", 0777);
            @ chmod("temtemp/$num.txt", 0777);
            echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
            break;

        case 2 :
            ////////////////////////////////////////////////////////////
            // Сохраняем тему в формате HTML                          //
            ////////////////////////////////////////////////////////////
            $text =
            "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'><html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<title>Форум</title>
<style type='text/css'>
body { color: #000000; background-color: #FFFFFF }
div { margin: 1px 0px 1px 0px; padding: 5px 5px 5px 5px;}
.b {background-color: #FFFFFF; }
.c {background-color: #EEEEEE; }
.quote{font-size: x-small; padding: 2px 0px 2px 4px; color: #878787; border-left: 3px solid #c0c0c0;
}
</style></head>
      <body><p><b><u>$type1[text]</u></b></p>";
            $i = 1;
            while ($arr = mysql_fetch_array($tema)) {
                $d = $i / 2;
                $d1 = ceil($d);
                $d2 = $d1 - $d;
                $d3 = ceil($d2);
                if ($d3 == 0) {
                    $div = "<div class='b'>";
                }
                else {
                    $div = "<div class='c'>";
                }
                $txt_tmp = htmlentities($arr['text'], ENT_QUOTES, 'UTF-8');
                $txt_tmp = tags($txt_tmp);
                $txt_tmp = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $txt_tmp);
                $txt_tmp = str_replace("\r\n", "<br/>", $txt_tmp);
                $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr['time']) . ")<br/>$txt_tmp</div>";
                $text = "$text $stroka";
                ++$i;
            }
            $text = $text . '<p>Данная тема была скачана с форума сайта: <b>' . $copyright . '</b></p></body></html>';
            $num = "$realtime$id";
            $fp = fopen("temtemp/$num.htm", "a+");
            flock($fp, LOCK_EX);
            fputs($fp, "$text\r\n");
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
            @ chmod("$fp", 0777);
            @ chmod("temtemp/$num.htm", 0777);
            echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
            break;
    }
}
else {
    echo "<p>Выберите формат<br/><form action='?act=tema&amp;id=" . $id .
    "' method='post'><br/><select name='mod'>
	<option value='1'>.txt</option>
	<option value='2'>.htm</option>
	</select><input type='submit' name='submit' value='Ok!'/><br/></form></p>";
}

?>