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

$headmod = 'users';
$textl = 'Юзеры';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
echo '<div class="phdr">Список пользователей</div>';
$q = mysql_query("select `id`, `name`, `sex`, `lastdate` from `users`;");
$count = mysql_num_rows($q);
if (empty($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}
$start = $page * 10 - 10;
if ($count < $start + 10)
{
    $end = $count;
} else
{
    $end = $start + 10;
}
while ($arr = mysql_fetch_array($q))
{
    if ($i >= $start && $i < $end)
    {
        if ($arr['sex'] == "m")
        {
            echo '<img src="../images/m.gif" alt=""/>&nbsp;';
        } elseif ($arr['sex'] == "zh")
        {
            echo '<img src="../images/f.gif" alt=""/>&nbsp;';
        }
        if (empty($_SESSION['uid']) || $_SESSION['uid'] == $arr['id'])
        {
            print "<b>$arr[name]</b>";
        } else
        {
            print "<a href='anketa.php?user=" . $arr['id'] . "'>$arr[name]</a>";
        }
        switch ($arr['rights'])
        {
            case 7:
                echo ' Adm ';
                break;
            case 6:
                echo ' Smd ';
                break;
            case 5:
                echo ' Mod ';
                break;
            case 4:
                echo ' Mod ';
                break;
            case 3:
                echo ' Mod ';
                break;
            case 2:
                echo ' Mod ';
                break;
            case 1:
                echo ' Kil ';
                break;
        }
        $ontime = $arr['lastdate'];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo '<font color="#FF0000"> [Off]</font><br/>';
        } else
        {
            echo '<font color="#00AA00"> [ON]</font><br/>';
        }
    }
    ++$i;
}
    echo "<hr/><p>";
if ($count > 10)
{
    $ba = ceil($count / 10);
    if ($offpg != 1)
    {
        echo "Страницы:<br/>";
    } else
    {
        echo "Страниц: $ba<br/>";
    }
    $asd = $start - (10);
    $asd2 = $start + (10 * 2);

    if ($start != 0)
    {
        echo '<a href="users.php?page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($offpg != 1)
    {
        if ($asd < $count && $asd > 0)
        {
            echo ' <a href="users.php?page=1&amp;">1</a> .. ';
        }
        $page2 = $ba - $page;
        $pa = ceil($page / 2);
        $paa = ceil($page / 3);
        $pa2 = $page + floor($page2 / 2);
        $paa2 = $page + floor($page2 / 3);
        $paa3 = $page + (floor($page2 / 3) * 2);
        if ($page > 13)
        {
            echo ' <a href="pusers.php?page=' . $paa . '">' . $paa . '</a> <a href="users.php?page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="users.php?page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="users.php?page=' . ($paa * 2 +
                1) . '">' . ($paa * 2 + 1) . '</a> .. ';
        } elseif ($page > 7)
        {
            echo ' <a href="users.php?page=' . $pa . '">' . $pa . '</a> <a href="users.php?page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
        }
        for ($i = $asd; $i < $asd2; )
        {
            if ($i < $count && $i >= 0)
            {
                $ii = floor(1 + $i / 10);

                if ($start == $i)
                {
                    echo " <b>$ii</b>";
                } else
                {
                    echo ' <a href="users.php?page=' . $ii . '">' . $ii . '</a> ';
                }
            }
            $i = $i + 10;
        }
        if ($page2 > 12)
        {
            echo ' .. <a href="users.php?page=' . $paa2 . '">' . $paa2 . '</a> <a href="users.php?page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="users.php?page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="users.php?page=' . ($paa3 + 1) .
                '">' . ($paa3 + 1) . '</a> ';
        } elseif ($page2 > 6)
        {
            echo ' .. <a href="users.php?page=' . $pa2 . '">' . $pa2 . '</a> <a href="users.php?page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
        }
        if ($asd2 < $count)
        {
            echo ' .. <a href="users.php?page=' . $ba . '">' . $ba . '</a>';
        }
    } else
    {
        echo "<b>[$page]</b>";
    }


    if ($count > $start + 10)
    {
        echo ' <a href="users.php?page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='users.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}
echo "<div>Всего: $count</div>";
echo '<a href="' . $_SESSION['refsm'] . '">Назад</a></p>';

require_once ("../incfiles/end.php");

?>