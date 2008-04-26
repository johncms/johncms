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
*/

defined('_IN_JOHNCMS') or die('Error: restricted access');

if (empty($_GET['id']))
{
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/><a href='index.php'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$id = intval(check($_GET['id']));
if (empty($_SESSION['uid']))
{
    require_once ("../incfiles/head.php");
    echo "Вы не авторизованы!<br/>";
    require_once ("../incfiles/end.php");
    exit;
}

$type = mysql_query("select * from `forum` where id= '" . $id . "';");
$type1 = mysql_fetch_array($type);
$tip = $type1['type'];
if ($tip != "r")
{
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
if (isset($_POST['submit']))
{
    $flt = $realtime - 30;
    $af = mysql_query("select * from `forum` where type='m' and time>'" . $flt . "' and `from`= '" . $login . "';");
    $af1 = mysql_num_rows($af);
    if ($af1 != 0)
    {
        require_once ("../incfiles/head.php");
        echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='?id=" . $id . "'>В раздел</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (empty($_POST['th']))
    {
        require_once ("../incfiles/head.php");
        echo "Вы не ввели название темы!<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if (empty($_POST['msg']))
    {
        require_once ("../incfiles/head.php");
        echo "Вы не ввели сообщение!<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    $th = mb_substr($th, 0, 100);
    $th = check(trim($_POST['th']));
    $msg = check(trim($_POST['msg']));
    if ($_POST['msgtrans'] == 1)
    {
        $th = trans($th);
		$msg = trans($msg);
    }
    $pt = mysql_query("select `id` from `forum` where type='t' and refid='" . $id . "' and text='" . $th . "';");
    if (mysql_num_rows($pt) != 0)
    {
        require_once ("../incfiles/head.php");
        echo "Ошибка!Тема с таким названием уже есть в этом разделе<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if ($fmod != 1)
    {
        $fmd = 1;
    } else
    {
        $fmd = 0;
    }
    mysql_query("insert into `forum` values(0,'" . $id . "','t','" . $realtime . "','" . $login . "','','','','','" . $th . "','','','" . $fmd . "','','','','','');");
    $rid = mysql_insert_id();
    $thm = mysql_query("select `id`, `refid` from `forum` where type='t'  and id= '" . $rid . "';");
    $tem1 = mysql_fetch_array($thm);
    $agn = strtok($agn, ' ');
    mysql_query("insert into `forum` values(0,'" . $rid . "','m','" . $realtime . "','" . $login . "','','','" . $ipp . "','" . $agn . "','" . $msg . "','','','','','','','" . $ch . "','');");
    $postid = mysql_insert_id();
    $fpst = $datauser['postforum'] + 1;
    mysql_query("update `users` set  postforum='" . $fpst . "' where id='" . intval($_SESSION['uid']) . "';");
    if ($fmod != 1)
    {
        $hid = $rid;
    } else
    {
        $hid = $tem1[refid];
    }
    #echo "Тема добавлена<br/><a href='index.php?id=" . $hid . "'>Продолжить</a><br/>";
    $np = mysql_query("select `id` from `forum` where type='l' and refid='" . $tem1[id] . "' and `from`='" . $login . "';");
    $np1 = mysql_num_rows($np);
    if ($np1 == 0)
    {
        mysql_query("insert into `forum` values(0,'" . $tem1[id] . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','');");
    } else
    {
        $np2 = mysql_fetch_array($np);
        mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
    }
    $addfiles = intval($_POST[addfiles]);
    if ($addfiles == 1)
    {
        header("Location: index.php?id=$postid&act=addfile");
    } else
    {
        header("Location: index.php?id=$hid");
    }
} else
{
    require_once ("../incfiles/head.php");
    if ($datauser['postforum'] == 0)
    {
        if (!isset($_GET['yes']))
        {
            include ("../pages/forum.txt");
            echo "<a href='index.php?act=nt&amp;id=" . $id . "&amp;yes'>Согласен</a>|<a href='index.php?id=" . $id . "'>Не согласен</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
    }
    if ($fmod == 1)
    {
        echo "Внимание!В данный момент в форуме включена премодерация тем,то есть Ваша тема будет открыта для общего доступа только после проверки модератором.<br/>";
    }
    echo "Добавление темы в раздел <font color='" . $cntem . "'>$type1[text]</font>:<br/><form action='index.php?act=nt&amp;id=" . $id .
        "' method='post' enctype='multipart/form-data'>Название(max. 100):<br/><input type='text' size='20' maxlength='100' title='Введите название темы' name='th'/><br/>Сообщение(max. 500):<br/><textarea cols='20' rows='3' title='Введите сообщение' name='msg'></textarea><br/><input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
    if ($offtr != 1)
    {
        echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
    }
    echo "<input type='submit' name='submit' title='Нажмите для отправки' value='Отправить'/><br/></form>";
    echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
    echo "<a href='?id=" . $id . "'>Назад</a><br/>";
}

?>