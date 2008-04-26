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
echo "Новые файлы<br/>";
$old = $realtime - (3 * 24 * 3600);

$newfile = mysql_query("select * from `download` where time > '" . $old . "' and type='file' order by time desc;");
$totalnew = mysql_num_rows($newfile);
if (empty($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}
$start = $page * 10 - 10;
if ($totalnew < $start + 10)
{
    $end = $totalnew;
} else
{
    $end = $start + 10;
}

if ($totalnew != 0)
{
    while ($newf = mysql_fetch_array($newfile))
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
            $fsz = filesize("$newf[adres]/$newf[name]");
            $fsz = round($fsz / 1024, 2);
            $ft = format("$newf[adres]/$newf[name]");
            switch ($ft)
            {
                case "mp3":
                    $imt = "mp3.png";
                    break;
                case "zip":
                    $imt = "rar.png";
                    break;
                case "jar":
                    $imt = "jar.png";
                    break;
                case "gif":
                    $imt = "gif.png";
                    break;
                case "jpg":
                    $imt = "jpg.png";
                    break;
                case "png":
                    $imt = "png.png";
                    break;
                default:
                    $imt = "file.gif";
                    break;
            }
            if ($newf[text] != "")
            {
                $tx = $newf[text];
                if (mb_strlen($tx) > 100)
                {
                    $tx = mb_substr($tx, 0, 90);

                    $tx = "<br/>$tx...";
                } else
                {
                    $tx = "<br/>$tx";
                }
            } else
            {
                $tx = "";
            }
            echo "$div<img src='" . $filesroot . "/img/" . $imt . "' alt=''/><a href='?act=view&amp;file=" . $newf[id] . "'>$newf[name]</a> ($fsz кб)$tx <br/>";
            $nadir = $newf[refid];
            $pat = "";
            while ($nadir != "")
            {
                $dnew = mysql_query("select * from `download` where type = 'cat' and id = '" . $nadir . "';");
                $dnew1 = mysql_fetch_array($dnew);
                $pat = "$dnew1[text]/$pat";
                $nadir = $dnew1[refid];
            }
            $l = mb_strlen($pat);
            $pat1 = mb_substr($pat, 0, $l - 1);
            echo "[$pat1]</div>";
        }
        ++$i;
    }
    if ($totalnew > 10)
    {
        echo "<hr/>";


        $ba = ceil($totalnew / 10);
        if ($offpg != 1)
        {
            echo "Страницы:<br/>";
        } else
        {
            echo "Страниц: $ba<br/>";
        }
        if ($start != 0)
        {
            echo '<a href="index.php?act=new&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
        }
        $asd = $start - 10;
        $asd2 = $start + 20;
        if ($offpg != 1)
        {
            if ($asd < $totalnew && $asd > 0)
            {
                echo ' <a href="index.php?act=new&amp;page=1">1</a> .. ';
            }
            $page2 = $ba - $page;
            $pa = ceil($page / 2);
            $paa = ceil($page / 3);
            $pa2 = $page + floor($page2 / 2);
            $paa2 = $page + floor($page2 / 3);
            $paa3 = $page + (floor($page2 / 3) * 2);
            if ($page > 13)
            {
                echo ' <a href="index.php?act=new&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=new&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?act=new&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                    '</a> <a href="index.php?act=new&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
            } elseif ($page > 7)
            {
                echo ' <a href="index.php?act=new&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=new&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
            }
            for ($i = $asd; $i < $asd2; )
            {
                if ($i < $totalnew && $i >= 0)
                {
                    $ii = floor(1 + $i / 10);

                    if ($start == $i)
                    {
                        echo " <b>$ii</b>";
                    } else
                    {
                        echo ' <a href="index.php?act=new&amp;page=' . $ii . '">' . $ii . '</a> ';
                    }
                }
                $i = $i + 10;
            }
            if ($page2 > 12)
            {
                echo ' .. <a href="index.php?act=new&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=new&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?act=new&amp;page=' . ($paa3) . '">' . ($paa3) .
                    '</a> <a href="index.php?act=new&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
            } elseif ($page2 > 6)
            {
                echo ' .. <a href="index.php?act=new&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?act=new&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
            }
            if ($asd2 < $totalnew)
            {
                echo ' .. <a href="index.php?act=new&amp;page=' . $ba . '">' . $ba . '</a>';
            }
        } else
        {
            echo "<b>[$page]</b>";
        }
        if ($totalnew > $start + 10)
        {
            echo ' <a href="index.php?act=new&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
        }
        echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='act' value='new'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
    }

    #####

    if ($totalnew >= 1)
    {
        echo '<br/>Всего новых файлов за 3 дня: ' . $totalnew . '<br/>';
    }
} else
{
    echo "За три дня новых файлов не было<br/>";
}
echo "<br/><a href='index.php?'>К категориям</a><br/>";

?>