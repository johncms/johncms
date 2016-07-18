<?

define('_IN_JOHNCMS', 1);

require_once ('../incfiles/core.php');

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

header('content-type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>' .
     '<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel>' .
     '<title>' . htmlspecialchars($set['copyright']) . ' | News</title>' .
     '<link>' . $set['homeurl'] . '</link>' .
     '<description>News</description>' .
     '<language>ru-RU</language>';

// Новости
$req = $db->query('SELECT * FROM `news` ORDER BY `time` DESC LIMIT 15;');

if ($req->rowCount()) {
    while ($res = $req->fetch()) {
        echo '<item>' .
             '<title>News: ' . $res['name'] . '</title>' .
             '<link>' . $set['homeurl'] . '/news/index.php</link>' .
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
             '<link>' . $set['homeurl'] . '/library/index.php?id=' . $res['id'] . '</link>' .
             '<author>' . htmlspecialchars($res['uploader']) . '</author>' .
             '<description>' . htmlspecialchars($res['announce']) .
             '</description>' .
             '<pubDate>' . date('r', $res['time']) . '</pubDate>' .
             '</item>';
    }
}

echo '</channel></rss>';
