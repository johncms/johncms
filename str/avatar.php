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

$textl = 'Аватары';

require_once ('../incfiles/core.php');
require_once ('../incfiles/head.php');

if (!$user_id) {
    display_error('Только для зарегистрированных посетителей');
    require_once ('../incfiles/end.php');
    exit;
}

switch ($act) {
    case 'choice' :
        if ($_GET['ava'] && intval($_GET['cat'])) {
            $ava = intval($_GET['ava']);
            $cat = intval($_GET['cat']);
            $av = '../avatars/' . $cat . '/' . $ava . '.png';
            copy($av, '../files/avatar/' . $user_id . '.png');
        }
        echo '<p>Аватар успешно установлен!<br /><a href="my_data.php?id=' . $user_id . '">Продолжить</a><br/><a href="avatar.php">В категории</a></p>';
        break;

    case 'cat' :
        if (!is_dir($rootpath . 'avatars/' . $id)) {
            echo $id;
            echo '<p>Ошибка!<br/><a href="avatar.php">В категории</a></p>';
            require_once ('../incfiles/end.php');
            exit;
        }
        echo '<div class="phdr">Аватары: <b>' . htmlentities(file_get_contents($rootpath . 'avatars/' . $id . '/name.dat'), ENT_QUOTES, 'utf-8') . '</b></div>';
        $array = glob($rootpath . 'avatars/' . $id . '/*.png');
        $total = count($array);
        $end = $start + $kmess;
        if ($end > $total)
            $end = $total;
        if ($total > 0) {
            for ($i = $start; $i < $end; $i++) {
                $ava = preg_replace('#^' . $rootpath . 'avatars/' . $id . '/(.*?).png$#isU', '$1', $array [$i], 1);
                echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                echo '<img src="' . $array [$i] . '" alt="" /> - <a href="avatar.php?act=choice&amp;cat=' . $id . '&amp;ava=' . $ava . '">Выбрать</a></div>';
            }
        }
        else {
            echo 'Аватар в категории нет!<br/>';
        }
        echo '<div class="phdr">Всего: ' . $total . '</div>';
        if ($total > $kmess) {
            echo '<p>' . pagenav('avatar.php?act=cat&amp;id=' . $id . '&amp;', $start, $total, $kmess) . '</p>';
            echo '<p><form action="avatar.php" method="get"><input type="hidden" value="cat" name="act" /><input type="hidden" value="' . $id .
            '" name="id" /><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
        }
        echo '<p><a href="avatar.php">В категории</a></p>';
        break;

    default :
        if (empty ($_SESSION['refsm'])) {
            $_SESSION['refsm'] = htmlspecialchars($_SERVER['HTTP_REFERER']);
        }
        echo '<div class="phdr"><b>Каталог аватаров</b></div>';
        $dir = glob($rootpath . 'avatars/*', GLOB_ONLYDIR);
        $total_dir = count($dir);
        for ($i = 0; $i < $total_dir; $i++) {
            echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
            echo '<a href="avatar.php?act=cat&amp;id=' . preg_replace('#^' . $rootpath . 'avatars/#isU', '', $dir[$i], 1) . '">' . htmlentities(file_get_contents($dir[$i] . '/name.dat'), ENT_QUOTES, 'utf-8') . '</a> (' . (int) count(glob($dir
            [$i] . '/*.png')) . ')</div>';
        }
        echo '<div class="phdr"><a href="' . $_SESSION['refsm'] . '">Назад</a></div>';
        break;
}

require_once ('../incfiles/end.php');

?>