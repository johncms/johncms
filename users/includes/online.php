<?php

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
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');
$headmod = 'online';
$textl = $lng['online'];
$lng_online = $core->load_lng('online');
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Показываем список Online
-----------------------------------------------------------------
*/
echo '<div class="phdr"><b>' . $lng_online['who_on_site'] . '</b></div>';
if ($rights > 0)
    echo '<div class="topmenu">' . ($mod == 'guest' ? '<a href="index.php?act=online">' . $lng['authorized'] . '</a> | <b>' . $lng['guests'] . '</b>' : '<b>' . $lng['authorized'] . '</b> | <a href="index.php?act=online&amp;mod=guest">' . $lng['guests'] . '</a>')
        . '</div>';
$onltime = $realtime - 300;
$total = mysql_result(mysql_query("SELECT COUNT(*) FROM `" . ($mod == 'guest' ? 'cms_guests' : 'users') . "` WHERE `lastdate` > '$onltime'"), 0);
if ($total) {
    $req = mysql_query("SELECT * FROM `" . ($mod == 'guest' ? 'cms_guests' : 'users') . "` WHERE `lastdate` > '$onltime' ORDER BY " . ($mod == 'guest' ? "`movings` DESC" : "`name` ASC") . " LIMIT $start,$kmess");
    while ($res = mysql_fetch_assoc($req)) {
        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
        $where = explode(",", $res['place']);
        // Список возможных местоположений
        $places = array (
            'admlist'       => '<a href="index.php?act=admlist">' . $lng_online['where_adm_list'] . '</a>',
            'album'         => '<a href="album.php">' . $lng_online['where_album'] . '</a>',
            'birth'         => '<a href="index.php?act=birth">' . $lng_online['where_birth'] . '</a>',
            'downloads'     => '<a href="../download/index.php">' . $lng_online['where_downloads'] . '</a>',
            'faq'           => '<a href="../pages/faq.php">' . $lng_online['where_faq'] . '</a>',
            'forum'         => '<a href="../forum/index.php">' . $lng_online['where_forum'] . '</a>&#160;/&#160;<a href="../forum/index.php?act=who">&gt;&gt;</a>',
            'forumfiles'    => '<a href="../forum/index.php?act=files">' . $lng_online['where_forum_files'] . '</a>',
            'forumwho'      => '<a href="../forum/index.php?act=who">' . $lng_online['where_forum_who'] . '</a>',
            'gallery'       => '<a href="../gallery/index.php">' . $lng_online['where_gallery'] . '</a>',
            'guest'         => '<a href="../guestbook/index.php">' . $lng_online['where_guestbook'] . '</a>',
            'library'       => '<a href="../library/index.php">' . $lng_online['where_library'] . '</a>',
            'news'          => '<a href="../news/index.php">' . $lng_online['where_news'] . '</a>',
            'online'        => $lng_online['where_here'],
            'pm'            => $lng_online['where_pm'],
            'profile'       => '<a href="profile.php">' . $lng_online['where_profile'] . '</a>',
            'userlist'      => '<a href="index.php?act=userlist">' . $lng_online['where_users_list'] . '</a>',
            'users'         => '<a href="index.php">' . $lng['community'] . '</a>',
            'userstop'      => '<a href="index.php?act=top">' . $lng_online['where_users_top'] . '</a>'
        );
        // Вычисляем местоположение
        $place = array_key_exists($where[0], $places) ? $places[$where[0]] : '<a href="../index.php">' . $lng_online['where_homepage'] . '</a>';
        $arg = array (
            'stshide' => 1,
            'header' => (' (' . $res['movings'] . ' - ' . functions::timecount($realtime - $res['sestime']) . ')<br /><img src="../images/info.png" width="16" height="16" align="middle" />&#160;' . $place)
        );
        echo functions::display_user($res, $arg);
        echo '</div>';
        ++$i;
    }
} else {
    echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<p>' . functions::display_pagination('index.php?act=online&amp;' . ($mod == 'guest' ? 'mod=guest&amp;' : ''), $start, $total, $kmess) . '</p>';
    echo '<p><form action="index.php?act=online' . ($mod == 'guest' ? '&amp;mod=guest' : '') . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}
?>