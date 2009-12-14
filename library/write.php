<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                                                                    //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@johncms.com                     //
// Олег Касьянов aka AlkatraZ          alkatraz@johncms.com                   //
//                                                                            //
// Информацию о версиях смотрите в прилагаемом файле version.txt              //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if ($_GET['id'] == "") {
    echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}

// Проверка на спам
$old = ($rights > 0) ? 5 : 60;
if ($datauser['lastpost'] > ($realtime - $old)) {
    require_once ("../incfiles/head.php");
    echo '<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог ' . $old . ' секунд<br/><br/><a href ="index.php?id=' . $id . '">Назад</a></p>';
    require_once ("../incfiles/end.php");
    exit;
}

$typ = mysql_query("select * from `lib` where id='" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($id != 0 && $ms['type'] != "cat") {
    echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
if ($ms['ip'] == 0) {
    if (($rights == 5 || $rights >= 6) || ($ms['soft'] == 1 && !empty ($_SESSION['uid']))) {
        if (isset ($_POST['submit'])) {
            if (empty ($_POST['name'])) {
                echo "Вы не ввели название!<br/><a href='index.php?act=write&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (empty ($_POST['text'])) {
                echo "Вы не ввели текст!<br/><a href='index.php?act=write&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $text = trim($_POST['text']);
            if (!empty ($_POST['anons'])) {
                $anons = mb_substr(trim($_POST['anons']), 0, 100);
            }
            else {
                $anons = mb_substr($text, 0, 100);
            }
            if ($rights == 5 || $rights >= 6) {
                $md = 1;
            }
            else {
                $md = 0;
            }
            mysql_query("INSERT INTO `lib` SET
			`refid` = '" . $id . "',
			`time` = '" . $realtime . "',
			`type` = 'bk',
			`name` = '" . mysql_real_escape_string(mb_substr(trim($_POST['name']), 0, 100)) . "',
			`announce` = '" .
            mysql_real_escape_string($anons) . "',
			`text` = '" . mysql_real_escape_string($text) . "',
			`avtor` = '" . $login . "',
			`ip` = '" . $ipl . "',
			`soft` = '" . mysql_real_escape_string($agn) . "',
			`moder` = '" . $md .
            "'");
            $cid = mysql_insert_id();
            if ($md == 1) {
                echo '<p>Статья добавлена</p>';
            }
            else {
                echo
                '<p>Статья добавлена<br/>Спасибо за то, что нам написали.</p><p>После проверки Модератором, Ваша статья будет опубликована в библиотеке.</p>';
            }
            mysql_query("UPDATE `users` SET `lastpost` = '" . $realtime . "' WHERE `id` = '" . $user_id . "'");
            echo '<p><a href="index.php?id=' . $cid . '">К статье</a></p>';
        }
        else {
            echo 'Добавление статьи<br/><form action="index.php?act=write&amp;id=' . $id . '" method="post">';
            echo 'Введите название(max. 100):<br/><input type="text" name="name"/><br/>';
            echo 'Анонс(max. 100):<br/><input type="text" name="anons"/><br/>';
            echo 'Введите текст:<br/><textarea name="text" cols="20" rows="5"></textarea><br/>';
            echo '<input type="submit" name="submit" value="Ok!"/><br/>';
            echo '</form><a href ="index.php?id=' . $id . '">Назад</a><br/>';
        }
    }
    else {
        header("location: index.php");
    }
}
else {
    echo "Эта категория не для статей,а для других категорий<br/>";
}
echo "<a href='index.php?'>В библиотеку</a><br/>";

?>