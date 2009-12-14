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

if (($rights == 4 || $rights >= 6) && (!empty ($_GET['cat']))) {
    $cat = $_GET['cat'];
    $delcat = mysql_query("select * from `download` where type = 'cat' and refid = '" . $cat . "';");
    $delcat1 = mysql_num_rows($delcat);
    if ($delcat1 == 0) {
        provcat($cat);
        $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
        $adrdir = mysql_fetch_array($cat1);
        deletcat("$adrdir[adres]/$adrdir[name]");
        echo "Каталог удалён<br/>";
    }
}

?>