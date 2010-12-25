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
        $array = array ();
        $dir = opendir('../images/smileys/user/' . $id);
        while ($file = readdir($dir)) {
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
                echo '<img src="../images/smileys/user/' . $id . '/' . $array[$i] . '" alt="" /> - :' . $smile . ': ' . $lng['lng_or'] . ' :' . functions::trans($smile) . ':</div>';
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . functions::display_pagination('faq.php?act=smusr&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>';
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
        $array = array ();
        $dir = opendir('../images/smileys/admin');
        while ($file = readdir($dir)) {
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
                echo '<img src="../images/smileys/admin/' . $array[$i] . '" alt="" /> - :' . $smile . ': ' . $lng['lng_or'] . ' :' . trans($smile) . ':</div>';
            }
        } else {
            echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
        }
        echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . functions::display_pagination('faq.php?act=smadm&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="faq.php?act=smadm" method="post">' .
                '<input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a></p>';
        break;

    case 'smileys':
        /*
        -----------------------------------------------------------------
        Главное меню каталога смайлов
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="faq.php"><b>F.A.Q.</b></a> | ' . $lng['smileys'] . '</div>';
        if ($rights >= 1) {
            echo '<div class="gmenu"><a href="faq.php?act=smadm">' . $lng_faq['smileys_adm'] . '</a> (' . (int)count(glob($rootpath . 'images/smileys/admin/*.gif')) . ')</div>';
        }
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
                // Устанавливаем пользовательский Аватар
                if(@copy('../images/avatars/' . $id . '/' . $avatar . '.png', '../files/users/avatar/' . $user_id . '.png'))
                    echo '<div class="gmenu"><p>' . $lng['avatar_applied'] . '<br />' .
                        '<a href="../users/profile.php?act=edit">' . $lng['continue'] . '</a></p></div>';
                else
                    echo functions::display_error($lng['error_avatar_select'], '<a href="' . $_SESSION['ref'] . '">' . $lng['back'] . '</a>');
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