<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

require_once("../incfiles/core.php");
$lng_pm = core::load_lng('pm');
$headmod = 'ignor';
$textl = 'Block List';
require_once('../incfiles/head.php');

if (!empty($_SESSION['uid'])) {
    if (!empty($_GET['act'])) {
        $act = $_GET['act'];
    }
    switch ($act) {
        case "add":
            echo '<div class="phdr">' . $lng_pm['add_to_ignor'] . '</div>';
            echo "<form action='ignor.php?act=edit&amp;add=1' method='post'>" . $lng_pm['enter_nick'] . "<br/>";
            echo "<input type='text' name='nik' value='' /><br/><input type='submit' value='" . $lng['add'] . "' /></form>";
            echo '<a href="ignor.php">' . $lng['back'] . '</a><br/>';
            break;

        case "edit":
            if (!empty($_POST['nik'])) {
                $nik = functions::check($_POST['nik']);
            } elseif (!empty($_GET['nik'])) {
                $nik = functions::check($_GET['nik']);
            } else {
                if (empty($_GET['id'])) {
                    echo "ERROR!<br/><a href='ignor.php'>Back</a><br/>";
                    require_once('../incfiles/end.php');
                    exit;
                }
                $nk = mysql_query("select * from `users` where id='" . $id . "';");
                $nk1 = mysql_fetch_array($nk);
                $nik = $nk1['name'];
            }
            if (!empty($_GET['add'])) {
                $add = intval($_GET['add']);
            }
            $adc = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $nik . "';");
            $adc1 = mysql_num_rows($adc);
            $addc = mysql_query("select * from `users` where name='" . $nik . "';");
            $addc2 = mysql_fetch_array($addc);
            $addc1 = mysql_num_rows($addc);
            if ($add == 1) {
                if ($addc2['rights'] >= 1) {
                    echo '<p>' . $lng_pm['block_adm'] . '<br/><a href="ignor.php">' . $lng['back'] . '</a></p>';
                    require_once('../incfiles/end.php');
                    exit;
                }
                if ($adc1 == 0) {
                    if ($addc1 == 1) {
                        mysql_query("insert into `privat` values(0,'" . $foruser . "','','" . time() . "','','','','','0','" . $login . "','','" . $nik . "','');");
                        echo $lng_pm['block_added'] . "<br/>";
                    } else {
                        echo $lng['error_user_not_exist'] . "<br/>";
                    }
                } else {
                    echo $lng_pm['contact_exists'] . "<br/>";
                }
            } else {
                if ($adc1 == 1) {
                    if ($addc1 == 1) {
                        mysql_query("delete from `privat` where me='" . $login . "' and ignor='" . $nik . "';");
                        echo $lng_pm['block_deleted'] . "<br/>";
                    } else {
                        echo $lng['error_user_not_exist'] . "<br/>";
                    }
                } else {
                    echo $lng_pm['contact_does_not_exists'] . "<br/>";
                }
            }
            echo "<a href='?'>" . $lng_pm['block_list'] . "</a><br />";
            break;

        case 'del':
            $req = mysql_query("SELECT * FROM `privat` WHERE `id`='" . $id . "' AND `me`='" . $login . "';");
            $res = mysql_fetch_array($req);
            if (mysql_num_rows($req) == 1) {
                mysql_query("DELETE FROM `privat` WHERE `id`='" . $id . "';");
                echo '<p>' . $lng_pm['block_deleted'] . '</p>';
            } else {
                echo '<p>ERROR</p>';
            }
            echo '<a href="?">' . $lng_pm['block_list'] . '</a><br />';
            break;

        default:
            echo '<div class="phdr"><b>' . $lng_pm['block_list'] . '</b></div>';
            $ig = mysql_query("select * from `privat` where me='" . $login . "' and ignor!='';");
            if (mysql_num_rows($ig)) {
                while ($mass = mysql_fetch_array($ig)) {
                    $uz = mysql_query("select * from `users` where name='$mass[ignor]';");
                    $mass1 = mysql_fetch_array($uz);
                    echo '<div class="menu">' . $mass['ignor'];
                    $ontime = $mass1['lastdate'];
                    $ontime2 = $ontime + 300;
                    if (time() > $ontime2) {
                        echo '<font color="#FF0000"> [Off]</font>';
                    } else {
                        echo '<font color="#00AA00"> [ON]</font>';
                    }
                    echo ' <a href="ignor.php?act=del&amp;id=' . $mass['id'] . '">[X]</a></div>';
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr"><a href="?act=add">' . $lng_pm['add_to_ignor'] . '</a></div>';
            break;
    }
}
echo "<p><a href='profile.php?act=office'>" . $lng['personal'] . "</a></p>";

require_once('../incfiles/end.php');

?>