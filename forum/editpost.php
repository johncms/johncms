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

require_once ("../incfiles/head.php");
if (empty($_GET['id']))
{
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
if (empty($_SESSION['uid']))
{
    echo "Вы не авторизованы!<br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$typ = mysql_query("select * from `forum` where id='" . $id . "';");
$ms = mysql_fetch_array($typ);
if ($ms['type'] != "m")
{
    echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}

$lp = mysql_query("select * from `forum` where type='m' and refid='" . $ms['refid'] . "'  order by time desc ;");
while ($arr = mysql_fetch_array($lp))
{
    $idpp[] = $arr['id'];
}
$idpr = $idpp[0];
$tpp = $realtime - 300;
$lp1 = mysql_query("select * from `forum` where id='" . $idpr . "';");
$arr1 = mysql_fetch_array($lp1);
if (($dostfmod != 1) && (($ms['from'] != $login) || ($arr1['id'] != $ms['id']) || ($ms['time'] < $tpp)))
{
    echo "Ошибка!Вероятно,прошло более 5 минут со времени написания поста,или он уже не последний<br/><a href='?id=" . $ms['refid'] . "'>В тему</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
if (($dostfmod == 1) || (($arr1['from'] == $login) && ($arr1['id'] == $ms['id']) && ($ms['time'] > $tpp)))
{
    if (isset($_POST['submit']))
    {
        if (empty($_POST['msg']))
        {
            echo "Вы не ввели сообщение!<br/><a href='?act=editpost&amp;id=" . $id . "'>Повторить</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        $msg = mysql_real_escape_string(trim($_POST['msg']));
        if ($_POST['msgtrans'] == 1)
        {
            $msg = trans($msg);
        }
        $koled = $ms['kedit'] + 1;
        mysql_query("update `forum` set  tedit='" . $realtime . "', edit='" . $login . "', kedit='" . $koled . "', text='" . $msg . "' where id='" . $id . "';");
        $pa = mysql_query("select * from `forum` where type='m' and refid= '" . $id . "';");
        $pa2 = mysql_num_rows($pa);

        if ((!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1))
        {
            $page = 1;
        } else
        {
            $page = ceil($pa2 / $kmess);
        }
        echo "Сообщение изменено.<br/><a href='index.php?id=" . $ms['refid'] . "&amp;page=" . $page . "'>Продолжить</a><br/>";
    } else
    {
        echo "Редактирование сообщения (max. 500):<br/><form action='?act=editpost&amp;id=" . $id . "' method='post'><textarea cols='20' rows='3' title='Введите текст сообщения' name='msg'>$ms[text]</textarea><br/>";
        if ($offtr != 1)
        {
            echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
        }
        echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
    }
}
echo "<a href='index.php?id=" . $ms['refid'] . "'>Назад</a><br/>";

?>