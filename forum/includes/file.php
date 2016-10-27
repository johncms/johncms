<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($id) {
    /** @var PDO $db */
    $db = App::getContainer()->get(PDO::class);
    $error = false;

    // Скачивание прикрепленного файла Форума
    $req = $db->query("SELECT * FROM `cms_forum_files` WHERE `id` = '$id'");

    if ($req->rowCount()) {
        $res = $req->fetch();

        if (file_exists('../files/forum/attach/' . $res['filename'])) {
            $dlcount = $res['dlcount'] + 1;
            $db->exec("UPDATE `cms_forum_files` SET  `dlcount` = '$dlcount' WHERE `id` = '$id'");
            header('location: ../files/forum/attach/' . $res['filename']);
        } else {
            $error = true;
        }
    } else {
        $error = true;
    }

    if ($error) {
        require('../system/head.php');
        echo functions::display_error(_t('File does not exist'), '<a href="index.php">' . _t('Forum') . '</a>');
        require('../incfiles/end.php');
        exit;
    }
} else {
    header('location: index.php');
}
