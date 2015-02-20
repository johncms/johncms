<?php
/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2015 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);
$headmod = 'library';
require_once('../incfiles/core.php');
require_once('inc.php');

$lng_lib = core::load_lng('library');
$textl = $lng['library'];

// Ограничиваем доступ к Библиотеке

$error = '';

if (!$set['mod_lib'] && $rights < 7) {
    $error = $lng_lib['library_closed'];
} elseif ($set['mod_lib'] == 1 && !$user_id) {
    $error = $lng['access_guest_forbidden'];
}

if ($error) {
    require_once('../incfiles/head.php');
    echo functions::display_error($error);
    require_once('../incfiles/end.php');
    exit;
}

// костыль перенаправления старых ссылок библиотеки, для поисковиков

if ($id && !$do && !$act) {
    mysql_result(mysql_query("SELECT COUNT(*) FROM `library_cats` WHERE `id`=" . $id), 0)
        ? header('Location: ' . core::$system_set['homeurl'] . '/library/?do=dir&id=' . $id)
        : (mysql_result(mysql_query("SELECT COUNT(*) FROM `library_texts` WHERE `id`=" . $id), 0)
        ? header('Location: ' . core::$system_set['homeurl'] . '/library/?do=text&id=' . $id)
        : redir404());
    exit;
}

// Заголовки библиотеки

if ($do) {
    switch ($do) {
        default:
            $tab = 'library_cats';
            break;

        case 'text':
            $tab = 'library_texts';
            break;
    }

    $hdr = $id > 0 ? htmlentities(mb_substr(mysql_result(mysql_query("SELECT `name` FROM `" . $tab . "` WHERE `id`=" . $id . " LIMIT 1"), 0), 0, 30), ENT_QUOTES, 'UTF-8') : '';
    if ($hdr) {
        $textl = mb_strlen($hdr) > 30 ? $hdr . '...' : $hdr;
    }
}

require_once('../incfiles/head.php');

?>

    <!-- style table image -->
    <style type="text/css">
        .avatar {
            display: table-cell;
            vertical-align: top;
        }

        .avatar img {
            height: 32px;
            margin-right: 5px;
            margin-bottom: 5px;
            width: 32px;
        }

        .righttable {
            display: table-cell;
            vertical-align: top;
            width: 100%;
        }
    </style>
    <!-- end style -->

<?php

if (!$set['mod_lib']) {
    echo functions::display_error($lng_lib['library_closed']);
}

$array_includes = array(
    'addnew',
    'comments',
    'del',
    'download',
    'mkdir',
    'moder',
    'move',
    'new',
    'premod',
    'search',
    'topread',
    'tags',
    'tagcloud'
);
$i = 0;

