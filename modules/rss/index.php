<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

/**
 * @var PDO $db
 */

$config = di('config')['johncms'];
$db = di(PDO::class);

header('content-type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>' .
    '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel>' .
    '<title>' . htmlspecialchars($config['copyright']) . ' | News</title>' .
    '<link>' . $config['homeurl'] . '</link>' .
    '<description>News</description>' .
    '<language>ru-RU</language>';

// Новости
$req = $db->query('SELECT * FROM `news` ORDER BY `time` DESC LIMIT 15;');

if ($req->rowCount()) {
    while ($res = $req->fetch()) {
        echo '<item>' .
            '<title>News: ' . $res['name'] . '</title>' .
            '<link>' . $config['homeurl'] . '/news/</link>' .
            '<author>' . htmlspecialchars($res['avt']) . '</author>' .
            '<description>' . htmlspecialchars($res['text']) . '</description>' .
            '<pubDate>' . date('r', (int) $res['time']) .
            '</pubDate>' .
            '</item>';
    }
}

// Библиотека
$req = $db->query('select * from `library_texts` where `premod`=1 limit 15;');

if ($req->rowCount()) {
    while ($res = $req->fetch()) {
        echo '<item>' .
            '<title>Library: ' . htmlspecialchars($res['name']) . '</title>' .
            '<link>' . $config['homeurl'] . '/library/?id=' . $res['id'] . '</link>' .
            '<author>' . htmlspecialchars($res['uploader']) . '</author>' .
            '<description>' . htmlspecialchars($res['announce']) .
            '</description>' .
            '<pubDate>' . date('r', (int) $res['time']) . '</pubDate>' .
            '</item>';
    }
}

echo '</channel></rss>';
