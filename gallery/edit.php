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
    $error = true;
    if ($id) {
        $stmt = $db->query('SELECT * FROM `gallery` WHERE `id`="' . $id . '" AND `type` IN ("al", "rz") LIMIT 1');
        if ($stmt->rowCount()) {
            $error = false;
            $ms = $stmt->fetch();
        }
    }
    if ($error) {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    if (isset($_POST['submit'])) {
        $text = isset($_POST['text']) ? functions::checkin($_POST['text']) : '';
        if ($text) {
            $stmt = $db->prepare("UPDATE `gallery` SET `text`= ? where `id`='" . $id . "' LIMIT 1");
            $stmt->execute([$text]);
            header("location: index.php?id=$id"); exit;
        } else {
            echo functions::display_error($lng['error_empty_fields']);
        }
    } else {
        echo $lng_gal['edit_album'] . "<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'><input type='text' name='text' value='" . _e($ms['text']) .
            "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>" . $lng['back'] . "</a><br/>";
    }
} else {
    header("location: index.php"); exit;
}
