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

if ($rights >= 6) {
    if ($_GET['id'] == "") {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    $typ = mysql_query("select * from `gallery` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if (isset($_GET['yes'])) {
        switch ($ms['type']) {
            case "al":
                $ft = mysql_query("select * from `gallery` where `type`='ft' and `refid`='" . $id . "';");
                while ($ft1 = mysql_fetch_array($ft)) {
                    $km = mysql_query("select * from `gallery` where type='km' and refid='" . $ft1['id'] . "';");
                    while ($km1 = mysql_fetch_array($km)) {
                        mysql_query("delete from `gallery` where `id`='" . $km1['id'] . "';");
                    }
                    unlink("foto/$ft1[name]");
                    mysql_query("delete from `gallery` where `id`='" . $ft1['id'] . "';");
                }
                mysql_query("delete from `gallery` where `id`='" . $id . "';");
                header("location: index.php?id=$ms[refid]");
                break;

            case "rz":
                $al = mysql_query("select * from `gallery` where type='al' and refid='" . $id . "';");
                while ($al1 = mysql_fetch_array($al)) {
                    $ft = mysql_query("select * from `gallery` where type='ft' and refid='" . $al1['id'] . "';");
                    while ($ft1 = mysql_fetch_array($ft)) {
                        $km = mysql_query("select * from `gallery` where type='km' and refid='" . $ft1['id'] . "';");
                        while ($km1 = mysql_fetch_array($km)) {
                            mysql_query("delete from `gallery` where `id`='" . $km1['id'] . "';");
                        }
                        unlink("foto/$ft1[name]");
                        mysql_query("delete from `gallery` where `id`='" . $ft1['id'] . "';");
                    }
                    mysql_query("delete from `gallery` where `id`='" . $al1['id'] . "';");
                }
                mysql_query("delete from `gallery` where `id`='" . $id . "';");
                header("location: index.php");
                break;

            default:
                echo "ERROR<br/><a href='index.php'>Back</a><br/>";
                require_once('../incfiles/end.php');
                exit;
                break;
        }
    } else {
        switch ($ms['type']) {
            case "al":
                echo $lng['delete_confirmation'] . " $ms[text]?<br/>";
                break;

            case "rz":
                echo $lng['delete_confirmation'] . " $ms[text]?<br/>";
                break;

            default:
                echo "ERROR<br/><a href='index.php'>" . $lng['to_gallery'] . "</a><br/>";
                require_once('../incfiles/end.php');
                exit;
                break;
        }
        echo "<a href='index.php?act=del&amp;id=" . $id . "&amp;yes'>" . $lng['delete'] . "</a> | <a href='index.php?id=" . $id . "'>" . $lng['cancel'] . "</a><br/>";
    }
} else {
    header("location: index.php");
}

?>