<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);
$headmod = 'library';
require_once('../incfiles/core.php');
$textl = $lng['library'] . ' - Конвертер';
require_once('../incfiles/head.php');

if ($rights != 9) {
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

if ($rights == 9) {
    // Очищаем
    mysql_query("TRUNCATE `library_cats`");
    mysql_query("TRUNCATE `library_texts`");
    mysql_query("TRUNCATE `library_tags`");
    mysql_query("TRUNCATE `cms_library_comments`");

    // Переносим структуру каталогов
    $sql = mysql_query("SELECT `id`, `refid`, `text`, `ip` FROM `lib` WHERE `type`='cat'");

    while ($row = mysql_fetch_assoc($sql)) {
        mysql_query("
          INSERT INTO `library_cats`
          SET
            `id`          = " . $row['id'] . ",
            `parent`      = " . $row['refid'] . ",
            `dir`         = " . $row['ip'] . ",
            `pos`         = " . $row['id'] . ",
            `name`        = '" . $row['text'] . "',
            `description` = ''
        ");
    }

    // Переносим статьи
    $sql = mysql_query("SELECT `id`, `refid`, `text`, `announce`, `avtor`, `name`, `moder`, `count`, `time` FROM `lib` WHERE `type`='bk'");

    while ($row = mysql_fetch_assoc($sql)) {
        $req = mysql_query("SELECT `id` FROM `users` WHERE `name`='" . $row['avtor'] . "' LIMIT 1");

        if(mysql_num_rows($req)){
            $res = mysql_fetch_assoc($req);
            $uploader_id = $res['id'];
        } else {
            $uploader_id = 0;
        }

        mysql_query("
          INSERT INTO `library_texts`
          SET
            `id`          = " . $row['id'] . ",
            `cat_id`      = " . $row['refid'] . ",
            `name`        = '" . $row['name'] . "',
            `announce`    = '" . mysql_real_escape_string($row['announce']) . "',
            `text`        = '" . mysql_real_escape_string($row['text']) . "',
            `uploader`    = '" . $row['avtor'] . "',
            `uploader_id` = '" . $uploader_id . "',
            `premod`      = " . $row['moder'] . ",
            `count_views` = " . $row['count'] . ",
            `time`        = '" . $row['time'] . "'
        ");
    }

    // Переносим комментарии
    $array = array();
    $sql = mysql_query("SELECT `id`,`refid`, `avtor`, `text`, `ip`, `soft`, `time` FROM `lib` WHERE `type`='komm'");

    while ($row = mysql_fetch_assoc($sql)) {
        $attributes = array(
            'author_name'         => $row['avtor'],
            'author_ip'           => $row['ip'],
            'author_ip_via_proxy' => '',
            'author_browser'      => $row['soft']
        );
        $array[$row['refid']][] = $row['id'];

        $req = mysql_query("SELECT `id` FROM `users` WHERE `name` = '" . $row['avtor'] . "' LIMIT 1");

        if(mysql_num_rows($req)){
            $res = mysql_fetch_assoc($req);
            mysql_query("
              INSERT INTO `cms_library_comments`
              SET
                `sub_id`     = " . $row['refid'] . ",
                `time`       = '" . $row['time'] . "',
                `user_id`    = " . $res['id'] . ",
                `text`       = '" . $row['text'] . "',
                `attributes` = '" . mysql_real_escape_string(serialize($attributes)) . "'") or die(mysql_error());

            foreach ($array as $aid => $cnt) {
                mysql_query("UPDATE `library_texts` SET `count_comments`=" . count($cnt) . ", `comments`=1 WHERE `id`=" . $aid);
            }
        }
    }

    echo '<div>Конвертация успешно произведена</div>';      // TODO: переводы в языковых пакетах
}

require_once('../incfiles/end.php');