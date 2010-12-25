<?

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
// Спасибо Esi0n за помощь в написании модуля RSS
*/

define('_IN_JOHNCMS', 1);

require_once ('../incfiles/core.php');
header('content-type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel>
<title>' . $set['copyright'] . ' | Новости ресурса</title>
<link>' . $set['homeurl'] .
'</link>
<description>Новости сайта</description>
<language>ru-RU</language>
<webMaster>' . $set['email'] . '</webMaster> ';

// Новости
$req = mysql_query('SELECT * FROM `news` ORDER BY `time` DESC LIMIT 15;');
if (mysql_num_rows($req) > 0) {
    while ($res = mysql_fetch_assoc($req)) {
        echo '
	<item>
<title>Новости: ' . $res['name'] . '</title>
<link>' . $set['homeurl'] . '/news/index.php</link>
<author>' . $res['avt'] . '</author>
<description>' . $res['text'] . '</description>
<pubDate>' . date('r', $res['time']) .
        '</pubDate>
	</item>';
    }
}

// Библиотека
$req = mysql_query("select * from `lib` where `type`='bk' and `moder`='1' order by `time` desc LIMIT 15;");
if (mysql_num_rows($req) > 0) {
    while ($res = mysql_fetch_array($req)) {
        echo '
	<item>
<title>Библиотека: ' . $res['name'] . '</title>
<link>' . $set['homeurl'] . '/library/index.php?id=' . $res['id'] . '</link>
<author>' . $res['avtor'] . '</author>
<description>' . $res['announce'] .
        '</description>
<pubDate>' . date('r', $res['time']) . '</pubDate>
	</item>';
    }
}

echo '</channel></rss>';

?>
