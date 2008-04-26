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
if (!empty($_SESSION['uid']))
{
    if ($_GET['id'] == "")
    {
        require_once ("../incfiles/head.php");
        echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval(check(trim($_GET['id'])));
    if (isset($_POST['submit']))
    {
        $flt = $realtime - 30;
        $af = mysql_query("select * from `download` where type='komm' and time>'" . $flt . "' and avtor= '" . $login . "';");
        $af1 = mysql_num_rows($af);
        if ($af1 != 0)
        {
            require_once ("../incfiles/head.php");
            echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='index.php?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if ($_POST['msg'] == "")
        {
            require_once ("../incfiles/head.php");
            echo "Вы не ввели сообщение!<br/><a href='?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $msg = check(trim($_POST['msg']));
        if ($_POST[msgtrans] == 1)
        {
            $msg = trans($msg);
        }
        $msg = mb_substr($msg, 0, 500);
        $agn = strtok($agn, ' ');
        mysql_query("insert into `download` values(0,'" . $id . "','','" . $realtime . "','','komm','" . $login . "','" . $ipp . "','" . $agn . "','" . $msg . "','');");
        if (empty($datauser[komm]))
        {
            $fpst = 1;
        } else
        {
            $fpst = $datauser[komm] + 1;
        }
        mysql_query("update `users` set  komm='" . $fpst . "' where id='" . intval($_SESSION['uid']) . "';");
        header("Location: index.php?act=komm&id=$id");
    } else
    {
        require_once ("../incfiles/head.php");
        echo "Напишите комментарий<br/><br/><form action='?act=addkomm&amp;id=" . $id . "' method='post'>
Cообщение(max. 500)<br/>
<textarea rows='3' title='Введите комментарий' name='msg' ></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' title='Поставьте флажок для транслитерации сообщения' /> Транслит<br/>
<input type='submit' title='Нажмите для отправки' name='submit' value='добавить' />  
  </form><br/>";
        echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
    }
} else
{
    require_once ("../incfiles/head.php");
    echo "Вы не авторизованы!<br/>";
}
echo '<br/><br/><a href="?act=komm&amp;id=' . $id . '">К комментариям</a><br/><a href="?act=view&amp;file=' . $id . '">К файлу</a><br/>';

?>