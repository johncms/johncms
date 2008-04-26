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

$headmod = 'load';
$textl = 'Загрузки';
require_once ("../incfiles/core.php");
require_once ("../incfiles/mp3.php");
require_once ("../incfiles/pclzip.php");
$filesroot = "../download";
$screenroot = "$filesroot/screen";
$loadroot = "$filesroot/files";

$act = isset($_GET['act']) ? $_GET['act'] : '';
$do = array('rat', 'delmes', 'search', 'addkomm', 'komm', 'new', 'zip', 'arc', 'down', 'dfile', 'opis', 'renf', 'screen', 'ren', 'import', 'cut', 'refresh', 'upl', 'view', 'makdir', 'select', 'preview', 'delcat', 'mp3', 'trans');
if (in_array($act, $do))
{
    require_once ($act . '.php');
} else
{
    require_once ("../incfiles/head.php");
    if (empty($_GET['cat']))
    {
        echo "<p><a href='?act=new'>Новые файлы</a></p><hr />";
        $loaddir = $loadroot;
    } else
    {
        $cat = intval(trim($_GET['cat']));
        provcat($cat);
        $cat1 = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
        $adrdir = mysql_fetch_array($cat1);
        $loaddir = "$loadroot/$cat3[adres]";
        echo "<p>Категория: <b>$adrdir[text]</b></p><hr />";
    }
    $zap = mysql_query("select * from `download` where refid = '$cat' order by type asc ;");
    $total = mysql_num_rows($zap);
    $zapcat = mysql_query("select * from `download` where refid = '$cat' and type='cat' ;");
    $totalcat = mysql_num_rows($zapcat);
    $zapfile = mysql_query("select * from `download` where refid = '$cat' and type='file' ;");
    $totalfile = mysql_num_rows($zapfile);
    if (empty($_GET['page']))
    {
        $page = 1;
    } else
    {
        $page = intval($_GET['page']);
    }
    $start = $page * 10 - 10;
    if ($total < $start + 10)
    {
        $end = $total;
    } else
    {
        $end = $start + 10;
    }
    if ($total != 0)
    {
        while ($zap2 = mysql_fetch_array($zap))
        {
            if ($i >= $start && $i < $end)
            {
                switch ($zap2['type'])
                {
                    case "cat":
                        echo '<div class="menu"><img alt="" src="../images/arrow.gif" width="7" height="12" />&nbsp;<a href="?cat=' . $zap2[id] . '">' . $zap2[text] . '</a>';
                        $g = 0;
                        $g1 = 0;
                        $kf = mysql_query("select * from `download` where type='file' ;");
                        while ($kf1 = mysql_fetch_array($kf))
                        {
                            if (stristr($kf1['adres'], "$zap2[adres]/$zap2[name]"))
                            {
                                $g = $g + 1;
                            }
                        }
                        // Считаем новые файлы
                        $old = $realtime - (3 * 24 * 3600);
                        $req = mysql_query("select * from `download` where `refid`='" . $zap2['id'] . "' and `type`='file' and `time`>'" . $old . "';");
                        $g1 = mysql_num_rows($req);
                        mysql_free_result($req);
                        echo "($g";
                        if ($g1 != 0)
                        {
                            echo "/+$g1)</div>";
                        } else
                        {
                            echo ")</div>";
                        }
                        break;

                    case "file":
                        $ft = format($zap2[name]);
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
                        if ($zap2[text] != "")
                        {
                            $tx = $zap2[text];
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
                        echo "<img src='" . $filesroot . "/img/" . $imt . "' alt=''/><a href='?act=view&amp;file=" . $zap2[id] . "'>$zap2[name]</a>$tx<br/>";
                        break;
                }
            }
            ++$i;
        }
        if ($total > 10)
        {
            echo "<hr/>";
            $ba = ceil($total / 10);
            if ($offpg != 1)
            {
                echo "Страницы:<br/>";
            } else
            {
                echo "Страниц: $ba<br/>";
            }

            if ($start != 0)
            {
                echo '<a href="index.php?cat=' . $cat . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
            }
            $asd = $start - 10;
            $asd2 = $start + 20;
            if ($offpg != 1)
            {
                if ($asd < $total && $asd > 0)
                {
                    echo ' <a href="index.php?cat=' . $cat . '&amp;page=1">1</a> .. ';
                }
                $page2 = $ba - $page;
                $pa = ceil($page / 2);
                $paa = ceil($page / 3);
                $pa2 = $page + floor($page2 / 2);
                $paa2 = $page + floor($page2 / 3);
                $paa3 = $page + (floor($page2 / 3) * 2);
                if ($page > 13)
                {
                    echo ' <a href="index.php?cat=' . $cat . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?cat=' . $cat . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?cat=' . $cat . '&amp;page=' . ($paa * 2) . '">' . ($paa *
                        2) . '</a> <a href="index.php?cat=' . $cat . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                } elseif ($page > 7)
                {
                    echo ' <a href="index.php?cat=' . $cat . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?cat=' . $cat . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                }
                for ($i = $asd; $i < $asd2; )
                {
                    if ($i < $total && $i >= 0)
                    {
                        $ii = floor(1 + $i / 10);

                        if ($start == $i)
                        {
                            echo " <b>$ii</b>";
                        } else
                        {
                            echo ' <a href="index.php?cat=' . $cat . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                        }
                    }
                    $i = $i + 10;
                }
                if ($page2 > 12)
                {
                    echo ' .. <a href="index.php?cat=' . $cat . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?cat=' . $cat . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?cat=' . $cat . '&amp;page=' . ($paa3) .
                        '">' . ($paa3) . '</a> <a href="index.php?cat=' . $cat . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                } elseif ($page2 > 6)
                {
                    echo ' .. <a href="index.php?cat=' . $cat . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?cat=' . $cat . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                }
                if ($asd2 < $totalnew)
                {
                    echo ' .. <a href="index.php?cat=' . $cat . '&amp;page=' . $ba . '">' . $ba . '</a>';
                }
            } else
            {
                echo "<b>[$page]</b>";
            }
            if ($total > $start + 10)
            {
                echo ' <a href="index.php?cat=' . $cat . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
            }
            echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='cat' value='" . $cat .
                "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
        }
        if ($totalcat >= 1)
        {
            echo '<br/>Всего папок: ' . $totalcat . '<br/>';
        }
        if ($totalfile >= 1)
        {
            echo '<br/>Всего файлов: ' . $totalfile . '<br/>';
        }
    } else
    {
        echo 'В данной категории нет файлов!<br/>';
    }
    if ($dostdmod == 1)
    {
        echo "<hr/><a href='?act=makdir&amp;cat=" . $cat . "'>Создать папку</a><br/>";
    }
    if (($dostdmod == 1) && (!empty($_GET['cat'])))
    {
        $delcat = mysql_query("select * from `download` where type = 'cat' and refid = '" . $cat . "';");
        $delcat1 = mysql_num_rows($delcat);
        if ($delcat1 == 0)
        {
            echo "<a href='?act=delcat&amp;cat=" . $cat . "'>Удалить каталог</a><br/>";
        }
        echo "<a href='?act=ren&amp;cat=" . $cat . "'>Переименовать каталог</a><br/>";
        echo "<a href='?act=select&amp;cat=" . $cat . "'>Выгрузить файл</a><br/>";
        echo "<a href='?act=import&amp;cat=" . $cat . "'>Импорт файла</a><br/>";
    }
    if ($dostdmod == 1)
    {
        echo "<a href='?act=refresh'>Обновить</a><hr/>";
    }
    if (!empty($cat))
    {
        $dnam = mysql_query("select * from `download` where type = 'cat' and id = '" . $cat . "';");
        $dnam1 = mysql_fetch_array($dnam);
        $dnam2 = mysql_query("select * from `download` where type = 'cat' and id = '" . $dnam1[refid] . "';");
        $dnam3 = mysql_fetch_array($dnam2);
        $dirname = "$dnam3[text]";
        $dirid = "$dnam1[id]";
        $nadir = $dnam1[refid];
        while ($nadir != "" && $nadir != "0")
        {
            echo "&#187;<a href='?cat=" . $nadir . "'>$dirname</a><br/>";
            $dnamm = mysql_query("select * from `download` where type = 'cat' and id = '" . $nadir . "';");
            $dnamm1 = mysql_fetch_array($dnamm);
            $dnamm2 = mysql_query("select * from `download` where type = 'cat' and id = '" . $dnamm1[refid] . "';");
            $dnamm3 = mysql_fetch_array($dnamm2);
            $nadir = $dnamm1[refid];
            $dirname = $dnamm3[text];
        }
        echo "&#187;<a href='?'>В загрузки</a><br/>";
    }
    echo "<a href='?act=preview'>Размеры изображений</a><br/>";
    if (empty($cat))
    {
        echo "<form action='?act=search' method='post'>";
        echo "Поиск файла: <br/><input type='text' name='srh' size='20' maxlength='20' title='Введите запрос' value=''/><br/>";

        echo "<input type='submit' title='Нажмите для поиска' value='Найти!'/></form><br/>";
    }
}

require_once ('../incfiles/end.php');

?>