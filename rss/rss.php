<?
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                             Content Management System              //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////

Большое спасибо Esi0n (esion@esion.name http://esion.name icq 808360)
за помощь в написании модуля RSS новостей.
*/

define('_IN_JOHNCMS', 1);

require_once ("../incfiles/core.php");
header('content-type: application/rss+xml');
echo '<?xml version="1.0" encoding="utf-8"?>
<rss version="2.0" xmlns:dc="http://purl.org/dc/elements/1.1/"><channel>
<title>' . $copyright . ' | Новости ресурса</title>
<link>' . $home . '</link>
<description>Новости сайта</description>
<language>ru-RU</language>
<webMaster>' . $emailadmina . '</webMaster> ';

// Новости
$req = mysql_query('SELECT * FROM `news` ORDER BY `time` DESC LIMIT 15;');
if (mysql_num_rows($req) > 0)
{
    while ($res = mysql_fetch_assoc($req))
    {
        echo '
	<item>
<title>Новости: ' . $res['name'] . '</title>
<link>' . $home . '/str/news.php</link>
<author>' . $res['avt'] . '</author>
<description>' . $res['text'] . '</description>
<pubDate>' . date('r', $res['time']) . '</pubDate>
	</item>';
    }
}

// Библиотека
$req = mysql_query("select * from `lib` where `type`='bk' and `moder`='1' order by `time` desc LIMIT 15;");
if (mysql_num_rows($req) > 0)
{
    while ($res = mysql_fetch_array($req))
    {
        echo '
	<item>
<title>Библиотека: ' . $res['name'] . '</title>
<link>' . $home . '/library/index.php?id=' . $res['id'] . '</link>
<author>' . $res['avtor'] . '</author>
<description>' . $res['announce'] . '</description>
<pubDate>' . date('r', $res['time']) . '</pubDate>
	</item>';
    }
}

echo '</channel></rss>';

?>