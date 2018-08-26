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
    if (!$id) {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    $stmt = $db->query("select * from `gallery` where id='" . $id . "' AND `type` = 'ft' LIMIT 1;");
    if (!$stmt->rowCount()) {
        echo "ERROR<br/><a href='index.php'>Back</a><br/>";
        require_once('../incfiles/end.php');
        exit;
    }
    $ms = $stmt->fetch();
    if (isset($_GET['yes'])) {
        $stmt = $db->query("select * from `gallery` where type='km' and refid='" . $id . "';");
        while ($km1 = $stmt->fetch()) {
            $db->exec("delete from `gallery` where `id`='" . $km1['id'] . "';");
        }
        unlink("foto/$ms[name]");
        $db->exec("delete from `gallery` where `id`='" . $id . "';");
        header("location: index.php?id=$ms[refid]"); exit;
    } else {
        echo $lng['delete_confirmation'] . "<br/>";
        echo "<a href='index.php?act=delf&amp;id=" . $id . "&amp;yes'>" . $lng['delete'] . "</a> | <a href='index.php?id=" . $ms['refid'] . "'>" . $lng['cancel'] . "</a><br/>";
    }
}

?>