<?php

/**
* @package     JohnCMS
* @link        http://johncms.com
* @copyright   Copyright (C) 2008-2011 JohnCMS Community
* @license     LICENSE.txt (see attached file)
* @version     VERSION.txt (see attached file)
* @author      http://johncms.com/about
*/

define('_IN_JOHNCMS', 1);

$headmod = 'forumsearch';
require('../incfiles/core.php');
$lng_forum = core::load_lng('forum');
$textl = $lng_forum['search_forum'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Функция подсветки результатов запроса
-----------------------------------------------------------------
*/
function ReplaceKeywords($search, $text) {
    $search = str_replace('*', '', $search);
    return mb_strlen($search) < 3 ? $text : preg_replace('|('.preg_quote($search, '/').')|siu','<span style="background-color: #FFFF33">$1</span>',$text);
}

/*
-----------------------------------------------------------------
Принимаем данные, выводим форму поиска
-----------------------------------------------------------------
*/
$search_post = isset($_POST['search']) ? trim($_POST['search']) : false;
$search_get = isset($_GET['search']) ? rawurldecode(trim($_GET['search'])) : false;
$search = $search_post ? $search_post : $search_get;
//$search = preg_replace("/[^\w\x7F-\xFF\s]/", " ", $search);
$search_t = isset($_REQUEST['t']);
echo '<div class="phdr"><a href="index.php"><b>' . $lng['forum'] . '</b></a> | ' . $lng['search'] . '</div>' .
     '<div class="gmenu"><form action="search.php" method="post"><p>' .
     '<input type="text" value="' . ($search ? functions::checkout($search) : '') . '" name="search" />' .
     '<input type="submit" value="' . $lng['search'] . '" name="submit" /><br />' .
     '<input name="t" type="checkbox" value="1" ' . ($search_t ? 'checked="checked"' : '') . ' />&nbsp;' . $lng_forum['search_topic_name'] .
     '</p></form></div>';

/*
-----------------------------------------------------------------
Проверям на ошибки
-----------------------------------------------------------------
*/
$error = false;
if ($search && (mb_strlen($search) < 2 || mb_strlen($search) > 64))
    $error = $lng['error_search_length'];

if ($search && !$error) {
    /*
    -----------------------------------------------------------------
    Выводим результаты запроса
    -----------------------------------------------------------------
    */
    $array = explode(' ', $search);
    $count = count($array);
    $query = mysql_real_escape_string($search);
    $total = mysql_result(mysql_query("
        SELECT COUNT(*) FROM `forum`
        WHERE MATCH (`text`) AGAINST ('$query' IN BOOLEAN MODE)
        AND `type` = '" . ($search_t ? 't' : 'm') . "'" . ($rights >= 7 ? "" : " AND `close` != '1'
    ")), 0);
    echo '<div class="phdr">' . $lng['search_results'] . '</div>';
    if ($total > $kmess)
        echo '<div class="topmenu">' . functions::display_pagination('search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>';
    if ($total) {
        $req = mysql_query("
            SELECT *, MATCH (`text`) AGAINST ('$query' IN BOOLEAN MODE) as `rel`
            FROM `forum`
            WHERE MATCH (`text`) AGAINST ('$query' IN BOOLEAN MODE)
            AND `type` = '" . ($search_t ? 't' : 'm') . "'
            ORDER BY `rel` DESC
            LIMIT $start, $kmess
        ");
        $i = 0;
        while (($res = mysql_fetch_assoc($req)) !== false) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            if (!$search_t) {
                // Поиск только в тексте
                $req_t = mysql_query("SELECT `id`,`text` FROM `forum` WHERE `id` = '" . $res['refid'] . "'");
                $res_t = mysql_fetch_assoc($req_t);
                echo '<b>' . $res_t['text'] . '</b><br />';
            } else {
                // Поиск в названиях тем
                $req_p = mysql_query("SELECT `text` FROM `forum` WHERE `refid` = '" . $res['id'] . "' ORDER BY `id` ASC LIMIT 1");
                $res_p = mysql_fetch_assoc($req_p);
                foreach($array as $val){
                    $res['text'] = ReplaceKeywords($val, $res['text']);
                }
                echo '<b>' . $res['text'] . '</b><br />';
            }
            echo '<a href="../users/profile.php?user=' . $res['user_id'] . '">' . $res['from'] . '</a> ';
            echo ' <span class="gray">(' . functions::display_date($res['time']) . ')</span><br/>';
            $text = $search_t ? $res_p['text'] : $res['text'];
            foreach ($array as $srch) if (($pos = mb_strpos(strtolower($res['text']), strtolower(str_replace('*', '', $srch)))) !== false) break;
            if(!isset($pos) || $pos < 100) $pos = 100;
            $text = functions::checkout(mb_substr($text, ($pos - 100), 400), 1);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
            if (!$search_t) {
                foreach($array as $val){
                    $text = ReplaceKeywords($val, $text);
                }
            }
            echo $text;
            if (mb_strlen($res['text']) > 500)
                echo '...<a href="index.php?act=post&amp;id=' . $res['id'] . '">' . $lng_forum['read_all'] . ' &gt;&gt;</a>';
            echo '<br /><a href="index.php?id=' . ($search_t ? $res['id'] : $res_t['id']) . '">' . $lng_forum['to_topic'] . '</a>' . ($search_t ? '' : ' | <a href="index.php?act=post&amp;id=' . $res['id'] . '">' . $lng_forum['to_post'] . '</a>');
            echo '</div>';
            ++$i;
        }
    } else {
        echo '<div class="rmenu"><p>' . $lng['search_results_empty'] . '</p></div>';
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        echo '<div class="topmenu">' . functions::display_pagination('search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '&amp;', $start, $total, $kmess) . '</div>' .
            '<p><form action="search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . urlencode($search) . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
            '</form></p>';
    }
} else {
    if ($error) echo functions::display_error($error);
    echo '<div class="phdr"><small>' . $lng['search_help'] . '</small></div>';
}
echo '<p>' . ($search ? '<a href="search.php">' . $lng['search_new'] . '</a><br />' : '') . '<a href="index.php">' . $lng['forum'] . '</a></p>';

require('../incfiles/end.php');

?>