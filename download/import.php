<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

require_once ("../incfiles/head.php");
if ($rights == 4 || $rights >= 6) {
    if (empty ($_GET['cat'])) {
        $loaddir = $loadroot;
    }
    else {
        $cat = intval($_GET['cat']);
        provcat($cat);
        $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
        $adrdir = mysql_fetch_array($cat1);
        $loaddir = "$adrdir[adres]/$adrdir[name]";
    }
    if (isset ($_POST['submit'])) {
        $url = trim($_POST['url']);
        $opis = functions::check($_POST['opis']);
        $newn = functions::check($_POST['newn']);
        $tipf = functions::format($url);
        if (eregi("[^a-z0-9.()+_-]", $newn)) {
            echo
            "В новом названии файла <b>$newn</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='?act=import&amp;cat="
            . $cat . "'>Повторить</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $import = "$loaddir/$newn.$tipf";
        $files = file("$import");
        if (!$files) {
            if (copy($url, $import)) {
                $ch = "$newn.$tipf";
                echo "Файл успешно загружен<br/>";
                mysql_query("insert into `download` values(0,'$cat','" . mysql_real_escape_string($loaddir) . "','" . time() . "','" . mysql_real_escape_string($ch) . "','file','','','','" . $opis . "','');");
            }
            else {
                echo "Загрузка файла не удалась!<br/>";
            }
        }
        else {
            echo "Ошибка, файл с таким именем уже существует в данной директории<br/>";
        }
    }
    else {
        echo "Загрузка по http<br/>";
        echo "<form action='?act=import&amp;cat=" . $cat . "' method='post'>";
        echo
        "Введите URL:<br/><input type='text' name='url' value='http://'/> <br/>Описание: <br/><textarea name='opis'></textarea><br/>Сохранить как(без расширения): <br/><input type='text' name='newn'/><br/>";
        echo "<input type='submit' name='submit' value='Загрузить'/></form><br/>";
    }
}
else {
    echo "Нет доступа!";
}
echo "&#187;<a href='?cat=" . $cat . "'>В папку</a><br/>";

?>