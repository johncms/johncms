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
    if (!empty($_GET['cat']))
    {
        $cat = $_GET['cat'];
    }
    if (isset($_POST['submit']))
    {
        if (empty($cat))
        {
            $droot = $loadroot;
        } else
        {
            $cat = intval(trim($cat));
            provcat($cat);
            $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
            $adrdir = mysql_fetch_array($cat1);
            $droot = "$adrdir[adres]/$adrdir[name]";
        }
        $drn = check(trim($_POST['drn']));
        $rusn = check(trim($_POST['rusn']));
        $mk = mkdir("$droot/$drn", 0777);
        if ($mk == true)
        {
            chmod("$droot/$drn", 0777);
            echo "Папка создана<br/>";
            mysql_query("insert into `download` values(0,'" . $cat . "','" . $droot . "','" . $realtime . "','" . $drn . "','cat','','','','" . $rusn . "','');");
            $categ = mysql_query("select * from `download` where type = 'cat' and name='$drn' and refid = '" . $cat . "';");
            $newcat = mysql_fetch_array($categ);
            echo "&#187;<a href='?cat=" . $newcat[id] . "'>В папку</a><br/>";
        } else
        {
            echo "Ошибка<br/>";
        }
    } else
    {
        echo "<form action='?act=makdir&amp;cat=" . $_GET['cat'] . "' method='post'>
         Название папки:<br/>
         <input type='text' name='drn'/><br/>
         Название для отображения:<br/>
         <input type='text' name='rusn'/><br/>

         <input type='submit' name='submit' value='Создать'/><br/>
         </form>";
    }
} else
{
    echo "Нет доступа!<br/>";
}
echo "&#187;<a href='?'>К категориям</a><br/>";

?>