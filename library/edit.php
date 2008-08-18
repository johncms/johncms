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

if ($dostlmod == 1)
{
    if ($_GET['id'] == "" || $_GET['id'] == "0")
    {
        echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval(trim($_GET['id']));
    $typ = mysql_query("select * from `lib` where id='" . $id . "';");
    $ms = mysql_fetch_array($typ);
    if (isset($_POST['submit']))
    {
        switch ($ms[type])
        {
            case "bk":
                $name = check($_POST['name']);
                $name = mb_substr($name, 0, 50);
                $anons = check($_POST['anons']);
                $anons = mb_substr($anons, 0, 100);
                mysql_query("update `lib` set name='" . $name . "', soft='" . $anons . "' where id='" . $id . "';");
                header("location: index.php?id=$ms[refid]");
                break;
            case "cat":
                $text = check($_POST['text']);

                if (!empty($_POST['user']))
                {
                    $user = intval($_POST['user']);
                } else
                {
                    $user = 0;
                }
                $mod = intval($_POST['mod']);
                mysql_query("update `lib` set text='" . $text . "',ip='" . $mod . "',soft='" . $user . "' where id='" . $id . "';");
                header("location: index.php?id=$id");
                break;
            default:
                $text = check($_POST['text']);
                mysql_query("update `lib` set text='" . $text . "' where id='" . $id . "';");
                header("location: index.php?id=$ms[refid]");
                break;
        }
    } else
    {
        switch ($ms['type'])
        {
            case "bk":
                echo "Редактируем название статьи<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Название:<br/><input type='text' name='name' value='" . $ms['name'] . "'/><br/>Анонс:<br/><input type='text' name='anons' value='" . $ms['soft'] .
                    "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                break;
            case "komm":
                echo "Редактируем пост<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Изменить:<br/><input type='text' name='text' value='" . $ms['text'] .
                    "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $ms['refid'] . "'>Назад</a><br/>";
                break;
            case "cat":

                echo "Редактируем категорию<br/><form action='index.php?act=edit&amp;id=" . $id . "' method='post'>Изменить:<br/><input type='text' name='text' value='" . $ms['text'] .
                    "'/><br/>Тип категории(во избежание глюков перед изменением типа очистите категорию!!!):<br/><select name='mod'>";

                if ($ms['ip'] == 1)
                {
                    echo "<option value='1'>Категории</option><option value='0'>Статьи</option>";
                } else
                {
                    echo "<option value='0'>Статьи</option><option value='1'>Категории</option>";
                }
                echo "</select><br/>";
                if ($ms['soft'] == 1)
                {
                    echo "Разрешить юзерам добавлять свои статьи<br/><input type='checkbox' name='user' value='1' checked='checked' /><br/>";
                } else
                {
                    echo "Разрешить юзерам добавлять свои статьи<br/><input type='checkbox' name='user' value='1'/><br/>";
                }

                echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='index.php?id=" . $ms['refid'] . "'>Назад</a><br/>";
                break;
        }
    }
} else
{
    header("location: index.php");
}

?>