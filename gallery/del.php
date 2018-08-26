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
    $ms = $db->query("select * from `gallery` where id='" . $id . "' LIMIT 1;")->fetch();
    if (isset($_GET['yes'])) {
        switch ($ms['type']) {
            case "al":
                $stmt = $db->query("select * from `gallery` where `type`='ft' and `refid`='" . $id . "';");
                while ($ft1 = $stmt->fetch()) {
                    $stmt_2 = $db->query("select * from `gallery` where type='km' and refid='" . $ft1['id'] . "';");
                    while ($km1 = $stmt_2->fetch()) {
                        $db->exec("delete from `gallery` where `id`='" . $km1['id'] . "';");
                    }
                    unlink("foto/$ft1[name]");
                    $db->exec("delete from `gallery` where `id`='" . $ft1['id'] . "';");
                }
                $db->exec("delete from `gallery` where `id`='" . $id . "';");
                header("location: index.php?id=$ms[refid]"); exit;
                break;

            case "rz":
                $stmt = $db->query("select * from `gallery` where type='al' and refid='" . $id . "';");
                while ($al1 = $stmt->fetch()) {
                    $stmt_2 = $db->query("select * from `gallery` where type='ft' and refid='" . $al1['id'] . "';");
                    while ($ft1 = $stmt_2->fetch()) {
                        $stmt_3 = $db->query("select * from `gallery` where type='km' and refid='" . $ft1['id'] . "';");
                        while ($km1 = $stmt_3->fetch()) {
                            $db->exec("delete from `gallery` where `id`='" . $km1['id'] . "';");
                        }
                        unlink("foto/$ft1[name]");
                        $db->exec("delete from `gallery` where `id`='" . $ft1['id'] . "';");
                    }
                    $db->exec("delete from `gallery` where `id`='" . $al1['id'] . "';");
                }
                $db->exec("delete from `gallery` where `id`='" . $id . "';");
                header("location: index.php"); exit;
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
                echo $lng['delete_confirmation'] . " " . _e($ms['text']) . "?<br/>";
                break;

            case "rz":
                echo $lng['delete_confirmation'] . " " . _e($ms['text']) . "?<br/>";
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
    header("location: index.php"); exit;
}
