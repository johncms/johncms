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

if ($user_id && !$ban['1'] && !$ban['10'])
{
    if ($_GET['id'] == "")
    {
        echo "Не выбрана статья<br/><a href='?'>К категориям</a><br/>";
        require_once ('../incfiles/end.php');
        exit;
    }
    $id = intval(trim($_GET['id']));
    if (isset($_POST['submit']))
    {
        $flt = $realtime - 30;
        $af = mysql_query("select `id` from `lib` where type='komm' and time>'" . $flt . "' and avtor= '" . $login . "';");
        $af1 = mysql_num_rows($af);
        if ($af1 != 0)
        {
            echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='index.php?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
            require_once ("../incfiles/end.php");
            exit;
        }
        if ($_POST['msg'] == "")
        {
            echo "Вы не ввели сообщение!<br/><a href='index.php?act=komm&amp;id=" . $id . "'>К комментариям</a><br/>";
            require_once ('../incfiles/end.php');
            exit;
        }
        $msg = check(trim($_POST['msg']));
        if ($_POST['msgtrans'] == 1)
        {
            $msg = trans($msg);
        }
        $msg = mb_substr($msg, 0, 500);
        $agn = strtok($agn, ' ');
        mysql_query("insert into `lib` (
                refid,
                time,
                type,
                avtor,
                text,
                ip,
                soft
				) values(
				'" . $id . "',
				'" . $realtime . "',
				'komm',
				'" . $login . "',
				'" . $msg . "',
				'" . $ipl . "',
				'" . mysql_real_escape_string($agn) . "');");
        $fpst = $datauser['komm'] + 1;
        mysql_query("UPDATE `users` SET  `komm`='" . $fpst . "' WHERE `id`='" . $user_id . "';");
        echo '<p>Комментарий успешно добавлен';
    } else
    {
        echo "<p>Напишите комментарий<br/><br/><form action='?act=addkomm&amp;id=" . $id . "' method='post'>
Cообщение(max. 500)<br/>
<textarea rows='3' name='msg'></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' /> Транслит<br/>
<input type='submit' name='submit' value='добавить' />  
  </form><br/>";
        echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
    }
    echo '<a href="?act=komm&amp;id=' . $id . '">К комментариям</a></p>';
} else
{
    echo "<p>Ошибка</p>";
}

?>