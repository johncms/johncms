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

define('_IN_JOHNCMS', 1);

$textl = 'Подтверждение регистрации';
require_once ("../incfiles/core.php");

if ($dostadm == 1)
{
    require_once ("../incfiles/head.php");
    $act = isset($_GET['act']) ? $_GET['act'] : '';
    switch ($act)
    {
        case 'prin':
            $pr = 1;
            $adminreg = $login;
            if (@mysql_query("update `users` set  preg='" . $pr . "', regadm='" . $adminreg . "'  where id='" . check(intval($_GET['user'])) . "';"))
                echo "<div>Регистрация подтверждена.<br/><a href='?'>Вернуться</a></div>";
            break;

        case 'otkl':
            $pr = 0;
            $adminreg = $login;
            if (@mysql_query("update `users` set  preg='" . $pr . "', regadm='" . $adminreg . "'  where id='" . check(intval($_GET['user'])) . "';"))
                echo "<div>Регистрация отклонена.<br/><a href='?'>Вернуться</a></div>";
            break;

        default:
            $page = $_GET['page'];
            if ($page <= 0)
            {
                $page = 1;
            }
            $reg = mysql_query("select * from `users` where `preg`='0';");
            $reg2 = mysql_num_rows($reg);
            echo '<div class="phdr">На регистрации [' . $reg2 . ']</div>';
            $i = 1;
            while ($reg1 = mysql_fetch_array($reg))
            {
                if ($i <= $page * 10 & $i >= ($page - 1) * 10)
                {
                    echo '<div class="menu"><a href="../str/anketa.php?user=' . $reg1['id'] . '">' . $reg1['name'] . '</a>   [<a href="?act=prin&amp;user=' . $reg1['id'] . '">Принять</a>|<a href="?act=otkl&amp;user=' . $reg1['id'] . '">Отклонить</a>]';
                    if ($reg1['regadm'] !== "")
                    {
                        print '<br />Отклонил <b>' . $reg1['regadm'] . '</b>';
                    }
                    $agent = strtok($reg1['browser'], ' ');
					echo '<div class="sub"><u>UA</u>:&nbsp;' . $agent . '<br /><u>IP</u>:&nbsp;' . long2ip($reg1['ip']) . '</div>';
                    echo '</div>';
                }
                ++$i;
            }
            if ($reg2 > 10 and $reg2 > 10 * ($page))
            {
                $next = $page + 1;
                print "<div><a href='preg.php?page=" . $next . "'>Вперёд</a></div>";
            }
            $prev = $page - 1;
            if ($prev != 0)
            {
                print "<div><a href='preg.php?page=" . $prev . "'>Назад</a></div>";
            }
            echo '<div class="bmenu"><a href="main.php">В админку</a></div>';
    }
    require_once ("../incfiles/end.php");
} else
{
    header("Location: ../index.php?mod=404");
}

?>