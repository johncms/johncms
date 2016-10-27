<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

$textl = _t('Mail');
require_once('../system/head.php');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

if ($id) {
    $req = $db->query("SELECT * FROM `cms_mail` WHERE (`user_id`='$user_id' OR `from_id`='$user_id') AND `id` = '$id' AND `file_name` != '' AND `delete`!='$user_id' LIMIT 1");

    if (!$req->rowCount()) {
        //Выводим ошибку
        echo functions::display_error(_t('Such file does not exist'));
        require_once("../incfiles/end.php");
        exit;
    }

    $res = $req->fetch();

    if (file_exists('../files/mail/' . $res['file_name'])) {
        $db->exec("UPDATE `cms_mail` SET `count` = `count`+1 WHERE `id` = '$id' LIMIT 1");
        header('Location: ../files/mail/' . $res['file_name']);
        exit;
    } else {
        echo functions::display_error(_t('Such file does not exist'));
    }
} else {
    echo functions::display_error(_t('No file selected'));
}
