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
    echo "Не выбрана статья<br/><a href='?'>К категориям</a><br/>";
    require_once ('../incfiles/end.php');
    exit;
}
$id = intval($_GET['id']);
$messz = mysql_query("select `id` from `lib` where type='komm' and refid='" . $id . "'  ;");
$countm = mysql_num_rows($messz);
$ba = ceil($countm / $kmess);
$fayl = mysql_query("select `name` from `lib` where type='bk' and id='" . $id . "';");
$fayl1 = mysql_fetch_array($fayl);
echo "<p>Комментируем статью:<br /><b>$fayl1[name]</b><br/>";
if ($user_id && !$ban['1'] && !$ban['10'])
{
    echo "</p><p><a href='index.php?act=addkomm&amp;id=" . $id . "'>Написать</a>";
}
echo '</p><hr/>';
if (empty($_GET['page']))
{
    $page = 1;
} else
{
    $page = intval($_GET['page']);
}
if ($page < 1)
{
    $page = 1;
}
if ($page > $ba)
{
    $page = $ba;
}
$start = $page * $kmess - $kmess;
if ($countm < $start + $kmess)
{
    $end = $countm;
} else
{
    $end = $start + $kmess;
}
$mess = mysql_query("select * from `lib` where type='komm' and refid='" . $id . "' order by time desc LIMIT " . $start . "," . $end . ";");
while ($mass = mysql_fetch_array($mess))
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
    $uz = @mysql_query("select * from `users` where name='" . check($mass['avtor']) . "';");
    $mass1 = @mysql_fetch_array($uz);
    echo "$div";
    if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1['id']))
    {
        echo "<a href='anketa.php?user=" . $mass1['id'] . "'>$mass[avtor]</a>";
    } else
    {
        echo "$mass[avtor]";
    }
    $vr = $mass['time'] + $sdvig * 3600;
    $vr1 = date("d.m.Y / H:i", $vr);
    switch ($mass1['rights'])
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
        case 1:
            echo ' Kil ';
            break;
    }
    $ontime = $mass1['lastdate'];
    $ontime2 = $ontime + 300;
    if ($realtime > $ontime2)
    {
        echo '<font color="#FF0000"> [Off]</font>';
    } else
    {
        echo '<font color="#00AA00"> [ON]</font>';
    }
    echo "($vr1)<br/>";
    if ($offsm != 1 && $offgr != 1)
    {
        $tekst = smiles($mass['text']);
        $tekst = smilescat($tekst);
        if ($mass['from'] == nickadmina || $mass['from'] == nickadmina2 || $mass1['rights'] >= 1)
        {
            $tekst = smilesadm($tekst);
        }
    } else
    {
        $tekst = $mass['text'];
    }
    echo "$tekst<br/>";
    if ($dostlmod == 1)
    {
        echo long2ip($mass['ip']) . " - $mass[soft]<br/><a href='index.php?act=del&amp;id=" . $mass['id'] . "'>(Удалить)</a>";
    }
    echo "</div>";
    ++$i;
}
echo "<hr/><p>";
if ($countm > $kmess)
{
    if ($offpg != 1)
    {
        echo "Страницы:<br/>";
    } else
    {
        echo "Страниц: $ba<br/>";
    }
    if ($start != 0)
    {
        echo '<a href="index.php?act=komm&amp;id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
    }
    if ($offpg != 1)
    {
        navigate('index.php?act=komm&amp;id=' . $id . '', $countm, $kmess, $start, $page);
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
echo "Всего комментариев: $countm";
echo '<br/><a href="?id=' . $id . '">К статье</a></p>';

?>