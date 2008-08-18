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

if ($_GET['id'] == "")
{
    echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$id = intval($_GET['id']);
$typ = mysql_query("select * from `lib` where id='" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($id != 0 && $ms['type'] != "cat")
{
    echo "Ошибка<br/><a href='index.php?'>В библиотеку</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
if ($ms['ip'] == 0)
{
    if ($dostlmod == 1 || ($ms['soft'] == 1 && !empty($_SESSION['uid'])))
    {
        if (isset($_POST['submit']))
        {
            if (empty($_POST['name']))
            {
                echo "Вы не ввели название!<br/><a href='index.php?act=write&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            if (empty($_POST['text']))
            {
                echo "Вы не ввели текст!<br/><a href='index.php?act=write&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $name = mb_substr($_POST['name'], 0, 50);
            $text = $_POST['text'];
            if (!empty($_POST['anons']))
            {
                $anons = mb_substr($_POST['anons'], 0, 100);
            } else
            {
                $anons = mb_substr($text, 0, 100);
            }
            if ($dostlmod == 1)
            {
                $md = 1;
            } else
            {
                $md = 0;
            }
            mysql_query("INSERT INTO `lib` (
					refid,
					time,
					type,
					name,
					announce,
					text,
					avtor,
					ip,
					soft,
					moder
					) VALUES(
					'" . $id . "',
					'" . $realtime . "',
					'bk',
					'" . mysql_real_escape_string($name) . "',
					'" . mysql_real_escape_string($anons) . "',
					'" . mysql_real_escape_string($text) . "',
					'" . $login . "',
					'" . $ipl . "',
					'" . mysql_real_escape_string($agn) . "',
					'" . $md . "'
					);");
            $cid = mysql_insert_id();
            if ($md == 1)
            {
                echo '<p>Статья добавлена</p>';
            } else
            {
                echo '<p>Статья добавлена<br/>Спасибо за то, что нам написали.</p><p>После проверки Модератором, Ваша статья будет опубликована в библиотеке.</p>';
            }
            echo '<p><a href="index.php?id=' . $cid . '">К статье</a></p>';
        } else
        {
            echo "Добавление статьи<br/><form action='index.php?act=write&amp;id=" . $id .
                "' method='post'>Введите название(max. 50):<br/><input type='text' name='name'/><br/>Анонс(max. 100):<br/><input type='text' name='anons'/><br/>Введите текст:<br/><textarea name='text' ></textarea><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href ='index.php?id=" .
                $id . "'>Назад</a><br/>";
        }
    } else
    {
        header("location: index.php");
    }
} else
{
    echo "Ваще то эта категория не для статей,а для других категорий<br/>";
}
echo "<a href='index.php?'>В библиотеку</a><br/>";

?>