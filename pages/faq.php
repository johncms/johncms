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
require('../incfiles/core.php');
$lng_faq = core::load_lng('faq');
$lng_smileys = core::load_lng('smileys');
$textl = 'FAQ';
$headmod = 'faq';
require('../incfiles/head.php');

// Обрабатываем ссылку для возврата
if (empty($_SESSION['ref'])) {
    $_SESSION['ref'] = isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : $home;
}

// Сколько смайлов разрешено выбрать пользователям?
$user_smileys = 20;

switch ($act) {
    case 'forum':
        /*
        -----------------------------------------------------------------
        Правила Форума
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng_faq['forum_rules'] . '</div>' .
            '<div class="menu"><p>' . $lng_faq['forum_rules_text'] . '</p></div>' .
            '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
        break;

    case 'tags':
        /*
        -----------------------------------------------------------------
        Справка по BBcode
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng_faq['tags'] . '</div>' .
            '<div class="menu"><p>' .
            '<table cellpadding="3" cellspacing="0">' .
            '<tr><td align="right"><h3>BBcode</h3></td><td></td></tr>' .
            '<tr><td align="right">[php]...[/php]</td><td>' . $lng['tag_code'] . '</td></tr>' .
            '<tr><td align="right"><a href="#">' . $lng['link'] . '</a></td><td>[url=http://site_url]<span style="color:blue">' . $lng_faq['tags_link_name'] . '</span>[/url]</td></tr>' .
            '<tr><td align="right">[b]...[/b]</td><td><b>' . $lng['tag_bold'] . '</b></td></tr>' .
            '<tr><td align="right">[i]...[/i]</td><td><i>' . $lng['tag_italic'] . '</i></td></tr>' .
            '<tr><td align="right">[u]...[/u]</td><td><u>' . $lng['tag_underline'] . '</u></td></tr>' .
            '<tr><td align="right">[s]...[/s]</td><td><strike>' . $lng['tag_strike'] . '</strike></td></tr>' .
            '<tr><td align="right">[red]...[/red]</td><td><span style="color:red">' . $lng['tag_red'] . '</span></td></tr>' .
            '<tr><td align="right">[green]...[/green]</td><td><span style="color:green">' . $lng['tag_green'] . '</span></td></tr>' .
            '<tr><td align="right">[blue]...[/blue]</td><td><span style="color:blue">' . $lng['tag_blue'] . '</span></td></tr>' .
            '<tr><td align="right">[color=]...[/color]</td><td>' . $lng['color_text'] . '</td></tr>' .
            '<tr><td align="right">[bg=][/bg]</td><td>' . $lng['color_bg'] . '</td></tr>' .
            '<tr><td align="right">[c]...[/c]</td><td><span class="quote">' . $lng['tag_quote'] . '</span></td></tr>' .
            '<tr><td align="right" valign="top">[*]...[/*]</td><td><span class="bblist">' . $lng['tag_list'] . '</span></td></tr>' .
            '<tr><td align="right" valign="top">Spoiler</td><td>[spoiler=' . $lng['title'] . ']' . $lng['text'] . '[/spoiler]</td></tr>' .
            '</table>' .
            '</p></div>' .
            '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
        break;

    case 'trans':
        /*
        -----------------------------------------------------------------
        Справка по Транслиту
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng_faq['translit_help'] . '</div>' .
            '<div class="menu"><p>' . $lng_faq['translit_help_text'] . '</p></div>' .
            '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
        break;

    case 'smileys':
        /*
        -----------------------------------------------------------------
        Главное меню каталога смайлов
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng['smileys'] . '</div>';
        if ($user_id) {
            $mycount = !empty($datauser['smileys']) ? count(unserialize($datauser['smileys'])) : '0';
            echo '<div class="topmenu"><a href="faq.php?act=my_smileys">' . $lng['my_smileys'] . '</a> (' . $mycount . ' / ' . $user_smileys . ')</div>';
        }
        if ($rights >= 1)
            echo '<div class="gmenu"><a href="faq.php?act=smadm">' . $lng_faq['smileys_adm'] . '</a> (' . (int)count(glob(ROOTPATH . 'images/smileys/admin/*.gif')) . ')</div>';
        $dir = glob(ROOTPATH . 'images/smileys/user/*', GLOB_ONLYDIR);
        foreach ($dir as $val) {
            $cat = explode('/', $val);
            $cat = array_pop($cat);
            if (array_key_exists($cat, $lng_smileys)) {
                $smileys_cat[$cat] = $lng_smileys[$cat];
            } else {
                $smileys_cat[$cat] = ucfirst($cat);
            }
        }
        asort($smileys_cat);
        $i = 0;
        foreach ($smileys_cat as $key => $val) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo '<a href="faq.php?act=smusr&amp;cat=' . urlencode($key) . '">' . htmlspecialchars($val) . '</a>' .
                ' (' . count(glob(ROOTPATH . 'images/smileys/user/' . $key . '/*.{gif,jpg,png}', GLOB_BRACE)) . ')';
            echo '</div>';
            ++$i;
        }
        echo '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
        break;

    case 'smusr':
        /*
        -----------------------------------------------------------------
        Каталог пользовательских Смайлов
        -----------------------------------------------------------------
        */
        $dir = glob(ROOTPATH . 'images/smileys/user/*', GLOB_ONLYDIR);
        foreach ($dir as $val) {
            $val = explode('/', $val);
            $cat_list[] = array_pop($val);
        }
        $cat = isset($_GET['cat']) && in_array(trim($_GET['cat']), $cat_list) ? trim($_GET['cat']) : $cat_list[0];
        $smileys = glob(ROOTPATH . 'images/smileys/user/' . $cat . '/*.{gif,jpg,png}', GLOB_BRACE);
        $total = count($smileys);
        $end = $start + $kmess;
        if ($end > $total) $end = $total;
        echo '<div class="phdr"><a href="faq.php?act=smileys"><b>' . $lng['smileys'] . '</b></a> | ' .
            (array_key_exists($cat, $lng_smileys) ? $lng_smileys[$cat] : ucfirst(htmlspecialchars($cat))) .
            '</div>';
        if ($total) {
            if ($user_id) {
                $user_sm = isset($datauser['smileys']) ? unserialize($datauser['smileys']) : '';
                if (!is_array($user_sm)) $user_sm = array();
                echo '<div class="topmenu">' .
                    '<a href="faq.php?act=my_smileys">' . $lng['my_smileys'] . '</a>  (' . count($user_sm) . ' / ' . $user_smileys . ')</div>' .
                    '<form action="faq.php?act=set_my_sm&amp;cat=' . $cat . '&amp;start=' . $start . '" method="post">';
            }
            if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('faq.php?act=smusr&amp;cat=' . urlencode($cat) . '&amp;', $start, $total, $kmess) . '</div>';
            for ($i = $start; $i < $end; $i++) {
                $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', basename($smileys[$i], 1));
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                if ($user_id) echo(in_array($smile, $user_sm) ? '' : '<input type="checkbox" name="add_sm[]" value="' . $smile . '" />&#160;');
                echo '<img src="../images/smileys/user/' . $cat . '/' . basename($smileys[$i]) . '" alt="" />&#160;:' . $smile . ': ' . $lng['lng_or'] . ' :' . functions::trans($smile) . ':';
                echo '</div>';
            }
            if ($user_id) echo '<div class="gmenu"><input type="submit" name="add" value=" ' . $lng['add'] . ' "/></div></form>';
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('faq.php?act=smusr&amp;cat=' . urlencode($cat) . '&amp;', $start, $total, $kmess) . '</div>';
            echo '<p><form action="faq.php?act=smusr&amp;cat=' . urlencode($cat) . '" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
        break;

    case 'smadm':
        /*
        -----------------------------------------------------------------
        Каталог Админских Смайлов
        -----------------------------------------------------------------
        */
        if ($rights < 1) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="faq.php?act=smileys">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        echo '<div class="phdr"><a href="faq.php?act=smileys"><b>' . $lng['smileys'] . '</b></a> | ' . $lng_faq['smileys_adm'] . '</div>';
        $user_sm = unserialize($datauser['smileys']);
        if (!is_array($user_sm))
            $user_sm = array();
        echo '<div class="topmenu"><a href="faq.php?act=my_smileys">' . $lng['my_smileys'] . '</a>  (' . count($user_sm) . ' / ' . $user_smileys . ')</div>' .
            '<form action="faq.php?act=set_my_sm&amp;start=' . $start . '&amp;adm" method="post">';
        $array = array();
        $dir = opendir('../images/smileys/admin');
        while (($file = readdir($dir)) !== FALSE) {
            if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
                $array[] = $file;
            }
        }
        closedir($dir);
        $total = count($array);
        if ($total > 0) {
            $end = $start + $kmess;
            if ($end > $total)
                $end = $total;
            for ($i = $start; $i < $end; $i++) {
                $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', $array[$i], 1);
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                $smileys = (in_array($smile, $user_sm) ? ''
                    : '<input type="checkbox" name="add_sm[]" value="' . $smile . '" />&#160;');
                echo $smileys . '<img src="../images/smileys/admin/' . $array[$i] . '" alt="" /> - :' . $smile . ': ' . $lng['lng_or'] . ' :' . functions::trans($smile) . ':</div>';
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        echo '<div class="gmenu"><input type="submit" name="add" value=" ' . $lng['add'] . ' "/></div></form>';
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('faq.php?act=smadm&amp;', $start, $total, $kmess) . '</div>';
            echo '<p><form action="faq.php?act=smadm" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
        break;

    case 'my_smileys':
        /*
        -----------------------------------------------------------------
        Список своих смайлов
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php?act=smileys"><b>' . $lng['smileys'] . '</b></a> | ' . $lng['my_smileys'] . '</div>';
        $smileys = !empty($datauser['smileys']) ? unserialize($datauser['smileys']) : array();
        $total = count($smileys);
        if ($total)
            echo '<form action="faq.php?act=set_my_sm&amp;start=' . $start . '" method="post">';
        if ($total > $kmess) {
            $smileys = array_chunk($smileys, $kmess, TRUE);
            if ($start) {
                $key = ($start - $start % $kmess) / $kmess;
                $smileys_view = $smileys[$key];
                if (!count($smileys_view))
                    $smileys_view = $smileys[0];
                $smileys = $smileys_view;
            } else {
                $smileys = $smileys[0];
            }
        }
        $i = 0;
        foreach ($smileys as $value) {
            $smile = ':' . $value . ':';
            echo ($i % 2 ? '<div class="list2">' : '<div class="list1">') .
                '<input type="checkbox" name="delete_sm[]" value="' . $value . '" />&#160;' .
                functions::smileys($smile, $rights >= 1 ? 1 : 0) . '&#160;' . $smile . ' ' . $lng['lng_or'] . ' ' . functions::trans($smile) . '</div>';
            $i++;
        }
        if ($total) {
            echo '<div class="rmenu"><input type="submit" name="delete" value=" ' . $lng['delete'] . ' "/></div></form>';
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '<br /><a href="faq.php?act=smileys">' . $lng['add_smileys'] . '</a></p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . ' / ' . $user_smileys . '</div>';
        if ($total > $kmess)
            echo '<div class="topmenu">' . functions::display_pagination('faq.php?act=my_smileys&amp;', $start, $total, $kmess) . '</div>';
        echo '<p>' . ($total ? '<a href="faq.php?act=set_my_sm&amp;clean">' . $lng['clear'] . '</a><br />'
                : '') . '<a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
        break;

    case 'set_my_sm':
        /*
        -----------------------------------------------------------------
        Настраиваем список своих смайлов
        -----------------------------------------------------------------
        */
        $adm = isset($_GET['adm']);
        $add = isset($_POST['add']);
        $delete = isset($_POST['delete']);
        $cat = isset($_GET['cat']) ? trim($_GET['cat']) : '';
        if (($adm && !$rights) || ($add && !$adm && !$cat) || ($delete && !$_POST['delete_sm']) || ($add && !$_POST['add_sm'])) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="faq.php?act=smileys">' . $lng['smileys'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $smileys = unserialize($datauser['smileys']);
        if (!is_array($smileys))
            $smileys = array();
        if ($delete)
            $smileys = array_diff($smileys, $_POST['delete_sm']);
        if ($add) {
            $add_sm = $_POST['add_sm'];
            $smileys = array_unique(array_merge($smileys, $add_sm));
        }
        if (isset($_GET['clean']))
            $smileys = array();
        if (count($smileys) > $user_smileys) {
            $smileys = array_chunk($smileys, $user_smileys, TRUE);
            $smileys = $smileys[0];
        }
        $stmt = $db->prepare("UPDATE `users` SET `smileys` = ? WHERE `id` = '$user_id' LIMIT 1");
        $stmt->execute([
            serialize($smileys)
        ]);
        if ($delete || isset($_GET['clean'])) {
            header('location: faq.php?act=my_smileys&start=' . $start); exit;
        } else {
            header('location: faq.php?act=' . ($adm ? 'smadm' : 'smusr&cat=' . urlencode($cat) . '') . '&start=' . $start); exit;
        }
        break;

    case 'avatars':
        /*
        -----------------------------------------------------------------
        Каталог пользовательских Аватаров
        -----------------------------------------------------------------
        */
        if ($id && is_dir(ROOTPATH . 'images/avatars/' . $id)) {
            $avatar = isset($_GET['avatar']) ? intval($_GET['avatar']) : FALSE;
            if ($user_id && $avatar && is_file('../images/avatars/' . $id . '/' . $avatar . '.png')) {
                if (isset($_POST['submit'])) {
                    // Устанавливаем пользовательский Аватар
                    if (@copy('../images/avatars/' . $id . '/' . $avatar . '.png', '../files/users/avatar/' . $user_id . '.png')) {
                        echo '<div class="gmenu"><p>' . $lng['avatar_applied'] . '<br />' .
                            '<a href="../users/profile.php?act=edit">' . $lng['continue'] . '</a></p></div>';
                    } else {
                        echo functions::display_error($lng['error_avatar_select'], '<a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a>');
                    }
                } else {
                    echo '<div class="phdr"><a href="faq.php?act=avatars"><b>' . $lng['avatars'] . '</b></a> | ' . $lng_faq['set_to_profile'] . '</div>' .
                        '<div class="rmenu"><p>' . $lng_faq['avatar_change_warning'] . '</p>' .
                        '<p><img src="../images/avatars/' . $id . '/' . $avatar . '.png" alt="" /></p>' .
                        '<p><form action="faq.php?act=avatars&amp;id=' . $id . '&amp;avatar=' . $avatar . '" method="post"><input type="submit" name="submit" value="' . $lng['save'] . '"/></form></p>' .
                        '</div>' .
                        '<div class="phdr"><a href="faq.php?act=avatars&amp;id=' . $id . '">' . $lng['cancel'] . '</a></div>';
                }
            } else {
                // Показываем список Аватаров
                echo '<div class="phdr"><a href="faq.php?act=avatars"><b>' . $lng['avatars'] . '</b></a> | ' . htmlentities(file_get_contents(ROOTPATH . 'images/avatars/' . $id . '/name.dat'), ENT_QUOTES, 'utf-8') . '</div>';
                $array = glob(ROOTPATH . 'images/avatars/' . $id . '/*.png');
                $total = count($array);
                $end = $start + $kmess;
                if ($end > $total)
                    $end = $total;
                if ($total > 0) {
                    for ($i = $start; $i < $end; $i++) {
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        echo '<img src="../images/avatars/' . $id . '/' . basename($array[$i]) . '" alt="" />';
                        if ($user_id)
                            echo ' - <a href="faq.php?act=avatars&amp;id=' . $id . '&amp;avatar=' . basename($array[$i]) . '">' . $lng['select'] . '</a>';
                        echo '</div>';
                    }
                } else {
                    echo '<div class="menu">' . $lng['list_empty'] . '</div>';
                }
                echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
                if ($total > $kmess) {
                    echo '<p>' . functions::display_pagination('faq.php?act=avatars&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>' .
                        '<p><form action="faq.php?act=avatars&amp;id=' . $id . '" method="post">' .
                        '<input type="text" name="page" size="2"/>' .
                        '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
                        '</form></p>';
                }
                echo '<p><a href="faq.php?act=avatars">' . $lng['catalogue'] . '</a><br />' .
                    '<a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
            }
        } else {
            // Показываем каталоги с Аватарами
            echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng['avatars'] . '</div>';
            $dir = glob(ROOTPATH . 'images/avatars/*', GLOB_ONLYDIR);
            $total = 0;
            $total_dir = count($dir);
            for ($i = 0; $i < $total_dir; $i++) {
                $count = (int)count(glob($dir[$i] . '/*.png'));
                $total = $total + $count;
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<a href="faq.php?act=avatars&amp;id=' . basename($dir[$i]) . '">' . htmlentities(file_get_contents($dir[$i] . '/name.dat'), ENT_QUOTES, 'utf-8') .
                    '</a> (' . $count . ')</div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>' .
                '<p><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Главное меню FAQ
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><b>F.A.Q.</b></div>' .
            '<div class="menu"><a href="faq.php?act=forum">' . $lng_faq['forum_rules'] . '</a></div>' .
            '<div class="menu"><a href="faq.php?act=tags">' . $lng_faq['tags'] . '</a></div>';
        if (core::$user_set['translit']) echo '<div class="menu"><a href="faq.php?act=trans">' . $lng_faq['translit_help'] . '</a></div>';
        echo '<div class="menu"><a href="faq.php?act=avatars">' . $lng['avatars'] . '</a></div>' .
            '<div class="menu"><a href="faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
            '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
}

require('../incfiles/end.php');