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
if ($_GET['id'] == "")
{
    echo "Не выбран файл<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$id = intval(check(trim($_GET['id'])));
$mess = mysql_query("select * from `download` where type='komm' and refid='" . $id . "' order by time desc ;");
$countm = mysql_num_rows($mess);
$fayl = mysql_query("select * from `download` where type='file' and id='" . $id . "';");
$fayl1 = mysql_fetch_array($fayl);
echo "Комментируем файл <font color='" . $clink . "'>$fayl1[name]</font><br/>";
if (!empty($_SESSION['uid']))
{
    echo "<a href='?act=addkomm&amp;id=" . $id . "'>Написать</a><br/>";
}
if (empty($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}
$start = $page * $kmess - $kmess;
if ($countm < $start + $kmess)
{
    $end = $countm;
} else
{
    $end = $start + $kmess;
}

while ($mass = mysql_fetch_array($mess))
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
        $uz = @mysql_query("select * from `users` where name='" . check($mass[avtor]) . "';");
        $mass1 = @mysql_fetch_array($uz);
        echo "$div";
        if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1[id]))
        {
            echo "<a href='anketa.php?user=" . $mass1[id] . "'>$mass[avtor]</a>";
        } else
        {
            echo "$mass[avtor]";
        }
        $vr = $mass[time] + $sdvig * 3600;
        $vr1 = date("d.m.Y / H:i", $vr);
        switch ($mass1[rights])
        {
            case 7:
                echo ' Adm ';
                break;
            case 6:
                echo ' Smd ';
                break;
            case 4:
                echo ' Mod ';
                break;
            case 1:
                echo ' Kil ';
                break;
        }
        $ontime = $mass1[lastdate];
        $ontime2 = $ontime + 300;
        if ($realtime > $ontime2)
        {
            echo " [Off]";
        } else
        {
            echo " [ON]";
        }
        echo "($vr1)<br/>";
        $mass[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $mass[text]);
        $mass[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $mass[text]);
        $mass[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $mass[text]);

        if (stristr($mass[text], "<a href="))
        {
            $mass[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)</a>",
                "<a href='\\1\\3'>\\3</a>", $mass[text]);
        } else
        {
            $mass[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $mass[text]);
        }
        if ($offsm != 1 && $offgr != 1)
        {
            $tekst = smiles($mass[text]);
            $tekst = smilescat($tekst);

            if ($mass[from] == nickadmina || $mass[from] == nickadmina2 || $mass1[rights] >= 1)
            {
                $tekst = smilesadm($tekst);
            }
        } else
        {
            $tekst = $mass[text];
        }
        echo "$tekst<br/>";
        if ($dostdmod == 1)
        {
            echo "$mass[ip] - $mass[soft]<br/><a href='index.php?act=delmes&amp;id=" . $mass[id] . "'>(Удалить)</a><br/>";
        }
        echo "</div>";
    }
    ++$i;
}
if ($countm > $kmess)
{
    echo "<hr/>";
    $ba = ceil($countm / $kmess);
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
        echo '<a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($offpg != 1)
    {
        if ($asd < $countm && $asd > 0)
        {
            echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=1&amp;">1</a> .. ';
        }
        $page2 = $ba - $page;
        $pa = ceil($page / 2);
        $paa = ceil($page / 3);
        $pa2 = $page + floor($page2 / 2);
        $paa2 = $page + floor($page2 / 3);
        $paa3 = $page + (floor($page2 / 3) * 2);
        if ($page > 13)
        {
            echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="?id=' . $id . '&amp;page=' . ($paa *
                2) . '">' . ($paa * 2) . '</a> <a href="?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
        } elseif ($page > 7)
        {
            echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
        }
        for ($i = $asd; $i < $asd2; )
        {
            if ($i < $countm && $i >= 0)
            {
                $ii = floor(1 + $i / $kmess);

                if ($start == $i)
                {
                    echo " <b>$ii</b>";
                } else
                {
                    echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                }
            }
            $i = $i + $kmess;
        }
        if ($page2 > 12)
        {
            echo ' .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?act=komm&amp;id=' .
                $id . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
        } elseif ($page2 > 6)
        {
            echo ' .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
        }
        if ($asd2 < $countm)
        {
            echo ' .. <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . $ba . '">' . $ba . '</a>';
        }
    } else
    {
        echo "<b>[$page]</b>";
    }
    if ($countm > $start + $kmess)
    {
        echo ' <a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
    }
    echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
        "'/><input type='hidden' name='act' value='komm'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
}
echo "<br/>Всего комментариев: $countm";
echo '<br/><a href="?act=view&amp;file=' . $id . '">К файлу</a><br/>';

?>