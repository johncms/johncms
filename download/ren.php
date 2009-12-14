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
if ($rights == 4 || $rights >= 6) {
    if (empty ($_GET['cat'])) {
        echo "Ошибка!<br /><a href='?'>В загрузки</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $cat = intval(trim($_GET['cat']));
    provcat($cat);
    $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
    $adrdir = mysql_fetch_array($cat1);
    $namedir = "$adrdir[adres]/$adrdir[name]";
    if (isset ($_POST['submit'])) {
        if (!empty ($_POST['newrus'])) {
            $newrus = check(trim($_POST['newrus']));
        }
        else {
            $newrus = "$adrdir[text]";
        }

        if (mysql_query("update `download` set text='" . $newrus . "' where id='" . $cat . "';")) {
            echo "Название для отображения изменено<br/>";
        }
    }
    else {
        echo "<form action='?act=ren&amp;cat=" . $cat . "' method='post'>";
        echo "Отображать каталог как: <br/><input type='text' name='newrus' value='" . $adrdir[text] . "'/><br/>";
        echo "<input type='submit' name='submit' value='Изменить'/></form><br/>";
    }
}
else {
    echo "Нет доступа!";
}
echo "&#187;<a href='?cat=" . $cat . "'>В папку</a><br/>";

?>