<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');
$textl = $lng['mail'];
require_once('../incfiles/head.php');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

if ($id) {
    $req = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `id` = '$id' AND `file_name` != '' AND `delete`!='$user_id' LIMIT 1");

    if (!$req->rowCount()) {
        //Выводим ошибку
        echo functions::display_error($lng_mail['file_does_not_exist']);
        require_once("../incfiles/end.php");
        exit;
    }

    $res = $req->fetch();

    if (file_exists('../files/mail/' . $res['file_name'])) {
        $db->exec("UPDATE `cms_mail` SET `count` = `count`+1 WHERE `id` = '$id' LIMIT 1");
        header('Location: ../files/mail/' . $res['file_name']);
        exit;
    } else {
        echo functions::display_error($lng_mail['file_does_not_exist']);
    }
} else {
    echo functions::display_error($lng_mail['file_is_not_chose']);
}
