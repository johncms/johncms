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

/*
-----------------------------------------------------------------
История активности
-----------------------------------------------------------------
*/
$textl = htmlspecialchars($user['name']) . ': ' . $lng_profile['activity'];
require('../incfiles/head.php');
echo '<div class="phdr"><a href="profile.php?user=' . $user['id'] . '"><b>' . $lng['profile'] . '</b></a> | ' . $lng_profile['activity'] . '</div>';
$menu = array (
    (!$mod ? '<b>' . $lng['messages'] . '</b>' : '<a href="profile.php?act=activity&amp;user=' . $user['id'] . '">' . $lng['messages'] . '</a>'),
    ($mod == 'topic' ? '<b>' . $lng['themes'] . '</b>' : '<a href="profile.php?act=activity&amp;mod=topic&amp;user=' . $user['id'] . '">' . $lng['themes'] . '</a>'),
    ($mod == 'guest' ? '<b>' . $lng['guestbook'] . '</b>' : '<a href="profile.php?act=activity&amp;mod=guest&amp;user=' . $user['id'] . '">' . $lng['guestbook'] . '</a>'),
);
echo '<div class="topmenu">' . functions::display_menu($menu) . '</div>';
echo '<div class="user"><p>' . functions::display_user($user, array ('iphide' => 1,)) . '</p></div>';
switch ($mod) {
    case 'guest':
        /*
        -----------------------------------------------------------------
        Список сообщений в Гостевой
        -----------------------------------------------------------------
        */
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `guest` WHERE `user_id` = '" . $user['id'] . "'" . ($rights >= 1 ? '' : " AND `adm` = '0'")), 0);
        $req = mysql_query("SELECT * FROM `guest` WHERE `user_id` = '" . $user['id'] . "'" . ($rights >= 1 ? '' : " AND `adm` = '0'") . " ORDER BY `id` DESC LIMIT $start, $kmess");
        if (mysql_num_rows($req)) {
            while ($res = mysql_fetch_assoc($req)) {
                echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') . functions::checkout($res['text'], 2, 1) . '<div class="sub">' .
                    '<span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>' .
                    '</div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . $lng_profile['guest_empty'] . '</p></div>';
        }
        break;

    case 'topic':
        /*
        -----------------------------------------------------------------
        Список тем Форума
        -----------------------------------------------------------------
        */
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't'" . ($rights >= 7 ? '' : " AND `close`!='1'")), 0);
        $req = mysql_query("SELECT * FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 't'" . ($rights >= 7 ? '' : " AND `close`!='1'") . " ORDER BY `id` DESC LIMIT $start, $kmess");
        if (mysql_num_rows($req)) {
            while ($res = mysql_fetch_assoc($req)) {
                $post = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `refid` = '" . $res['id'] . "'" . ($rights >= 7 ? '' : " AND `close`!='1'") . " ORDER BY `id` ASC LIMIT 1"));
                $section = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'"));
                $category = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $section['refid'] . "'"));
                $text = mb_substr($post['text'], 0, 300);
                $text = functions::checkout($text, 2, 1);
                echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
                    '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $res['id'] . '">' . $res['text'] . '</a>' .
                    '<br />' . $text . '...<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $res['id'] . '">' . $lng_forum['read_all'] . ' &gt;&gt;</a>' .
                    '<div class="sub">' .
                    '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $category['id'] . '">' . $category['text'] . '</a> | ' .
                    '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $section['id'] . '">' . $section['text'] . '</a>' .
                    '<br /><span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>' .
                    '</div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Список постов Форума
        -----------------------------------------------------------------
        */
        $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm'" . ($rights >= 7 ? '' : " AND `close`!='1'")), 0);
        $req = mysql_query("SELECT * FROM `forum` WHERE `user_id` = '" . $user['id'] . "' AND `type` = 'm' " . ($rights >= 7 ? '' : " AND `close`!='1'") . " ORDER BY `id` DESC LIMIT $start, $kmess");
        if (mysql_num_rows($req)) {
            while ($res = mysql_fetch_assoc($req)) {
                $topic = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'"));
                $section = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $topic['refid'] . "'"));
                $category = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $section['refid'] . "'"));
                $text = mb_substr($res['text'], 0, 300);
                $text = functions::checkout($text, 2, 1);
                $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
                    '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $topic['id'] . '">' . $topic['text'] . '</a>' .
                    '<br />' . $text . '...<a href="' . $set['homeurl'] . '/forum/index.php?act=post&amp;id=' . $res['id'] . '">' . $lng_forum['read_all'] . ' &gt;&gt;</a>' .
                    '<div class="sub">' .
                    '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $category['id'] . '">' . $category['text'] . '</a> | ' .
                    '<a href="' . $set['homeurl'] . '/forum/index.php?id=' . $section['id'] . '">' . $section['text'] . '</a>' .
                    '<br /><span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>' .
                    '</div></div>';
                ++$i;
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
}
echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
if ($total > $kmess) {
    echo '<p>' . functions::display_pagination('profile.php?act=activity' . ($mod ? '&amp;mod=' . $mod : '') . '&amp;user=' . $user['id'] . '&amp;', $start, $total, $kmess) . '</p>' .
        '<p><form action="profile.php?act=activity&amp;user=' . $user['id'] . '" method="post">' .
        '<input type="text" name="page" size="2"/>' .
        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
        '</form></p>';
}
?>