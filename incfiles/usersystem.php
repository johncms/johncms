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

defined('_IN_JOHNCMS') or die('Error:restricted access');

// Фиксируем перемещения пользователя по сайту (кто кде?)
if ($user_id && $user_ps)
    $user1 = $login;
else
    $user1 = "Guestuser";

if ($headmod != "forum" && $headmod != "chat")
{
    mysql_query("insert into `count` values(0,'" . $ipp . "','" . $agn . "','" . $realtime . "','" . $headmod . "','" . $user1 . "','0');");
}

if (isset($ban))
    echo '<div class="alarm">БАН&nbsp;<a href="' . $home . '/index.php?mod=ban">Подробно</a></div>';

// Проверяем, есть ли новые письма
if ($headmod != "pradd")
{
    $newl = mysql_query("select * from `privat` where user = '" . $login . "' and type = 'in' and chit = 'no';");
    $countnew = mysql_num_rows($newl);
    if ($countnew > 0)
    {
        echo "<div class=\"rmenu\" style='text-align: center'><a href='$home/str/pradd.php?act=in&amp;new'><b><font color='red'>Вам письмо: $countnew</font></b></a></div>";
    }
}

?>