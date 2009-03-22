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

$headmod = 'lib';
$textl = 'Библиотека';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

if (!$set['mod_lib'] && $dostadm != 1)
{
    echo '<p>' . $set['mod_lib_msg'] . '</p>';
    require_once ("../incfiles/end.php");
    exit;
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$do = array('java', 'symb', 'search', 'new', 'moder', 'addkomm', 'komm', 'del', 'edit', 'load', 'write', 'mkcat', 'topread', 'trans');
if (in_array($act, $do))
{
    require_once ($act . '.php');
} else
{
    if (!$set['mod_lib'])
        echo '<p><font color="#FF0000"><b>Библиотека закрыта!</b></font></p>';
    if (empty($_GET['id']))
    {
        echo '<div class="phdr"><b>Библиотека</b></div>';
        if ($dostlmod == 1)
        {
            // Считаем число статей, ожидающих модерацию
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = '0';");
            $res = mysql_result($req, 0);
            if ($res > 0)
                echo '<div class="rmenu">Модерации ожидают <a href="index.php?act=moder">' . $res . '</a> статей</div>';
        }
        $old = $realtime - (3 * 24 * 3600); // Сколько суток считать статьи новыми?
        $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `time` > '" . $old . "' AND `type`='bk' AND `moder`='1'");
        $res = mysql_result($req, 0);
        echo '<div class="gmenu"><p>';
        if ($res > 0)
            echo '<a href="index.php?act=new">Новые статьи</a> (' . $res . ')<br/>';
        echo '<a href="index.php?act=topread">Самые читаемые</a></p></div>';
        $id = 0;
        $tip = "cat";
    } else
    {
        $tp = mysql_query("SELECT * FROM `lib` WHERE `id` = '" . $id . "' LIMIT 1;");
        $tp1 = mysql_fetch_array($tp);
        $tip = $tp1['type'];
        if ($tp1['type'] == "cat")
        {
            echo '<div class="phdr"><b>' . $tp1['text'] . '</b></div>';
        }
    }
    switch ($tip)
    {
        case 'cat':
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'cat' AND `refid` = '" . $id . "'");
            $totalcat = mysql_result($req, 0);
            $bkz = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `refid` = '" . $id . "' AND `moder`='1'");
            $totalbk = mysql_result($bkz, 0);
            if ($totalcat > 0)
            {
                $total = $totalcat;
                $req = mysql_query("SELECT `id`, `text`  FROM `lib` WHERE `type` = 'cat' AND `refid` = '" . $id . "' LIMIT " . $start . "," . $kmess);
                while ($cat1 = mysql_fetch_array($req))
                {
                    $cat2 = mysql_query("select `id` from `lib` where type = 'cat' and refid = '" . $cat1['id'] . "';");
                    $totalcat2 = mysql_num_rows($cat2);
                    $bk2 = mysql_query("select `id` from `lib` where type = 'bk' and refid = '" . $cat1['id'] . "' and moder='1';");
                    $totalbk2 = mysql_num_rows($bk2);
                    if ($totalcat2 != 0)
                    {
                        $kol = "$totalcat2 кат.";
                    } elseif ($totalbk2 != 0)
                    {
                        $kol = "$totalbk2 ст.";
                    } else
                    {
                        $kol = "0";
                    }
                    echo '<div class="menu"><a href="index.php?id=' . $cat1['id'] . '">' . $cat1['text'] . '</a>(' . $kol . ')</div>';
                    ++$i;
                }
                echo '<div class="bmenu">Всего категорий: ' . $totalcat . '</div>';
            } elseif ($totalbk > 0)
            {
                $total = $totalbk;
                $ba = ceil($total / 10);
                if ($page > $ba)
                {
                    $page = $ba;
                }
                $start = $page * 10 - 10;
                if ($total < $start + 10)
                {
                    $end = $total;
                } else
                {
                    $end = $start + 10;
                }
                $bk = mysql_query("select * from `lib` where type = 'bk' and refid = '" . $id . "' and moder='1' order by time desc LIMIT " . $start . "," . $end . ";");
                while ($bk1 = mysql_fetch_array($bk))
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
                    $vr = $bk1[time] + $sdvig * 3600;
                    $vr = date("d.m.y / H:i", $vr);
                    echo $div . '<b><a href="index.php?id=' . $bk1['id'] . '">' . htmlentities($bk1['name'], ENT_QUOTES, 'UTF-8') . '</a></b><br/>';
                    echo htmlentities($bk1['announce'], ENT_QUOTES, 'UTF-8') . '<br />';
                    echo 'Добавил: ' . $bk1['avtor'] . ' (' . $vr . ')<br />';
                    echo 'Прочтений: ' . $bk1['count'] . '</div>';
                    ++$i;
                }
            } else
            {
                $total = 0;
            }
            echo '<p>';
            if ($total > 10)
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
                    echo '<a href="index.php?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                if ($offpg != 1)
                {
                    navigate('index.php?id=' . $id . '', $total, 10, $start, $page);
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($total > $start + 10)
                {
                    echo ' <a href="index.php?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                    "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
            if ($total != 0)
            {
                if ($totalcat >= 1)
                {
                    echo 'Всего категорий: ' . $totalcat . '<br/>';
                } elseif ($totalbk >= 1)
                {
                    echo 'Всего статей: ' . $totalbk . '<br/>';
                }
            } else
            {
                echo 'В данной категории нет статей!<br/>';
            }
            if ($dostlmod == 1 && $id != 0)
            {
                $ct = mysql_query("select `id` from `lib` where type='cat' and refid='" . $id . "';");
                $ct1 = mysql_num_rows($ct);
                if ($ct1 == 0)
                {
                    echo "<a href='index.php?act=del&amp;id=" . $id . "'>Удалить категорию</a><br/>";
                }
                echo "<a href='index.php?act=edit&amp;id=" . $id . "'>Изменить категорию</a><br/>";
            }
            if ($dostlmod == 1 && ($tp1['ip'] == 1 || $id == 0))
            {
                echo "<a href='index.php?act=mkcat&amp;id=" . $id . "'>Создать категорию</a><br/>";
            }
            if ($tp1['ip'] == 0 && $id != 0)
            {
                if ($dostlmod == 1 || ($tp1['soft'] == 1 && !empty($_SESSION['uid'])))
                {
                    echo "<a href='index.php?act=write&amp;id=" . $id . "'>Написать статью</a><br/>";
                }
                if ($dostlmod == 1)
                {
                    echo "<a href='index.php?act=load&amp;id=" . $id . "'>Выгрузить статью</a><br/>";
                }
            }
            if ($id != 0)
            {
                $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $id . "';");
                $dnam1 = mysql_fetch_array($dnam);
                $dnam2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnam1['refid'] . "';");
                $dnam3 = mysql_fetch_array($dnam2);
                $catname = "$dnam3[text]";
                $dirid = "$dnam1[id]";

                $nadir = $dnam1[refid];
                while ($nadir != "0")
                {
                    echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                    $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                    $dnamm1 = mysql_fetch_array($dnamm);
                    $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1['refid'] . "';");
                    $dnamm3 = mysql_fetch_array($dnamm2);
                    $nadir = $dnamm1['refid'];
                    $catname = $dnamm3['text'];
                }
                echo "&#187;<a href='index.php?'>В библиотеку</a><br/>";
            } else
            {
                echo "<a href='index.php?act=symb'>Настройки</a><br/>";
                echo "<form action='?act=search' method='post'>";
                echo "Поиск статьи: <br/><input type='text' name='srh' value=''/><br/>Метод поиска:<br/><select name='mod'><option value='1'>По названию</option><option value='2'>По тексту</option></select><br/>";
                echo "<input type='submit' value='Найти!'/></form><br/>";
            }
            echo '</p>';
            break;

        case 'bk':
            ////////////////////////////////////////////////////////////
            // Читаем статью                                          //
            ////////////////////////////////////////////////////////////
            if (!empty($_SESSION['symb']))
            {
                $simvol = $_SESSION['symb'];
            } else
            {
                $simvol = 2000; // Число символов на страницу по умолчанию
            }
            // Счетчик прочтений
            if ($_SESSION['lib'] != $id)
            {
                $_SESSION['lib'] = $id;
                $libcount = intval($tp1[count]) + 1;
                mysql_query("update `lib` set  `count`='" . $libcount . "' where id='" . $id . "';");
            }

            // Заголовок статьи
            echo '<p><b>' . htmlentities($tp1['name'], ENT_QUOTES, 'UTF-8') . '</b></p>';
            $tx = $tp1['text'];

            # для постраничного вывода используется модифицированный код от hintoz #
            $strrpos = mb_strrpos($tx, " ");
            $pages = 1;
            $t_si = 0;
            if ($strrpos)
            {
                while ($t_si < $strrpos)
                {
                    $string = mb_substr($tx, $t_si, $simvol);
                    $t_ki = mb_strrpos($string, " ");
                    $m_sim = $t_ki;
                    $strings[$pages] = $string;
                    $t_si = $t_ki + $t_si;
                    if ($page == $pages)
                    {
                        $page_text = $strings[$pages];
                    }
                    if ($strings[$pages] == "")
                    {
                        $t_si = $strrpos++;
                    } else
                    {
                        $pages++;
                    }
                }
                if ($page >= $pages)
                {
                    $page = $pages - 1;
                    $page_text = $strings[$page];
                }
                $pages = $pages - 1;
                if ($page != $pages)
                {
                    $prb = mb_strrpos($page_text, " ");
                    $page_text = mb_substr($page_text, 0, $prb);
                }
            } else
            {
                $page_text = $tx;
            }
            // Текст статьи
            $page_text = htmlentities($page_text, ENT_QUOTES, 'UTF-8');
            $page_text = str_replace("\r\n", "<br />", $page_text);
            echo $page_text;
            echo '<hr /><p>';
            $next = $page + 1;
            $prev = $page - 1;
            if ($pages > 1)
            {
                if ($offpg != 1)
                {
                    echo "Страницы:<br/>";
                } else
                {
                    echo "Страниц: $pages<br/>";
                }
                if ($page > 1)
                {
                    print " <a href=\"index.php?id=$id&amp;page=$prev\">&lt;&lt;</a> ";
                }
                if ($offpg != 1)
                {
                    if ($page > 1)
                    {
                        print "<a href=\"index.php?id=$id&amp;page=1\">1</a> ";
                    }
                    if ($prev > 2)
                    {
                        print " .. ";
                    }
                    $page2 = $pages - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="index.php?id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="index.php?id=' . $id . '&amp;page=' . ($paa * 2) . '">' . ($paa *
                            2) . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="index.php?id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                    }
                    if ($prev > 1)
                    {
                        print "<a href=\"index.php?id=$id&amp;page=$prev\">$prev</a> ";
                    }
                    echo "<b>$page</b> ";
                    if ($next < $pages)
                    {
                        print "<a href=\"index.php?id=$id&amp;page=$next\">$next</a> ";
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="index.php?id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="index.php?id=' . $id . '&amp;page=' . ($paa3) . '">' . ($paa3) .
                            '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="index.php?id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($next < ($pages - 1))
                    {
                        print " .. ";
                    }
                    if ($page < $pages)
                    {
                        print "<a href=\"index.php?id=$id&amp;page=$pages\">$pages</a> ";
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }
                if ($page < $pages)
                {
                    print " <a href=\"index.php?id=$id&amp;page=$next\">&gt;&gt;</a>";
                }
                echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                    "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }
            if ($dostlmod == 1)
            {
                echo "<a href='index.php?act=del&amp;id=" . $id . "'>Удалить статью</a><br/>";
                echo "<a href='index.php?act=edit&amp;id=" . $id . "'>Изменить название</a><br/><br/>";
            }
            $km = mysql_query("select `id` from `lib` where type = 'komm' and refid = '" . $id . "';");
            $km1 = mysql_num_rows($km);
            echo "<a href='index.php?act=komm&amp;id=" . $id . "'>Комментарии</a>($km1)<br />";
            echo '<a href="index.php?act=java&amp;id=' . $id . '">Скачать Java книгу</a><br /><br />';
            $dnam = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $tp1[refid] . "';");
            $dnam1 = mysql_fetch_array($dnam);
            $catname = "$dnam1[text]";
            $dirid = "$dnam1[id]";
            $nadir = $tp1[refid];
            while ($nadir != "0")
            {
                echo "&#187;<a href='index.php?id=" . $nadir . "'>$catname</a><br/>";
                $dnamm = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $nadir . "';");
                $dnamm1 = mysql_fetch_array($dnamm);
                $dnamm2 = mysql_query("select `id`, `refid`, `text` from `lib` where type = 'cat' and id = '" . $dnamm1[refid] . "';");
                $dnamm3 = mysql_fetch_array($dnamm2);
                $nadir = $dnamm1[refid];
                $catname = $dnamm3[text];
            }
            echo "&#187;<a href='index.php?'>В библиотеку</a></p>";
            break;

        default:
            header("location: index.php");
            break;
    }
}

require_once ('../incfiles/end.php');

?>