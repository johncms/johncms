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

defined('_IN_JOHNCMS') or die('Error: restricted access');
require_once("../incfiles/head.php");

$error = true;
if ($id) {
    $stmt = $db->query("SELECT * FROM `download` WHERE `id` = '" . $id . "' AND `type` = 'file' LIMIT 1");
    if ($stmt->rowCount()) {
        $error = false;
        $ms = $stmt->fetch();
    }
}
if ($error) {
    echo "ERROR<br/><a href='index.php?'>Back</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}
if (isset($_SESSION['rat']) && $_SESSION['rat'] == $id) {
    echo $lng_dl['already_rated'] . "<br/><a href='index.php?act=view&amp;file=" . $id . "'>" . $lng['back'] . "</a><br/>";
    require_once('../incfiles/end.php');
    exit;
}

if (isset($_POST['rat'])
    && ctype_digit($_POST['rat'])
    && $_POST['rat'] > 0
    && $_POST['rat'] < 11
) {
    $rat = intval($_POST['rat']);
    if (!empty($ms['soft'])) {
        $tmp = unserialize($ms['soft']);
        $rating['vote'] = $tmp['vote'] + $rat;
        $rating['count'] = $tmp['count'] + 1;
    } else {
        $rating['vote'] = $rat;
        $rating['count'] = 1;
    }

    $_SESSION['rat'] = $id;
    $stmt = $db->prepare("UPDATE `download` SET `soft` = ? WHERE `id` = '" . $id . "' LIMIT 1");
    $stmt->execute([
        serialize($rating)
    ]);
}

echo $lng_dl['vote_adopted'] . "<br/><a href='index.php?act=view&amp;file=" . $id . "'>" . $lng['back'] . "</a><br/>";