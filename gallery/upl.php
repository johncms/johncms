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

if (!$user_id || $rights < 6) {
    header("location: index.php");
    exit;
}
if (empty($_GET['id'])) {
    echo "ERROR<br/><a href='index.php'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}

$type = mysql_query("select * from `gallery` where id='$id'");
$ms = mysql_fetch_array($type);
if ($ms['type'] != "al") {
    echo "ERROR<br/><a href='index.php'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}
$rz = mysql_query("select * from `gallery` where type='rz' and id='" . $ms['refid'] . "'");
$rz1 = mysql_fetch_array($rz);
if ((!empty($_SESSION['uid']) && $rz1['user'] == 1 && $ms['text'] == $login) || $rights >= 6) {
    $dopras = array (
        "gif",
        "jpg",
        "png"
    );
    $tff = implode(" ,", $dopras);
    $fotsize = $set['flsz'] / 5;
    echo '<h3>' . $lng_gal['upload_photo'] . "</h3>" . $lng_gal['allowed_types'] . ": $tff<br/>" . $lng_gal['maximum_weight'] . ": $fotsize кб.<br/><form action='index.php?act=load&amp;id=" . $id .
        "' method='post' enctype='multipart/form-data'><p>" . $lng_gal['select_photo'] . ":<br/><input type='file' name='fail'/></p><p>" . $lng['description'] . ":<br/><textarea name='text'></textarea></p><p><input type='submit' value='" . $lng['sent'] . "'/></p></form><a href='index.php?id="
        . $id . "'>" . $lng['back'] . "</a>";
} else {
    header("location: index.php");
}

?>
