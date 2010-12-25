<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
$textl = $lng_profile['my_office'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Проверяем права доступа
-----------------------------------------------------------------
*/
if ($user['id'] != $user_id) {
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/*
-----------------------------------------------------------------
Личный кабинет пользователя
-----------------------------------------------------------------
*/
$total_photo = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_album_files` WHERE `user_id` = '$user_id'"), 0);
echo '<div class="phdr"><b>' . $lng_profile['my_office'] . '</b></div>' .
    '<div class="list2"><p>' .
    '<div><img src="../images/contacts.png" width="16" height="16"/>&#160;<a href="profile.php">' . $lng_profile['my_profile'] . '</a></div>' .
    '<div><img src="../images/rate.gif" width="16" height="16"/>&#160;<a href="profile.php?act=stat">' . $lng['statistics'] . '</a></div>' .
    '</p><p>' .
    '<div><img src="../images/photo.gif" width="16" height="16"/>&#160;<a href="album.php?act=list">' . $lng['photo_album'] . '</a>&#160;(' . $total_photo . ')</div>' .
    '<div><img src="../images/guestbook.gif" width="16" height="16"/>&#160;<a href="profile.php?act=guestbook">' . $lng['guestbook'] . '</a>&#160;(' . $user['comm_count'] . ')</div>';
//echo '<div><img src="../images/pt.gif" width="16" height="16"/>&#160;<a href="">' . $lng['blog'] . '</a>&#160;(0)</div>';
if ($rights >= 1) {
    $guest = functions::stat_guestbook(2);
    echo '</p><p>' .
        '<div><img src="../images/admin.png" width="16" height="16"/>&#160;<a href="../guestbook/index.php?act=ga&amp;do=set">' . $lng['admin_club'] . '</a> (<span class="red">' . $guest . '</span>)</div>';
}
echo '</p></div><div class="menu"><p>';
// Блок почты
$count_mail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in'"), 0);
$count_newmail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '" . $login . "' AND `type` = 'in' AND `chit` = 'no'"), 0);
$count_sentmail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `author` = '$login' AND `type` = 'out'"), 0);
$count_sentunread = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `author` = '$login' AND `type` = 'out' AND `chit` = 'no'"), 0);
$count_files = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `attach` != ''"), 0);
echo '<h3><img src="../images/mail.png" width="16" height="16" class="left" />&#160;' . $lng_profile['my_mail'] . '</h3><ul>' .
    '<li><a href="pradd.php?act=in">' . $lng_profile['received'] . '</a>&#160;(' . $count_mail . ($count_newmail ? '&#160;/&#160;<span class="red"><a href="pradd.php?act=in&amp;new">+' . $count_newmail . '</a></span>' : '') . ')</li>' .
    '<li><a href="pradd.php?act=out">' . $lng_profile['sent'] . '</a>&#160;(' . $count_sentmail . ($count_sentunread ? '&#160;/&#160;<span class="red">' . $count_sentunread . '</span>' : '') . ')</li>';
if (!$ban['1'] && !$ban['3'])
    echo '<p><form action="pradd.php?act=write" method="post"><input type="submit" value=" ' . $lng['write'] . ' " /></form></p>';
// Блок контактов
$count_contacts = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `me` = '$login' AND `cont` != ''"), 0);
$count_ignor = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `me` = '$login' AND `ignor` != ''"), 0);
echo '</ul><h3><img src="../images/users.png" width="16" height="16" class="left" />&#160;' . $lng['contacts'] . '</h3><ul>' .
    '<li><a href="cont.php">' . $lng['contacts'] . '</a>&#160;(' . $count_contacts . ')</li>' .
    '<li><a href="ignor.php">' . $lng['blocking'] . '</a>&#160;(' . $count_ignor . ')</li>' .
    '</ul></p></div>';
// Блок настроек
echo '<div class="bmenu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&#160;' . $lng_profile['my_settings'] . '</h3><ul>' .
    '<li><a href="profile.php?act=settings">' . $lng['system_settings'] . '</a></li>' .
    '<li><a href="profile.php?act=edit">' . $lng_profile['profile_edit'] . '</a></li>' .
    '<li><a href="profile.php?act=password">' . $lng['change_password'] . '</a></li>';
if ($rights >= 1)
    echo '<li><span class="red"><a href="../' . $set['admp'] . '/index.php"><b>' . $lng['admin_panel'] . '</b></a></span></li>';
echo '</ul></p></div>';
?>
