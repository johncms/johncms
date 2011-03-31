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
$lng_forum = $core->load_lng('forum');
$textl = $lng_forum['search_forum'];
require('../incfiles/head.php');

/*
-----------------------------------------------------------------
Функция подсветки результатов запроса
-----------------------------------------------------------------
*/
function ReplaceKeywords($keywords, $value) {
    $a = stripos(mb_strtolower($value), mb_strtolower($keywords));
    if ($a === false)
        return $value;
    else {
        $zamen = 'qwertyzxcytrewq';
        $b = substr($value, $a, strlen($keywords));
        preg_match_all("/<.*>/Usi", $value, $out);
        $value = preg_replace('/<.*>/Usi', $zamen, $value);
        $value = str_replace($b, '<span style="background-color: #FFFF33">' . $b . '</span>', $value);
        $heck = 0;
        for ($i = 0; $i < count($out[0]); $i++) {
            $heck = strpos($value, $zamen, $heck);
            $value = substr($value, 0, $heck) . $out[0][$i] . substr($value, $heck + strlen($zamen));
        }
        return $value;
    }
}
echo '<p>' . functions::forum_new(1) . '</p>';
echo '<div class="phdr"><a href="index.php"><b>' . $lng['forum'] . '</b></a> | ' . $lng['search'] . '</div>';

/*
-----------------------------------------------------------------
Принимаем данные, выводим форму поиска
-----------------------------------------------------------------
*/
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$search = $search ? $search : rawurldecode(trim($_GET['search']));
$search = preg_replace("/[^\w\x7F-\xFF\s]/", " ", $search);
$search_t = isset($_REQUEST['t']) ? 1 : 0;
$search = preg_replace('/ {2,}/', ' ', $search);
$search = str_replace('qwertyzxcytrewq', '', $search);
echo '<div class="gmenu"><form action="search.php" method="post"><p>' .
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
if ($search && (mb_strlen($search) < 4 || mb_strlen($search) > 64))
    $error = $lng_forum['error_search_lenght'];

if ($search && !$error) {
    /*
    -----------------------------------------------------------------
    Выводим результаты запроса
    -----------------------------------------------------------------
    */
    $array = explode(' ', $search);
    $count = count($array);
    echo '<div class="bmenu">' . $lng['search_results'] . '</div>';
    $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum`
    WHERE MATCH (`text`) AGAINST ('" . mysql_real_escape_string($search) . "')
    AND `type` = '" . ($search_t ? 't' : 'm') . "'" . ($rights >= 7 ? "" : " AND `close` != '1'")), 0);
    if ($total) {
        $searchs = str_replace(' ', '|', $search);
        $req = mysql_query("SELECT * FROM `forum` WHERE MATCH (`text`) AGAINST ('" . mysql_real_escape_string($search) . "') AND `type` = '" . ($search_t ? 't' : 'm') . "' LIMIT $start, $kmess");
        while (($res = mysql_fetch_assoc($req)) !== false) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            if (!$search_t) {
                $req_t = mysql_query("SELECT `id`,`text` FROM `forum` WHERE `id` = '" . $res['refid'] . "'");
                $res_t = mysql_fetch_assoc($req_t);
                echo '<b>' . $res_t['text'] . '</b><br />';
            } else {
                $req_p = mysql_query("SELECT `text` FROM `forum` WHERE `refid` = '" . $res['id'] . "' ORDER BY `id` ASC LIMIT 1");
                $res_p = mysql_fetch_assoc($req_p);
                if ($count > 1) {
                    for ($s = 0; $s <= count($array); $s++) {
                        if (mb_strlen($array[$s]) >= 3) {
                            $res['text'] = ReplaceKeywords($array[$s], $res['text']);
                        }
                    }
                } else {
                    $res['text'] = ReplaceKeywords($search, $res['text']);
                }
                echo '<b>' . $res['text'] . '</b><br />';
            }
            echo '<a href="../users/profile.php?user=' . $res['user_id'] . '">' . $res['from'] . '</a> ';
            echo ' <span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span><br/>';
            $text = $search_t ? $res_p['text'] : $res['text'];
            $text = functions::checkout(mb_substr($text, 0, 400), 2, 1);
            $text = str_replace('qwertyzxcytrewq', '', $text);
            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
            if (!$search_t) {
                if ($count > 1) {
                    for ($s = 0; $s <= count($array); $s++) {
                        if (mb_strlen($array[$s]) >= 3) {
                            $text = ReplaceKeywords($array[$s], $text);
                        }
                    }
                } else {
                    $text = ReplaceKeywords($search, $text);
                }
            }
            echo $text;
            if (mb_strlen($res['text']) > 400)
                echo '...<a href="index.php?act=post&amp;id=' . $res['id'] . '">' . $lng_forum['read_all'] . ' &gt;&gt;</a>';
            echo '<br /><a href="index.php?id=' . ($search_t ? $res['id'] : $res_t['id']) . '">' . $lng_forum['to_topic'] . '</a>'
                . ($search_t ? '' : ' | <a href="index.php?act=post&amp;id=' . $res['id'] . '">' . $lng_forum['to_post'] . '</a>');
            echo '</div>';
            ++$i;
        }
    } else {
        echo '<div class="rmenu"><p>' . $lng['search_results_empty'] . '</p></div>';
    }
    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
    if ($total > $kmess) {
        // Навигация по страницам
        echo '<p>' . functions::display_pagination('search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . rawurlencode($search) . '&amp;', $start, $total, $kmess) . '</p>' .
            '<p><form action="search.php?' . ($search_t ? 't=1&amp;' : '') . 'search=' . rawurlencode($search) . '" method="post">' .
            '<input type="text" name="page" size="2"/>' .
            '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
            '</form></p>';
    }
} else {
    /*
    -----------------------------------------------------------------
    Выводим сообщение об ошибке
    -----------------------------------------------------------------
    */
    if ($error)
        echo functions::display_error($error);

    /*
    -----------------------------------------------------------------
    Инструкции для поиска
    -----------------------------------------------------------------
    */
    echo '<div class="phdr"><small>' . $lng_forum['search_help'] . '</small></div>';
}
echo '<p>' . ($search ? '<a href="search.php">' . $lng['search_new'] . '</a><br />' : '') . '<a href="index.php">' . $lng['to_forum'] . '</a></p>';
require('../incfiles/end.php');
?>