<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC1                                                        //
// Дата релиза: 08.02.2008                                                    //
// Авторский сайт: http://gazenwagen.com                                      //
////////////////////////////////////////////////////////////////////////////////
// Оригинальная идея и код: Евгений Рябинин aka JOHN77                        //
// E-mail: 
// Модификация, оптимизация и дизайн: Олег Касьянов aka AlkatraZ              //
// E-mail: alkatraz@batumi.biz                                                //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
// Внимание!                                                                  //
// Авторские версии данных скриптов публикуются ИСКЛЮЧИТЕЛЬНО на сайте        //
// http://gazenwagen.com                                                      //
// Если Вы скачали данный скрипт с другого сайта, то его работа не            //
// гарантируется и поддержка не оказывается.                                  //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_PUSTO', 1);

require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
if ($dostadm == 1)
{
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "new":
            if (isset($_POST['submit']))
            {

                if (empty($_POST['name']))
                {
                    echo "Вы не ввели заголовок<br/><a href='news.php?act=new'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if (empty($_POST['text']))
                {
                    echo "Вы не ввели текст<br/><a href='news.php?act=new'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $name = check($_POST['name']);
                $text = check($_POST['text']);
                if (!empty($_POST['pf']))
                {
                    $pf = intval(check($_POST['pf']));
                    $rz = $_POST['rz'];
                    $pr = mysql_query("select * from `forum` where type='r' and refid= '" . $pf . "';");
                    while ($pr1 = mysql_fetch_array($pr))
                    {
                        $arr[] = $pr1[id];
                    }
                    foreach ($rz as $v)
                    {
                        if (in_array($v, $arr))
                        {
                            mysql_query("insert into `forum` values(0,'" . $v . "','t','" . $realtime . "','" . $login . "','','','','','" . $name . "','','','1','','','','');");
                            $tem = mysql_query("select * from `forum` where type='t' and time='" . $realtime . "' and refid= '" . $v . "';");
                            $tem1 = mysql_fetch_array($tem);
                            $agn = strtok($agn, ' ');
                            mysql_query("insert into `forum` values(0,'" . $tem1[id] . "','m','" . $realtime . "','" . $login . "','','','" . $ipp . "','" . $agn . "','" . $text . "','','','','','','','');");
                        }
                    }
                }

                mysql_query("insert into `news` values(0,'" . $realtime . "','" . $login . "','" . $name . "','" . $text . "','" . $tem1[id] . "');");
                echo "Новость добавлена.<br/><a href='news.php'>Продолжить</a><br/>";
            } else
            {
                echo "Добавление новости<br/><form action='news.php?act=new' method='post'>Заголовок:<br/><input type='text' name='name'/><br/>Текст:<br/><input type='text' name='text'/><hr/>";
                echo "Выберите раздел форума для обсуждения новости:<br/>";
                $fr = mysql_query("select * from `forum` where type='f';");
                while ($fr1 = mysql_fetch_array($fr))
                {
                    echo "$fr1[text]<input type='radio' name='pf' value='" . $fr1[id] . "'/><select name='rz[]'>";
                    $pr = mysql_query("select * from `forum` where type='r' and refid= '" . $fr1[id] . "';");
                    while ($pr1 = mysql_fetch_array($pr))
                    {
                        echo "<option value='" . $pr1[id] . "'>$pr1[text]</option>";
                    }
                    echo "</select><br/>";
                }
                echo "<input type='submit' name='submit' value='Ok!'/></form><br/><a href='news.php'>К новостям</a><br/>";
            }
            break;
        case "edit":
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='news.php'>К новостям</a><br>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (isset($_POST['submit']))
            {

                if (empty($_POST['name']))
                {
                    echo "Вы не ввели заголовок<br/><a href='news.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if (empty($_POST['text']))
                {
                    echo "Вы не ввели текст<br/><a href='news.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $name = check($_POST['name']);
                $text = check($_POST['text']);

                mysql_query("update `news` set name='" . $name . "', text='" . $text . "' where id='" . $id . "';");
                echo "Новость изменена.<br/><a href='news.php'>Продолжить</a><br/>";
            } else
            {
                $n = mysql_query("select * from `news` where id='" . $id . "';");
                $n1 = mysql_fetch_array($n);
                echo "Редактирование новости<br/><form action='news.php?act=edit&amp;id=" . $id . "' method='post'>Заголовок:<br/><input type='text' name='name' value='" . $n1[name] . "'/><br/>Текст:<br/><input type='text' name='text' value='" . $n1[text] .
                    "'/><br/><input type='submit' name='submit' value='Ok!'/></form><br/><a href='news.php'>К новостям</a><br/>";
            }

            break;

        case "del":
            if (empty($_GET['id']))
            {
                if (isset($_GET['all']))
                {
                    if (isset($_GET['yes']))
                    {
                        $n = mysql_query("select * from `news`;");
                        while ($n1 = mysql_fetch_array($n))
                        {
                            mysql_query("delete from `news` where `id`='" . $n1[id] . "';");
                        }
                        echo "База новостей очищена!<br/><a href='news.php'>К новостям</a><br/>";
                    } else
                    {
                        echo "Вы уверены,что хотите удалить все новости?<br/><a href='news.php?act=del&amp;all&amp;yes'>Да</a> | <a href='news.php'>Нет</a><br/>";
                    }
                } else
                {
                    echo "Ошибка!<br/><a href='news.php'>К новостям</a><br>";
                    require ("../incfiles/end.php");
                    exit;
                }
            } else
            {
                $id = intval(check($_GET['id']));
                if (isset($_GET['yes']))
                {
                    mysql_query("delete from `news` where `id`='" . $id . "';");
                    echo "Новость удалена!<br/><a href='news.php'>К новостям</a><br/>";
                } else
                {
                    echo "Вы уверены,что хотите удалить новость?<br/><a href='news.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='news.php'>Нет</a><br/>";
                }
            }


            break;

        default:

            echo "Все новости<br/>";
            $nw = mysql_query("select * from `news` order by time desc;");
            $count = mysql_num_rows($nw);
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
                    $nw1[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $nw1[text]);
                    $nw1[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $nw1[text]);
                    $nw1[text] = eregi_replace("\\[l\\]((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='\\1\\3'>\\7</a>", $nw1[text]);

                    if (stristr($nw1[text], "<a href="))
                    {
                        $nw1[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                            "<a href='\\1\\3'>\\3</a>", $nw1[text]);
                    } else
                    {
                        $nw1[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $nw1[text]);
                    }
                    if ($offsm != 1 && $offgr != 1)
                    {
                        $tekst = smiles($nw1[text]);
                        $tekst = smilescat($tekst);

                        if ($nw1[from] == nickadmina || $nw1[from] == nickadmina2 || $nw11[rights] >= 1)
                        {
                            $tekst = smilesadm($tekst);
                        }
                    } else
                    {
                        $tekst = $nw1[text];
                    }


                    $vr = $nw1[time] + $sdvig * 3600;
                    $vr1 = date("d.m.y / H:i", $vr);
                    echo "$div<b>$nw1[name]</b><br/>Добавил: $nw1[avt] ($vr1)<br/><br/>$tekst<br/>";
                    if ($nw1[kom] != 0 && $nw1[kom] != "")
                    {
                        $mes = mysql_query("select * from `forum` where type='m' and refid= '" . $nw1[kom] . "';");
                        $komm = mysql_num_rows($mes) - 1;
                        echo "<a href='../forum/?id=" . $nw1[kom] . "'>Комментарии ($komm)</a><br/>";
                    } else
                    {
                        echo "Новость не нуждается в комментариях<br/>";
                    }
                    echo "<a href='news.php?act=del&amp;id=" . $nw1[id] . "'>Удалить</a> | <a href='news.php?act=edit&amp;id=" . $nw1[id] . "'>Изменить</a><br/>";
                    echo "</div>";
                }
                ++$i;
            }
            ######
            if ($count > $kmess)
            {
                echo "<hr/>";

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
            echo "Всего: $count<br/>";
            echo "<a href='news.php?act=new'>Добавить новость</a><br/>";

            echo "<a href='news.php?act=del&amp;all'>Удалить все новости</a><br/>";


            break;
    }
    echo "<a href='../" . $admp . "/main.php'>В админку</a><br/>";
} else
{
    header("location: ../index.php?err");
}


require ("../incfiles/end.php");
?>



