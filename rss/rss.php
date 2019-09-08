<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

define('_IN_JOHNCMS', 1);

require_once ('../system/bootstrap.php');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

/** @var PDO $db */
$db = $container->get(PDO::class);

header('content-type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>' .
     '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel>' .
     '<title>' . htmlspecialchars($config->copyright) . ' | News</title>' .
     '<link>' . $config->homeurl . '</link>' .
     '<description>News</description>' .
     '<language>ru-RU</language>';

// Новости
$req = $db->query('SELECT * FROM `news` ORDER BY `time` DESC LIMIT 15;');

if ($req->rowCount()) {
    while ($res = $req->fetch()) {
        echo '<item>' .
             '<title>News: ' . $res['name'] . '</title>' .
             '<link>' . $config->homeurl . '/news/index.php</link>' .
             '<author>' . htmlspecialchars($res['avt']) . '</author>' .
             '<description>' . htmlspecialchars($res['text']) . '</description>' .
             '<pubDate>' . date('r', $res['time']) .
             '</pubDate>' .
             '</item>';
    }
}

// Библиотека
$req = $db->query("select * from `library_texts` where `premod`=1 limit 15;");

if ($req->rowCount()) {
    while ($res = $req->fetch()) {
        echo '<item>' .
             '<title>Library: ' . htmlspecialchars($res['name']) . '</title>' .
             '<link>' . $config->homeurl . '/library/index.php?id=' . $res['id'] . '</link>' .
             '<author>' . htmlspecialchars($res['uploader']) . '</author>' .
             '<description>' . htmlspecialchars($res['announce']) .
             '</description>' .
             '<pubDate>' . date('r', $res['time']) . '</pubDate>' .
             '</item>';
    }
}

echo '</channel></rss>';