if (in_array($act, $array_includes)) {
    require_once('includes/' . $act . '.php');
} else {
    if (!$id) {
        echo '<div class="phdr"><b>' . $lng['library'] . '</b></div>';
        echo '<div class="topmenu"><a href="?act=search">' . $lng['search'] . '</a> | <a href="?act=tagcloud">' . $lng_lib['tagcloud'] . '</a></div>';
        if ($adm) {
            // Считаем число статей, ожидающих модерацию
            $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_texts` WHERE `premod`=0"), 0);
            if ($res > 0) {
                echo '<div>' . $lng['on_moderation'] . ': <a href="?act=premod">' . $res . '</a></div>';
            }
        }

        // Считаем новое в библиотеке

        echo '<div class="gmenu">';
        if ($adm) {
            // Считаем число статей, ожидающих модерацию
            $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_texts` WHERE `premod`=0"), 0);
            if ($res > 0) {
                echo '<div>' . $lng['on_moderation'] . ': <a href="?act=premod">' . $res . '</a></div>';
            }
        }
        $res = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_texts` WHERE `time` > '" . (time() - 259200) . "' AND `premod`=1"), 0);
        if ($res) {
            echo '<div><a href="?act=new">' . $lng_lib['new_articles'] . '</a> (' . $res . ')</div>';
        }

        echo '<div><a href="?act=topread">' . $lng_lib['most_readed'] . '</a></div></div>';
        $sql = mysql_query("SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE `parent`=0 ORDER BY `pos` ASC");
        $kol = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_cats` WHERE `parent`=0"), 0);
        $y = 0;
        if ($kol) {
            while ($row = mysql_fetch_assoc($sql)) {
                $y++;
                echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                    . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $row['name'] . '</a> ('
                    . mysql_result(mysql_query("SELECT COUNT(*) FROM `" . ($row['dir'] ? 'library_cats' : 'library_texts') . "` WHERE " . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id'])), 0) . ') '
                    . '<div class="sub"><span class="gray">' . $row['description'] . '</span>';
                if ($adm) {
                    echo '<br/>' . ($y != 1 ? '<a href="?act=move&amp;moveset=up&amp;posid=' . $y . '">' . $lng['up'] . '</a> | ' : $lng['up'] . ' | ') . ($y != $kol ? '<a href="?act=move&amp;moveset=down&amp;posid=' . $y . '">' . $lng['down'] . '</a>' : $lng['down']) . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $row['id'] . '">' . $lng['edit'] . '</a> | <a href="?act=del&amp;type=dir&amp;id=' . $row['id'] . '">' . $lng['delete'] . '</a>';
                }
                echo '</div></div>';
            }
        } else {
            echo '<div class="menu">' . $lng['list_empty'] . '</div>';
        }

        echo '<div class="phdr">' . $lng['total'] . ': ' . $kol . '</div>';
        if ($adm) {
            echo '<div><a href="?act=mkdir&amp;id=0">' . $lng_lib['create_category'] . '</a></div>';
        }
    } else {
        $dir_nav = new Tree($id);
        $dir_nav->process_nav_panel();
        switch ($do) {
            default:
                // dir
                $actdir = mysql_fetch_assoc(mysql_query("SELECT `id`, `dir` FROM `library_cats` WHERE " . ($id !== null ? '`id`=' . $id : 1) . " LIMIT 1"));
                $actdir = $actdir['id'] > 0 ? $actdir['dir'] : redir404();
                echo '<div class="phdr">' . $dir_nav->print_nav_panel() . '</div>';

                if ($actdir) {
                    $sql = mysql_query("SELECT `id`, `name`, `dir`, `description` FROM `library_cats` WHERE " . ($id !== null ? '`parent`=' . $id : '`parent`=0') . ' ORDER BY `pos` ASC');
                    $kol = mysql_result(mysql_query("SELECT COUNT(*) FROM `library_cats` WHERE " . ($id !== null ? '`parent`=' . $id : '`parent`=0')), 0);
                    $y = 0;
                    if ($kol) {
                        while ($row = mysql_fetch_assoc($sql)) {
                            $y++;
                            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                                . '<a href="?do=dir&amp;id=' . $row['id'] . '">' . $row['name'] . '</a>('
                                . mysql_result(mysql_query("SELECT COUNT(*) FROM `" . ($row['dir'] ? 'library_cats' : 'library_texts') . "` WHERE " . ($row['dir'] ? '`parent`=' . $row['id'] : '`cat_id`=' . $row['id'])), 0) . ' '
                                . ($row['dir'] ? ' кат.' : ' ст.') . ')'
                                . '<div class="sub"><span class="gray">' . $row['description'] . '</span></div>';
                            if ($adm) {
                                echo '<div class="sub"><small>' . ($y != 1 ? '<a href="?do=dir&amp;id=' . $id . '&amp;act=move&amp;moveset=up&amp;posid=' . $y . '">' . $lng_lib['up'] . '</a> | ' : '' . $lng_lib['up'] . ' | ') . ($y != $kol ? '<a href="?do=dir&amp;id=' . $id . '&amp;act=move&amp;moveset=down&amp;posid=' . $y . '">' . $lng_lib['down'] . '</a>' : $lng_lib['down']) . ' | <a href="?act=moder&amp;type=dir&amp;id=' . $row['id'] . '">' . $lng['edit'] . '</a> | <a href="?act=del&amp;type=dir&amp;id=' . $row['id'] . '">' . $lng['delete'] . '</a></small></div>';
                            }
                            echo '</div>';
                        }
                    } else {
                        echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
                    }

                    echo '<div class="phdr">' . $lng['total'] . ': ' . $kol . '</div>';

                    if ($adm) {
                        echo '<div><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . $lng['edit'] . '</a></div>'
                            . '<div><a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . $lng['delete'] . '</a></div>'
                            . '<div><a href="?act=mkdir&amp;id=' . $id . '">' . $lng_lib['create_category'] . '</a></div>';
                    }
                } else {
                    $total = mysql_result(mysql_query('SELECT COUNT(*) FROM `library_texts` WHERE `premod`=1 AND `cat_id`=' . $id), 0);
                    $page = $page >= ceil($total / $kmess) ? ceil($total / $kmess) : $page;
                    $start = $page == 1 ? 0 : ($page - 1) * $kmess;
                    $sql2 = mysql_query("SELECT `id`, `name`, `time`, `author`, `count_views`, `count_comments`, `comments`, `announce` FROM `library_texts` WHERE `premod`=1 AND `cat_id`=" . $id . " LIMIT " . $start . "," . $kmess);
                    $nav = ($total > $kmess) ? '<div class="topmenu">' . functions::display_pagination('?id=' . $id . '&amp;', $start, $total, $kmess) . '</div>' : '';
                    if ($total) {
                        echo $nav;

                        while ($row = mysql_fetch_assoc($sql2)) {
                            $obj = new Hashtags($row['id']);
                            echo '<div class="list' . (++$i % 2 ? 2 : 1) . '">'
                                . (file_exists('../files/library/images/small/' . $row['id'] . '.png')
                                    ? '<div class="avatar"><img src="../files/library/images/small/' . $row['id'] . '.png" alt="screen" /></div>'
                                    : '')
                                . '<div class="righttable"><strong><a href="?do=text&amp;id=' . $row['id'] . '">' . $row['name'] . '</a></strong>'
                                . '<div><small>' . bbcode::notags($row['announce']) . '</small></div></div>'
                                . '<div class="sub">'
                                . ($obj->get_all_stat_tags() ? '<span class="gray">' . $lng_lib['tags'] . ':</span> [ ' . $obj->get_all_stat_tags(1) . ' ]<br/>' : '')
                                . '<span class="gray">' . $lng_lib['added'] . ':</span> ' . $row['author'] . ' (' . functions::display_date($row['time']) . ')<br/>'
                                . '<span class="gray">' . $lng_lib['reads'] . ':</span> ' . $row['count_views']
                                . ($row['comments'] ? '<br/><a href="?act=comments&amp;id=' . $row['id'] . '">' . $lng['comments'] . '</a> (' . $row['count_comments'] . ')' : '')
                                . '</div>'
                                . '</div>';
                        }
                    } else {
                        echo '<div class="menu">' . $lng['list_empty'] . '</div>';
                    }

                    echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                    echo $nav;

                    if (($adm || (mysql_result(mysql_query("SELECT `user_add` FROM `library_cats` WHERE `id`=" . $id), 0) > 0)) && isset($id)) {
                        echo '<div><a href="?act=addnew&amp;id=' . $id . '">' . $lng_lib['write_article'] . '</a></div>'
                            . '<div><a href="?act=moder&amp;type=dir&amp;id=' . $id . '">' . $lng['edit'] . '</a></div>'
                            . '<div><a href="?act=del&amp;type=dir&amp;id=' . $id . '">' . $lng['delete'] . '</a></div>';
                    }
                }

                break;

            case 'text':
                $res = mysql_fetch_assoc(mysql_query("SELECT `id`, `cat_id`, `name`, `time`, `premod`, `author`, `count_views`, `count_comments`, `comments` FROM `library_texts` WHERE `id`=" . $id . " LIMIT 1"));
                if ($res['premod'] || $adm) {

                    // Счетчик прочтений
                    if (!isset($_SESSION['lib']) || isset($_SESSION['lib']) && $_SESSION['lib'] != $id) {
                        $_SESSION['lib'] = $id;
                        mysql_query('UPDATE `library_texts` SET  `count_views`=' . ($res['count_views'] ? ++$res['count_views'] : 1) . ' WHERE `id`=' . $id);
                    }

                    // Запрашиваем выбранную статью из базы
                    $symbols = core::$is_mobile ? 3000 : 7000;
                    $count_pages = ceil(mysql_result(mysql_query("SELECT CHAR_LENGTH(`text`) FROM `library_texts` WHERE `id`= '" . $id . "' LIMIT 1"), 0) / $symbols);
                    if ($count_pages) {

                        // Чтоб всегда последнюю страницу считал правильно
                        $page = $page >= $count_pages ? $count_pages : $page;
                        $text = mysql_result(mysql_query("SELECT SUBSTRING(`text`, " . ($page == 1 ? 1 : ($page - 1) * $symbols) . ", " . ($symbols + 100) . ") FROM `library_texts` WHERE `id`='" . $id . "'"), 0);
                        $tmp = mb_substr($text, $symbols, 100);
                    } else {
                        redir404();
                    }

                    $obj = new Hashtags($id);
                    $nav = $count_pages > 1 ? '<div class="topmenu">' . functions::display_pagination('?do=text&amp;id=' . $id . '&amp;', $page == 1 ? 0 : ($page - 1) * 1, $count_pages, 1) . '</div>' : '';
                    $catalog = mysql_fetch_assoc(mysql_query("SELECT `id`, `name` FROM `library_cats` WHERE `id` = " . $res['cat_id'] . " LIMIT 1"));
                    echo '<div class="phdr"><a href="?"><b>' . $lng['library'] . '</b></a> | <a href="?do=dir&amp;id=' . $catalog['id'] . '">' . $catalog['name'] . '</a> | ' . $res['name'] . '</div>'
                        . ($page == 1 && $count_pages >= 1 && file_exists('../files/library/images/big/' . $id . '.png')
                            ? '<div class="topmenu"><a href="../files/library/images/orig/' . $id . '.png"><img style="max-width : 100%;" src="../files/library/images/big/' . $id . '.png" alt="screen" /></a></div>'
                            : '')
                        . ($page == 1 && $count_pages >= 1
                            ? ($obj->get_all_stat_tags() ? '<div class="list1">' . $lng_lib['tags'] . ' [ ' . $obj->get_all_stat_tags(1) . ' ]</div>' : '') : '')
                        . $nav;
                    $text = functions::checkout(mb_substr($text, ($page == 1 ? 0 : min(position($text, PHP_EOL), position($text, ' '))), (($count_pages == 1 || $page == $count_pages) ? $symbols : $symbols + min(position($tmp, PHP_EOL), position($tmp, ' ')) - ($page == 1 ? 0 : min(position($text, PHP_EOL), position($text, ' '))))), 1, 1);
                    if ($set_user['smileys']) {
                        $text = functions::smileys($text, $rights ? 1 : 0);
                    }

                    echo '<div class="list2">' . $text . '</div>';
                    echo ($res['comments']
                            ? '<div class="phdr"><a href="?act=comments&amp;id=' . $res['id'] . '">' . $lng['comments'] . '</a> (' . $res['count_comments'] . ')</div>'
                            : '')
                        . $nav
                        . '<div>' . $lng['download'] . ' <a href="?act=download&amp;type=txt&amp;id=' . $id . '">txt</a> | <a href="?act=download&amp;type=fb2&amp;id=' . $id . '">fb2</a></div>';
                    if ($adm) {
                        echo '<div><a href="?act=moder&amp;type=article&amp;id=' . $id . '">' . $lng['edit'] . '</a></div>'
                            . '<div><a href="?act=del&amp;type=article&amp;id=' . $id . '">' . $lng['delete'] . '</a></div>';
                    }
                } else {
                    redir404();
                }

                break;
        } // end switch
    } // end else !id
} // end else $act
require_once('../incfiles/end.php');