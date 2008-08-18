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

define('_IN_JOHNCMS', 1);

$textl = 'Новости ресурса';
$headmod = "news";
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

echo '<div class="phdr">Новости ресурса</div>';
$nw = mysql_query("select * from `news` order by `time` desc;");
if (!empty($_GET['kv']))
{
    $count = intval(check($_GET['kv']));
} else
{
    $count = mysql_num_rows($nw);
}
if (empty($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}
$start = $page * $kmess - $kmess;
if ($count < $start + $kmess)
{
    $end = $count;
} else
{
    $end = $start + $kmess;
}
while ($nw1 = mysql_fetch_array($nw))
{
    if ($i >= $start && $i < $end)
    {
        $d = $i / 2;
        $d1 = ceil($d);
        $d2 = $d1 - $d;
        $d3 = ceil($d2);
        if ($d3 == 0)
        {
            $div = "<div class='c'>";
        } else
        {
            $div = "<div class='b'>";
        }
        $text = $nw1['text'];
        $text = tags($text);
        if ($offsm != 1 && $offgr != 1)
        {
            $text = smiles($text);
            $text = smilescat($text);
            $text = smilesadm($text);
        }
        $vr = $nw1['time'] + $sdvig * 3600;
        $vr1 = date("d.m.y / H:i", $vr);
        echo $div . '<b>' . $nw1['name'] . '</b><br/>' . $text . '<br/><font color="#999999">Добавил: ' . $nw1['avt'] . ' (' . $vr1 . ')</font><br/>';
        if ($nw1['kom'] != 0 && $nw1['kom'] != "")
        {
            $mes = mysql_query("select * from `forum` where type='m' and refid= '" . $nw1['kom'] . "';");
            $komm = mysql_num_rows($mes) - 1;
            echo '<a href="../forum/?id=' . $nw1['kom'] . '">Обсудить на форуме (' . $komm . ')</a><br/>';
        }
        echo "</div>";
    }
    ++$i;
}
echo "<hr/><p>";
if ($count > $kmess)
{
    $ba = ceil($count / $kmess);
    if ($offpg != 1)
    {
        echo "Страницы:<br/>";
    } else
    {
        echo "Страниц: $ba<br/>";
    }
    $asd = $start - ($kmess);
    $asd2 = $start + ($kmess * 2);

    if ($start != 0)
    {
        echo '<a href="news.php?page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($offpg != 1)
    {
        if ($asd < $count && $asd > 0)
        {
            echo ' <a href="news.php?page=1&amp;">1</a> .. ';
        }
        $page2 = $ba - $page;
        $pa = ceil($page / 2);
        $paa = ceil($page / 3);
        $pa2 = $page + floor($page2 / 2);
        $paa2 = $page + floor($page2 / 3);
        $paa3 = $page + (floor($page2 / 3) * 2);
        if ($page > 13)
        {
            echo ' <a href="pnews.php?page=' . $paa . '">' . $paa . '</a> <a href="news.php?page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="news.php?page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="news.php?page=' . ($paa * 2 + 1) .
                '">' . ($paa * 2 + 1) . '</a> .. ';
        } elseif ($page > 7)
        {
            echo ' <a href="news.php?page=' . $pa . '">' . $pa . '</a> <a href="news.php?page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
        }
        for ($i = $asd; $i < $asd2; )
        {
            if ($i < $count && $i >= 0)
            {
                $ii = floor(1 + $i / $kmess);

                if ($start == $i)
                {
                    echo " <b>$ii</b>";
                } else
                {
                    echo ' <a href="news.php?page=' . $ii . '">' . $ii . '</a> ';
                }
            }
            $i = $i + $kmess;
        }
        if ($page2 > 12)
        {
            echo ' .. <a href="news.php?page=' . $paa2 . '">' . $paa2 . '</a> <a href="news.php?page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="news.php?page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="news.php?page=' . ($paa3 + 1) .
                '">' . ($paa3 + 1) . '</a> ';
        } elseif ($page2 > 6)
        {
            echo ' .. <a href="news.php?page=' . $pa2 . '">' . $pa2 . '</a> <a href="news.php?page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
        }
        if ($asd2 < $count)
        {
            echo ' .. <a href="news.php?page=' . $ba . '">' . $ba . '</a>';
        }
    } else
    {
        echo "<b>[$page]</b>";
    }
    if ($count > $start + $kmess)
    {
        echo ' <a href="news.php?page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='news.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}
if (!empty($_GET['kv']))
{
    echo "Новых: $count<br/><a href='news.php'>Все новости</a><br/>";
} else
{
    echo "Всего: $count<br/>";
}
echo '</p>';
require_once ("../incfiles/end.php");
?>