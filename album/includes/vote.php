<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Голосуем за фотографию
if (!$img) {
    echo functions::display_error(_t('Wrong data'));
    require('../incfiles/end.php');
    exit;
}

$ref = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

$check = $db->query("SELECT * FROM `cms_album_votes` WHERE `user_id` = '$user_id' AND `file_id` = '$img' LIMIT 1");

if ($check->rowCount()) {
    header('Location: ' . $ref);
    exit;
}

$req = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` != '$user_id'");

if ($req->rowCount()) {
    $res = $req->fetch();

    switch ($mod) {
        case 'plus':
            /**
             * Отдаем положительный голос
             */
            $db->exec("INSERT INTO `cms_album_votes` SET
                `user_id` = '$user_id',
                `file_id` = '$img',
                `vote` = '1'
            ");
            $db->exec("UPDATE `cms_album_files` SET `vote_plus` = '" . ($res['vote_plus'] + 1) . "' WHERE `id` = '$img'");
            break;

        case 'minus':
            /**
             * Отдаем отрицательный голос
             */
            $db->exec("INSERT INTO `cms_album_votes` SET
                `user_id` = '$user_id',
                `file_id` = '$img',
                `vote` = '-1'
            ");
            $db->exec("UPDATE `cms_album_files` SET `vote_minus` = '" . ($res['vote_minus'] + 1) . "' WHERE `id` = '$img'");
            break;
    }

    header('Location: ' . $ref);
} else {
    echo functions::display_error(_t('Wrong data'));
}
