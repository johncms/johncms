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

$headmod = 'library';
require_once('../incfiles/core.php');
$lng_lib = core::load_lng('library');
$textl = $lng['library'];

// Ограничиваем доступ к Библиотеке
$error = '';
if (!$set['mod_lib'] && $rights < 7)
    $error = $lng_lib['library_closed'];
elseif ($set['mod_lib'] == 1 && !$user_id)
    $error = $lng['access_guest_forbidden'];
if ($error) {
    require_once('../incfiles/head.php');
    echo '<div class="rmenu"><p>' . $error . '</p></div>';
    require_once('../incfiles/end.php');
    exit;
}

// Заголовки библиотеки
if ($id) {
    $req = mysql_query("SELECT * FROM `lib` WHERE `id`= '$id'");
    $zag = mysql_fetch_array($req);
    $hdr = $zag['type'] == 'bk' ? $zag['name'] : $zag['text'];
    $hdr = htmlentities(mb_substr($hdr, 0, 30), ENT_QUOTES, 'UTF-8');
    $textl = mb_strlen($zag['text']) > 30 ? $hdr . '...' : $hdr;
}
require_once('../incfiles/head.php');

$mods = array(
    'java',
    'new',
    'moder',
    'addkomm',
    'komm',
    'del',
    'edit',
    'load',
    'write',
    'mkcat',
    'topread'
);
if ($act && ($key = array_search($act, $mods)) !== false && file_exists('includes/' . $mods[$key] . '.php')) {
    require('includes/' . $mods[$key] . '.php');
} else {
    if (!$set['mod_lib'])
        echo '<p><font color="#FF0000"><b>' . $lng_lib['library_closed'] . '</b></font></p>';
    if (!$id) {
        echo '<div class="phdr"><b>' . $lng['library'] . '</b></div>';
        echo '<div class="topmenu"><a href="search.php">' . $lng['search'] . '</a></div>';
        if ($rights == 5 || $rights >= 6) {
            // Считаем число статей, ожидающих модерацию
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0'");
            $res = mysql_result($req, 0);
            if ($res > 0)
                echo '<div class="rmenu">' . $lng['on_moderation'] . ': <a href="index.php?act=moder">' . $res . '</a></div>';
        }
        // Считаем новое в библиотеке
        $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . (time() - 259200) . "' AND `type`='bk' AND `moder`='1'");
        $res = mysql_result($req, 0);
        echo '<div class="gmenu"><p>';
        if ($res > 0)
            echo '<a href="index.php?act=new">' . $lng_lib['new_articles'] . '</a> (' . $res . ')<br/>';
        echo '<a href="index.php?act=topread">' . $lng_lib['most_readed'] . '</a></p></div>';
        $id = 0;
        $tip = "cat";
    } else {
        $tip = $zag['type'];
        if ($tip == "cat") {
            echo '<div class="phdr"><a href="index.php"><b>' . $lng['library'] . '</b></a> | ' . htmlentities($zag['text'], ENT_QUOTES, 'UTF-8') . '</div>';
        }
    }
    switch ($tip) {
        case 'cat':
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'cat' AND `refid` = '$id'");
            $totalcat = mysql_result($req, 0);
            $bkz = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `refid` = '$id' AND `moder`='1'");
            $totalbk = mysql_result($bkz, 0);
            if ($totalcat > 0) {
                $total = $totalcat;
                if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
                $req = mysql_query("SELECT `id`, `text`  FROM `lib` WHERE `type` = 'cat' AND `refid` = '$id' LIMIT " . $start . "," . $kmess);
                $i = 0;
                while ($cat1 = mysql_fetch_array($req)) {
                    $cat2 = mysql_query("select `id` from `lib` where type = 'cat' and refid = '" . $cat1['id'] . "'");
                    $totalcat2 = mysql_num_rows($cat2);
                    $bk2 = mysql_query("select `id` from `lib` where type = 'bk' and refid = '" . $cat1['id'] . "' and moder='1'");
                    $totalbk2 = mysql_num_rows($bk2);
                    if ($totalcat2 != 0) {
                        $kol = "$totalcat2 кат.";
                    } elseif ($totalbk2 != 0) {
                        $kol = "$totalbk2 ст.";
                    } else {
                        $kol = "0";
                    }
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo '<a href="index.php?id=' . $cat1['id'] . '">' . $cat1['text'] . '</a>(' . $kol . ')</div>';
                    ++$i;
                }
                echo '<div class="phdr">' . $lng['total'] . ': ' . $totalcat . '</div>';
            } elseif ($totalbk > 0) {
                $total = $totalbk;
                if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
                $bk = mysql_query("select * from `lib` where type = 'bk' and refid = '" . $id . "' and moder='1' order by `time` desc LIMIT " . $start . "," . $kmess);
                $i = 0;
                while ($bk1 = mysql_fetch_array($bk)) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo '<b><a href="index.php?id=' . $bk1['id'] . '">' . htmlentities($bk1['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
                    echo htmlentities($bk1['announce'], ENT_QUOTES, 'UTF-8');
                    echo '<div class="sub"><span class="gray">' . $lng_lib['added'] . ':</span> ' . $bk1['avtor'] . ' (' . functions::display_date($bk1['time']) . ')<br />';
                    echo '<span class="gray">' . $lng_lib['reads'] . ':</span> ' . $bk1['count'] . '</div></div>';
                    ++$i;
                }
                echo '<div class="phdr">' . $lng['total'] . ': ' . $totalbk . '</div>';
            } else {
                $total = 0;
            }
            // Навигация по страницам
            if ($total > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?id=' . $id . '&amp;', $start, $total, $kmess) . '</div>' .
                     '<p><form action="index.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="text" name="page" size="2"/><input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
            }
            echo '<p>';
            if (($rights == 5 || $rights >= 6) && $id != 0) {
                $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "'");
                $ct1 = mysql_num_rows($ct);
                if ($ct1 == 0) {
                    echo '<a href="index.php?act=del&amp;id=' . $id . '">' . $lng_lib['delete_category'] . '</a><br/>';
                }
                echo '<a href="index.php?act=edit&amp;id=' . $id . '">' . $lng_lib['edit_category'] . '</a><br/>';
            }
            if (($rights == 5 || $rights >= 6) && (isset($zag['ip']) && $zag['ip'] == 1 || $id == 0)) {
                echo '<a href="index.php?act=mkcat&amp;id=' . $id . '">' . $lng_lib['create_category'] . '</a><br/>';
            }
            if (isset($zag['ip']) && $zag['ip'] == 0 && $id != 0) {
                if (($rights == 5 || $rights >= 6) || ($zag['soft'] == 1 && !empty($_SESSION['uid']))) {
                    echo "<a href='index.php?act=write&amp;id=" . $id . "'>" . $lng_lib['write_article'] . "</a><br/>";
                }
                if ($rights == 5 || $rights >= 6) {
                    echo "<a href='index.php?act=load&amp;id=" . $id . "'>" . $lng_lib['upload_article'] . "</a><br/>";
                }
            }
            if ($id) {
                $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $id . "'");
                $dnam1 = mysql_fetch_array($dnam);
                $dnam2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnam1['refid'] . "'");
                $dnam3 = mysql_fetch_array($dnam2);
                $catname = "$dnam3[text]";
                $dirid = "$dnam1[id]";

                $nadir = $dnam1['refid'];
                while ($nadir != "0") {
                    echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                    $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "'");
                    $dnamm1 = mysql_fetch_array($dnamm);
                    $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1['refid'] . "'");
                    $dnamm3 = mysql_fetch_array($dnamm2);
                    $nadir = $dnamm1['refid'];
                    $catname = $dnamm3['text'];
                }
                echo "<a href='index.php?'>" . $lng_lib['to_library'] . "</a><br/>";
            }
            echo '</p>';
            break;

        case 'bk':
            /*
            -----------------------------------------------------------------
            Читаем статью
            -----------------------------------------------------------------
            */
            if (!empty($_SESSION['symb'])) {
                $simvol = $_SESSION['symb'];
            } else {
                $simvol = 2000; // Число символов на страницу по умолчанию
            }
            // Счетчик прочтений
            if (!isset($_SESSION['lib']) || isset($_SESSION['lib']) && $_SESSION['lib'] != $id) {
                $_SESSION['lib'] = $id;
                $libcount = intval($zag['count']) + 1;
                mysql_query("UPDATE `lib` SET  `count` = '$libcount' WHERE `id` = '$id'");
            }
            // Запрашиваем выбранную статью из базы
            $symbols = core::$is_mobile ? 3000 : 7000;
            $req = mysql_fetch_assoc(mysql_query("SELECT CHAR_LENGTH(`text`) / $symbols AS `count_pages` FROM `lib` WHERE `id`= '$id'"));
            $count_pages = ceil($req['count_pages']);
            $start_pos = $page == 1 ? 1 : $page * $symbols - $symbols;
            $req = mysql_fetch_assoc(mysql_query("SELECT SUBSTRING(`text`, $start_pos, " . ($symbols + 100) . ") AS `text` FROM `lib` WHERE `id` = '$id'"));
            if ($page == 1) {
                $int_start = 0;
            } else {
                if (false === ($pos1 = mb_strpos($req['text'], "\r\n"))) $pos1 = 100;
                if (false === ($pos2 = mb_strpos($req['text'], ' '))) $pos2 = 100;
                $int_start = $pos1 >= $pos2 ? $pos2 : $pos1;
                $start = $page - 1;
            }
            if ($count_pages == 1 || $page == $count_pages) {
                $int_lenght = $symbols;
            } else {
                $tmp = mb_substr($req['text'], $symbols, 100);
                if (($pos1 = mb_strpos($tmp, "\r\n")) === false) $pos1 = 100;
                if (($pos2 = mb_strpos($tmp, ' ')) === false) $pos2 = 100;
                $int_lenght = $symbols + ($pos1 >= $pos2 ? $pos2 : $pos1) - $int_start;
            }

            // Заголовок статьи
            echo '<div class="phdr"><b>' . htmlentities($zag['name'], ENT_QUOTES, 'UTF-8') . '</b></div>';
            if ($count_pages > 1) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?id=' . $id . '&amp;', $start, $count_pages, 1) . '</div>';
            }
            // Текст статьи
            $text = functions::checkout(mb_substr($req['text'], $int_start, $int_lenght), 1, 1);
            if ($set_user['smileys'])
                $text = functions::smileys($text, $rights ? 1 : 0);
            echo '<div class="list2">' . $text . '</div>';

            // Ссылка на комментарии
            if ($set['mod_lib_comm'] || $rights >= 7) {
                $km = mysql_query("select `id` from `lib` where type = 'komm' and refid = '" . $id . "'");
                $km1 = mysql_num_rows($km);
                $comm_link = "<a href='index.php?act=komm&amp;id=" . $id . "'>" . $lng['comments'] . "</a> ($km1)";
            } else {
                $comm_link = '&#160;';
            }
            echo '<div class="phdr">' . $comm_link . '</div>';
            if ($count_pages > 1) {
                echo '<div class="topmenu">' .
                     functions::display_pagination('index.php?id=' . $id . '&amp;', $start, $count_pages, 1) .
                     '</div><div class="topmenu">' .
                     '<form action="index.php?id=' . $id . '" method="post">' .
                     '<input type="text" name="page" size="2"/>' .
                     '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
                     '</form></div>';
            }
            if ($rights == 5 || $rights >= 6) {
                echo '<p><a href="index.php?act=edit&amp;id=' . $id . '">' . $lng['edit'] . '</a><br/>';
                echo '<a href="index.php?act=del&amp;id=' . $id . '">' . $lng['delete'] . '</a></p>';
            }
            echo '<a href="index.php?act=java&amp;id=' . $id . '">' . $lng_lib['download_java'] . '</a><br /><br />';
            $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $zag['refid'] . "'");
            $dnam1 = mysql_fetch_array($dnam);
            $catname = "$dnam1[text]";
            $dirid = "$dnam1[id]";
            $nadir = $zag['refid'];
            while ($nadir != "0") {
                echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "'");
                $dnamm1 = mysql_fetch_array($dnamm);
                $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1['refid'] . "'");
                $dnamm3 = mysql_fetch_array($dnamm2);
                $nadir = $dnamm1['refid'];
                $catname = $dnamm3['text'];
            }
            echo "<a href='index.php?'>" . $lng_lib['to_library'] . "</a>";
            break;
        default :
            header("location: index.php");
            break;
    }
}

require_once('../incfiles/end.php');

?>
