<?php

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
// За основу взят модуль смайлов от Suliman, доработка AlkatraZ               //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);
$textl = 'Смайлы';

require_once('../incfiles/core.php');
require_once('../incfiles/head.php');
switch ($act) {
    case 'cat':
        if (!is_dir($rootpath . 'smileys/user/' . $id)) {
            echo $id;
            echo '<p>Ошибка!<br/><a href="smile.php">В категории</a></p>';
            require_once('../incfiles/end.php');
            exit;
        }
        echo '<div class="phdr"><b>Смайлы:</b> ' . htmlentities(file_get_contents($rootpath . 'smileys/user/' . $id . '/name.dat'), ENT_QUOTES, 'utf-8') . '</div>';
        $array = array();
        $dir = opendir('../smileys/user/' . $id);
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
                echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                echo '<img src="../smileys/user/' . $id . '/' . $array[$i] . '" alt="" /> - :' . $smile . ': или :' . trans($smile) . ':</div>';
            }
        } else {
            echo 'Смайлов в категории нет!<br/>';
        }
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('smile.php?act=cat&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="smile.php" method="get"><input type="hidden" value="cat" name="act" /><input type="hidden" value="' . $id .
                '" name="id" /><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="smile.php">В категории</a></p>';
        break;

    case 'adm':
        if ($rights < 1) {
            echo 'Ошибка!<br/><a href="smile.php">В категории</a><br/>';
            require_once('../incfiles/end.php');
            exit;
        }
        echo '<div class="phdr"><b>Смайлы:</b> Для администрации</div>';
        $array = array();
        $dir = opendir('../smileys/admin');
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
                echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                echo '<img src="../smileys/admin/' . $array[$i] . '" alt="" /> - :' . $smile . ': или :' . trans($smile) . ':</div>';
            }
        } else {
            echo 'Смайлов в категории нет!<br/>';
        }
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('smile.php?act=adm&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="smile.php" method="get"><input type="hidden" value="adm" name="act" /><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="smile.php">В категории</a></p>';
        break;

    default:
        if (empty($_SESSION['refsm'])) {
            $_SESSION['refsm'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }
        echo '<div class="phdr"><b>Каталог смайлов</b></div>';
        $dir = glob($rootpath . 'smileys/user/*', GLOB_ONLYDIR);
        $total_dir = count($dir);
        for ($i = 0; $i < $total_dir; $i++) {
            echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
            echo '<a href="smile.php?act=cat&amp;id=' . preg_replace('#^' . $rootpath . 'smileys/user/#isU', '', $dir[$i], 1) . '">' . htmlentities(file_get_contents($dir[$i] . '/name.dat'), ENT_QUOTES, 'utf-8') . '</a> ('
                . (int)count(glob($dir[$i] . '/*.gif')) . ')</div>';
        }
        if ($rights >= 1) {
            echo '<div class="gmenu"><p><a href="smile.php?act=adm">Для администрации</a> (' . (int)count(glob($rootpath . 'smileys/admin/*.gif')) . ')</p></div>';
        }
        echo '<div class="bmenu"><a href="' . $_SESSION['refsm'] . '">Назад</a></div>';
        break;
}

require_once('../incfiles/end.php');
?>