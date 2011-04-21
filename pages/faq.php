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
$lng_faq = $core->load_lng('faq');
$textl = 'FAQ';
$headmod = 'faq';
require('../incfiles/head.php');

// Обрабатываем ссылку для возврата
if (empty($_SESSION['ref'])) {
    $_SESSION['ref'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
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
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng_faq['tags_faq'] . '</div>' .
            '<div class="menu"><p>' . $lng_faq['tags_faq_text'] . '</p></div>' .
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

    case 'smusr':
        /*
        -----------------------------------------------------------------
        Каталог пользовательских Смайлов
        -----------------------------------------------------------------
        */
        if (!is_dir($rootpath . 'images/smileys/user/' . $id)) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="faq.php?act=smileys">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        echo '<div class="phdr"><a href="faq.php?act=smileys"><b>' . $lng['smileys'] . '</b></a> | ' . htmlentities(file_get_contents($rootpath . 'images/smileys/user/' . $id . '/name.dat'), ENT_QUOTES, 'utf-8') . '</div>';
        if (!$is_mobile) {
            $user_sm = isset($datauser['smileys']) ? unserialize($datauser['smileys']) : '';
            if (!is_array($user_sm))
                $user_sm = array ();
            echo '<div class="topmenu"><a href="faq.php?act=my_smileys">' . $lng['my_smileys'] . '</a>  (' . count($user_sm) . ' / ' . $user_smileys . ')</div>' .
                '<form action="faq.php?act=set_my_sm&amp;id=' . $id . '&amp;start=' . $start . '" method="post">';
        }
        $array = array ();
        $dir = opendir('../images/smileys/user/' . $id);
        while (($file = readdir($dir)) !== false) {
            if (($file != '.') && ($file != "..") && ($file != "name.dat") && ($file != ".svn") && ($file != "index.php")) {
                $array[] = $file;
            }
        }
        closedir($dir);
        $total = count($array);
        $end = $start + $kmess;
        if ($end > $total)
            $end = $total;
        if ($total > 0) {
            for ($i = $start; $i < $end; $i++) {
                $smile = preg_replace('#^(.*?).(gif|jpg|png)$#isU', '$1', $array[$i], 1);
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                if (!$is_mobile)
                    $smileys = (in_array($smile, $user_sm) ? '' : '<input type="checkbox" name="add_sm[]" value="' . $smile . '" />&#160;');
                echo $smileys . '<img src="../images/smileys/user/' . $id . '/' . $array[$i] . '" alt="" /> - :' . $smile . ': ' . $lng['lng_or'] . ' :' . functions::trans($smile) . ':</div>';
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        if (!$is_mobile)
            echo '<div class="gmenu"><input type="submit" name="add" value=" ' . $lng['add'] . ' "/></div></form>';
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<div class="topmenu">' . functions::display_pagination('faq.php?act=smusr&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</div>';
            echo '<p><form action="faq.php?act=smusr&amp;id=' . $id . '" method="post">' .
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
        if (!$is_mobile) {
            $user_sm = unserialize($datauser['smileys']);
            if (!is_array($user_sm))
                $user_sm = array ();
            echo '<div class="topmenu"><a href="faq.php?act=my_smileys">' . $lng['my_smileys'] . '</a>  (' . count($user_sm) . ' / ' . $user_smileys . ')</div>' .
                '<form action="faq.php?act=set_my_sm&amp;start=' . $start . '&amp;adm" method="post">';
        }
        $array = array ();
        $dir = opendir('../images/smileys/admin');
        while (($file = readdir($dir)) !== false) {
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
                if (!$is_mobile)
                    $smileys = (in_array($smile, $user_sm) ? '' : '<input type="checkbox" name="add_sm[]" value="' . $smile . '" />&#160;');
                echo $smileys . '<img src="../images/smileys/admin/' . $array[$i] . '" alt="" /> - :' . $smile . ': ' . $lng['lng_or'] . ' :' . trans($smile) . ':</div>';
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        if (!$is_mobile)
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
        if ($is_mobile || $page != 1) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="faq.php?act=smileys">' . $lng['smileys'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
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
                 '<input type="checkbox" name="delete_sm[]" value="' . $value . '" />&#160;' . functions::smileys($smile, $rights >= 1 ? 1 : 0) . '&#160;' . $smile . ' ' . $lng['lng_or'] . ' ' . trans($smile) . '</div>';
            $i++;
        }
        if ($total) {
            echo '<div class="rmenu"><input type="submit" name="delete" value=" ' . $lng['delete'] . ' "/></div></form>';
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '<br /><a href="faq.php?act=smileys">' . $lng['add_smileys'] . '</a></p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . ' / ' . $user_smileys . '</div>';
        if ($total > $kmess)
            echo '<div class="topmenu"><p>' . functions::display_pagination('faq.php?act=my_smileys&amp;', $start, $total, $kmess) . '</p></div>';
        echo '<p>' . ($total ? '<a href="faq.php?act=set_my_sm&amp;clean">' . $lng['clear'] . '</a><br />' : '') . '<a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
        break;

    case 'set_my_sm':
        /*
        -----------------------------------------------------------------
        Настраиваем список своих смайлов
        -----------------------------------------------------------------
        */
        $adm = isset($_GET['adm']) ? 1 : 0;
        $add = $_POST['add'];
        $delete = $_POST['delete'];
        if ($is_mobile || ($adm && $rights < 1) || ($add && !$adm && !$id) || ($delete && !$_POST['delete_sm']) || ($add && !$_POST['add_sm'])) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="faq.php?act=smileys">' . $lng['smileys'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $smileys = unserialize($datauser['smileys']);
        if (!is_array($smileys))
            $smileys = array ();
        if ($delete)
            $smileys = array_diff($smileys, $_POST['delete_sm']);
        if ($add) {
            $add_sm = $_POST['add_sm'];
            if (file_exists('../files/cache/smileys_cache.dat')) {
                $file = file('../files/cache/smileys_cache.dat');
                $cache = unserialize($file[0]);
                if ($rights)
                    $cache = array_merge($cache, unserialize($file[1]));
                foreach ($add_sm as $value)
                    if (!array_key_exists(':' . $value . ':', $cache))
                        $delete_sm[] = $value;
                echo print_r($delete_sm);
                if (is_array($delete_sm))
                    $add_sm = array_diff($delete_sm, $add_sm);
            }
            $smileys = array_unique(array_merge($smileys, $add_sm));
        }
        if (isset($_GET['clean']))
            $smileys = array ();
        if (count($smileys) > $user_smileys) {
            $smileys = array_chunk($smileys, $user_smileys, TRUE);
            $smileys = $smileys[0];
        }
        mysql_query("UPDATE `users` SET `smileys` = '" . mysql_real_escape_string(serialize($smileys)) . "' WHERE `id` = '$user_id'");
        if ($delete || isset($_GET['clean'])) {
            header('location: faq.php?act=my_smileys&start=' . $start . '');
        } else {
            header('location: faq.php?act=' . ($adm ? 'smadm' : 'smusr&id=' . $id . '') . '&start=' . $start . '');
        }
        break;

    case 'smileys':
        /*
        -----------------------------------------------------------------
        Главное меню каталога смайлов
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng['smileys'] . '</div>';
        if($user_id && !$is_mobile){
            $mycount = !empty($datauser['smileys']) ? count(unserialize($datauser['smileys'])) : '0';
            echo '<div class="topmenu"><a href="faq.php?act=my_smileys">' . $lng['my_smileys'] . '</a> (' . $mycount . ' / ' . $user_smileys . ')</div>';
        }
        if ($rights >= 1)
            echo '<div class="gmenu"><a href="faq.php?act=smadm">' . $lng_faq['smileys_adm'] . '</a> (' . (int)count(glob($rootpath . 'images/smileys/admin/*.gif')) . ')</div>';
        $dir = glob($rootpath . 'images/smileys/user/*', GLOB_ONLYDIR);
        $total_dir = count($dir);
        for ($i = 0; $i < $total_dir; $i++) {
            echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
            echo '<a href="faq.php?act=smusr&amp;id=' . preg_replace('#^' . $rootpath . 'images/smileys/user/#isU', '', $dir[$i], 1) . '">' . htmlentities(file_get_contents($dir[$i] . '/name.dat'), ENT_QUOTES, 'utf-8') . '</a> ('
                . (int)count(glob($dir[$i] . '/*.gif')) . ')</div>';
        }
        echo '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
        break;

    case 'avatars':
        /*
        -----------------------------------------------------------------
        Каталог пользовательских Аватаров
        -----------------------------------------------------------------
        */
        if ($id && is_dir($rootpath . 'images/avatars/' . $id)) {
            $avatar = isset($_GET['avatar']) ? intval($_GET['avatar']) : false;
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
                echo '<div class="phdr"><a href="faq.php?act=avatars"><b>' . $lng['avatars'] . '</b></a> | ' . htmlentities(file_get_contents($rootpath . 'images/avatars/' . $id . '/name.dat'), ENT_QUOTES, 'utf-8') . '</div>';
                $array = glob($rootpath . 'images/avatars/' . $id . '/*.png');
                $total = count($array);
                $end = $start + $kmess;
                if ($end > $total)
                    $end = $total;
                if ($total > 0) {
                    for ($i = $start; $i < $end; $i++) {
                        $ava = preg_replace('#^' . $rootpath . 'images/avatars/' . $id . '/(.*?).png$#isU', '$1', $array[$i], 1);
                        echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                        echo '<img src="' . $array[$i] . '" alt="" />';
                        if ($user_id)
                            echo ' - <a href="faq.php?act=avatars&amp;id=' . $id . '&amp;avatar=' . $ava . '">' . $lng['select'] . '</a>';
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
            $dir = glob($rootpath . 'images/avatars/*', GLOB_ONLYDIR);
            $total = 0;
            $total_dir = count($dir);
            for ($i = 0; $i < $total_dir; $i++) {
                $count = (int)count(glob($dir[$i] . '/*.png'));
                $total = $total + $count;
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<a href="faq.php?act=avatars&amp;id=' . preg_replace('#^' . $rootpath . 'images/avatars/#isU', '', $dir[$i], 1) . '">' . htmlentities(file_get_contents($dir[$i] . '/name.dat'), ENT_QUOTES, 'utf-8') .
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
            '<div class="menu"><a href="faq.php?act=tags">' . $lng_faq['tags_faq'] . '</a></div>' .
            '<div class="menu"><a href="faq.php?act=trans">' . $lng_faq['translit_help'] . '</a></div>' .
            '<div class="menu"><a href="faq.php?act=avatars">' . $lng['avatars'] . '</a></div>' .
            '<div class="menu"><a href="faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
            '<div class="phdr"><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></div>';
}

require('../incfiles/end.php');
?>