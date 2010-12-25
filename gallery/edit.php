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
    switch ($ms['type']) {
        case "al":
            if (isset($_POST['submit'])) {
                $text = functions::check($_POST['text']);
                mysql_query("update `gallery` set text='" . $text . "' where id='" . $id . "';");
                header("location: index.php?id=$id");
            } else {
                echo $lng_gal['edit_album'] . "<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] .
                    "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>" . $lng['back'] . "</a><br/>";
            }
            break;

        case "rz":
            if (isset($_POST['submit'])) {
                $text = functions::check($_POST['text']);
                if (!empty($_POST['user'])) {
                    $user = intval($_POST['user']);
                } else {
                    $user = 0;
                }
                mysql_query("update `gallery` set text='" . $text . "', user='" . $user . "' where id='" . $id . "';");
                header("location: index.php?id=$id");
            } else {
                echo $lng_gal['edit_section'] . "<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . $ms['text'] . "'/><br/>";
                echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>" . $lng['back'] . "</a><br/>";
            }
            break;

        default:
            echo "ERROR<br/><a href='index.php'>Back</a><br/>";
            require_once('../incfiles/end.php');
            exit;
            break;
    }
} else {
    header("location: index.php");
}

?>