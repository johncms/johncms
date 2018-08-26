<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @license     http://johncms.com/license/
 * @author      http://johncms.com/about/
 * @version     VERSION.txt (see attached file)
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/**
 * Голосуем за фотографию
 */
if (!$img) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}

$ref = isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'album.php';

if ($db->query("SELECT COUNT(*) FROM `cms_album_votes` WHERE `user_id` = '$user_id' AND `file_id` = '$img'")->fetchColumn()) {
    header('Location: ' . $ref); exit;
}

$stmt = $db->query("SELECT * FROM `cms_album_files` WHERE `id` = '$img' AND `user_id` != '$user_id'");
if ($stmt->rowCount()) {
    $res = $stmt->fetch();

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

    header('Location: ' . $ref); exit;
} else {
    echo functions::display_error($lng['error_wrong_data']);
}
