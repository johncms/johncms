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

define('_IN_JOHNCMS', 1);

require_once('../incfiles/core.php');
$lng_pm = $core->load_lng('pm');
$headmod = 'contacts';
$textl = $lng['contacts'];
require('../incfiles/head.php');

if ($user_id) {
    switch ($act) {
        case 'add':
            /*
            -----------------------------------------------------------------
            Добавить контакт
            -----------------------------------------------------------------
            */
            echo '<form action="cont.php?act=edit&amp;add=1" method="post">' . $lng_pm['enter_nick'] . '<br/>' .
                 '<input type="text" name="nik" /><br/>' .
                 '<input type="submit" value="' . $lng['add'] . '" /></form>' .
                 '<p><a href="?">' . $lng['back'] . '</a></p>';
            break;

        case 'edit':
            if (!empty($_POST['nik'])) {
                $nik = functions::check($_POST['nik']);
            } elseif (!empty($_GET['nik'])) {
                $nik = functions::check($_GET['nik']);
            } else {
                if (empty($_GET['id'])) {
                    echo "ERROR!<br/><a href='cont.php'>" . $lng['back'] . "</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }

                $id = intval($_GET['id']);
                $nk = mysql_query("select * from `users` where id='" . $id . "';");
                $nk1 = mysql_fetch_array($nk);
                $nik = $nk1['name'];
            }
            if (!empty($_GET['add'])) {
                $add = intval($_GET['add']);
            }
            $adc = mysql_query("select * from `privat` where me='" . $login . "' and cont='" . $nik . "';");
            $adc1 = mysql_num_rows($adc);
            $addc = mysql_query("select * from `users` where name='" . $nik . "';");
            $addc1 = mysql_num_rows($addc);
            if ($add == 1) {
                if ($adc1 == 0) {
                    if ($addc1 == 1) {
                        mysql_query("insert into `privat` values(0,'" . $foruser . "','','" . $realtime . "','','','','','0','" . $login . "','" . $nik . "','','');");
                        echo $lng_pm['contact_added'] . "<br/>";
                    } else {
                        echo $lng['error_user_not_exist'] . "<br/>";
                    }
                } else {
                    echo $lng_pm['contact_exists'] . "<br/>";
                }
            } else {
                if ($adc1 == 1) {
                    if ($addc1 == 1) {
                        mysql_query("delete from `privat` where me='" . $login . "' and cont='" . $nik . "';");
                        echo $lng_pm['contact_deleted'] . "<br/>";
                    } else {
                        echo $lng['error_user_not_exist'] . "<br/>";
                    }
                } else {
                    echo $lng_pm['contact_does_not_exists'] . "<br/>";
                }
            }
            echo "<a href='?'>" . $lng['contacts'] . "</a><br />";
            break;

        default:
            /*
            -----------------------------------------------------------------
            Список контактов
            -----------------------------------------------------------------
            */
            echo '<div class="phdr"><b>' . $lng['contacts'] . '</b></div>';
            $contacts = mysql_query("select * from `privat` where me='$login' and cont!='';");
            if (mysql_num_rows($contacts)) {
                while ($mass = mysql_fetch_array($contacts)) {
                    $uz = mysql_query("select * from `users` where name='$mass[cont]';");
                    $mass1 = mysql_fetch_array($uz);
                    echo '<div class="menu"><a href="pradd.php?act=write&amp;adr=' . $mass1['id'] . '">' . $mass['cont'] . '</a>';
                    $ontime = $mass1['lastdate'];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2) echo '<font color="#FF0000"> [Off]</font>';
                    else echo '<font color="#00AA00"> [ON]</font>';
                    echo ' <a href="cont.php?act=edit&amp;id=' . $mass1['id'] . '">[X]</a></div>';
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr"><a href="?act=add">' . $lng['add'] . '</a></div>';
            break;
    }
}
echo '<p><a href="profile.php?act=office">' . $lng['personal'] . '</a></p>';
require('../incfiles/end.php');

?>