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

if ($rights == 5 || $rights >= 6) {
    if ($_GET['id'] == "" || $_GET['id'] == "0") {
        echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $req = mysql_query("SELECT * FROM `lib` WHERE `id` = '" . $id . "'");
    $ms = mysql_fetch_array($req);
    if (isset ($_POST['submit'])) {
        switch ($ms['type']) {
            case "bk" :
                ////////////////////////////////////////////////////////////
                // Сохраняем отредактированную статью                     //
                ////////////////////////////////////////////////////////////
                if (empty ($_POST['name'])) {
                    echo '<p>ОШИБКА!<br />Вы не ввели название!<br/><a href="index.php?act=edit&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                if (empty ($_POST['text'])) {
                    echo '<p>ОШИБКА!<br />Вы не ввели текст<br/><a href="index.php?act=edit&amp;id=' . $id . '">Повторить</a></p>';
                    require_once ('../incfiles/end.php');
                    exit;
                }
                $text = trim($_POST['text']);
                $autor = isset ($_POST['autor']) ? check(trim($_POST['autor'])) : '';
                $count = isset ($_POST['count']) ? abs(intval($_POST['count'])) : '0';
                if (!empty ($_POST['anons'])) {
                    $anons = mb_substr(trim($_POST['anons']), 0, 100);
                }
                else {
                    $anons = mb_substr($text, 0, 100);
                }
                mysql_query("UPDATE `lib` SET
				`name` = '" . mysql_real_escape_string(mb_substr(trim($_POST['name']), 0, 100)) . "',
				`announce` = '" . mysql_real_escape_string($anons) . "',
				`text` = '" . mysql_real_escape_string(
                $text) . "',
				`avtor` = '" . $autor . "',
				`count` = '" . $count . "'
				WHERE `id` = '" . $id . "'");
                header('location: index.php?id=' . $id);
                break;

            case "cat" :
                ////////////////////////////////////////////////////////////
                // Сохраняем отредактированную категорию                  //
                ////////////////////////////////////////////////////////////
                $text = check($_POST['text']);
                if (!empty ($_POST['user'])) {
                    $user = intval($_POST['user']);
                }
                else {
                    $user = 0;
                }
                $mod = intval($_POST['mod']);
                mysql_query("UPDATE `lib` SET
				`text` = '" . $text . "',
				`ip` = '" . $mod . "',
				`soft` = '" . $user . "'
				WHERE `id` = '" . $id . "'");
                header('location: index.php?id=' . $id);
                break;

            default :
                ////////////////////////////////////////////////////////////
                // Сохраняем отредактированный комментарий                //
                ////////////////////////////////////////////////////////////
                $text = check($_POST['text']);
                mysql_query("update `lib` set text='" . $text . "' where id='" . $id . "';");
                header("location: index.php?id=$ms[refid]");
                break;
        }
    }
    else {
        switch ($ms['type']) {
            case 'bk' :
                ////////////////////////////////////////////////////////////
                // Форма редактирования статьи                            //
                ////////////////////////////////////////////////////////////
                echo '<div class="phdr"><b>Редактируем статью</b></div>';
                echo '<form action="index.php?act=edit&amp;id=' . $id . '" method="post">';
                echo '<div class="menu"><p><u>Название</u><br /><input type="text" name="name" value="' . htmlentities($ms['name'], ENT_QUOTES, 'UTF-8') . '"/></p>';
                echo '<p><u>Анонс</u><br /><small>Если поле оставить пустым, то анонс будет создан автоматически</small><br/><input type="text" name="anons" value="' . htmlentities($ms
                ['announce'], ENT_QUOTES, 'UTF-8') . '"/></p>';
                echo '<p><u>Текст</u><br/><textarea rows="5" name="text">' . htmlentities($ms['text'], ENT_QUOTES, 'UTF-8') . '</textarea></p></div>';
                echo '<div class="rmenu"><p><u>Автор</u><br /><input type="text" name="autor" value="' . $ms['avtor'] . '"/></p>';
                echo '<p><u>Прочтений</u><br /><input type="text" name="count" value="' . $ms['count'] . '" size="4"/></p></div>';
                echo '<div class="bmenu"><input type="submit" name="submit" value="Ok!"/></div></form>';
                echo '<p><a href="index.php?id=' . $id . '">Назад</a></p>';
                break;

            case "komm" :
                echo "Редактируем пост<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Изменить:<br/><input type='text' name='text' value='" . $ms['text'] .
                "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $ms['refid'] . "'>Назад</a><br/>";
                break;

            case "cat" :
                echo "Редактируем категорию<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Изменить:<br/><input type='text' name='text' value='" . $ms['text'] .
                "'/><br/>Тип категории(во избежание глюков перед изменением типа очистите категорию!!!):<br/><select name='mod'>";
                if ($ms['ip'] == 1) {
                    echo "<option value='1'>Категории</option><option value='0'>Статьи</option>";
                }
                else {
                    echo "<option value='0'>Статьи</option><option value='1'>Категории</option>";
                }
                echo "</select><br/>";
                if ($ms['soft'] == 1) {
                    echo "Разрешить юзерам добавлять свои статьи<br/><input type='checkbox' name='user' value='1' checked='checked' /><br/>";
                }
                else {
                    echo "Разрешить юзерам добавлять свои статьи<br/><input type='checkbox' name='user' value='1'/><br/>";
                }
                echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $ms['refid'] . "'>Назад</a><br/>";
                break;
        }
    }
}
else {
    header("location: index.php");
}

?>