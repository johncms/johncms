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

if (empty($_GET['id']))
{
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/><a href='index.php'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$id = intval($_GET['id']);
$fil = mysql_query("select * from `forum` where id='" . $id . "';");
$mas = mysql_fetch_array($fil);
if (!empty($mas['attach']))
{
    $tfl = strtolower(format(trim($mas['attach'])));
    $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
    if (in_array($tfl, $df))
    {
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/>&#187;<a href='index.php'>В форум</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (file_exists("./files/$mas[attach]"))
    {
        $dlcount = $mas['dlcount'] + 1;
        mysql_query("update `forum` set  `dlcount`='" . $dlcount . "' where id='" . $id . "';");
		header("location: ./files/$mas[attach]");
    }
} else
{
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/>&#187;<a href='index.php'>В форум</a><br/>";
}

?>