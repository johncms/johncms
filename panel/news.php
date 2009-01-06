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

require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

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
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (empty($_POST['text']))
                {
                    echo "Вы не ввели текст<br/><a href='news.php?act=new'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $name = check($_POST['name']);
                $text = mysql_real_escape_string($_POST['text']);
                if (!empty($_POST['pf']) && ($_POST['pf'] != '0'))
                {
                    $pf = intval($_POST['pf']);
                    $rz = $_POST['rz'];
                    $pr = mysql_query("select * from `forum` where type='r' and refid= '" . $pf . "';");
                    while ($pr1 = mysql_fetch_array($pr))
                    {
                        $arr[] = $pr1['id'];
                    }
                    foreach ($rz as $v)
                    {
                        if (in_array($v, $arr))
                        {
                            mysql_query("insert into `forum` values(0,'" . $v . "','t','" . $realtime . "','" . $login . "','','','','','" . $name . "','','','1','','','','','');");
                            $tem = mysql_query("select * from `forum` where type='t' and time='" . $realtime . "' and refid= '" . $v . "';");
                            $tem1 = mysql_fetch_array($tem);
                            $agn = strtok($agn, ' ');
                            mysql_query("insert into `forum` values(0,'" . $tem1['id'] . "','m','" . $realtime . "','" . $login . "','','','" . $ipp . "','" . $agn . "','" . $text . "','','','','','','','','');");
                        }
                    }
                }
                mysql_query("insert into `news` values(0,'" . $realtime . "','" . $login . "','" . $name . "','" . $text . "','" . $tem1[id] . "');");
                echo "Новость добавлена.<p><a href='news.php'>Продолжить</a><br/>";
            } else
            {
                echo 'Добавление новости<br/>';
				echo '<form action="news.php?act=new" method="post">';
				echo 'Заголовок:<br/><input type="text" name="name"/><br/>';
				echo 'Текст:<br/><textarea cols="20" rows="4" name="text"/><br/><br/>';
                echo "Выберите раздел форума для обсуждения новости:<br/>";
                $fr = mysql_query("select * from `forum` where type='f';");
                echo "<input type='radio' name='pf' value='0' checked='checked' />Не обсуждать<br />";
                while ($fr1 = mysql_fetch_array($fr))
                {
                    echo "<input type='radio' name='pf' value='" . $fr1['id'] . "'/>$fr1[text]<select name='rz[]'>";
                    $pr = mysql_query("select * from `forum` where type='r' and refid= '" . $fr1['id'] . "';");
                    while ($pr1 = mysql_fetch_array($pr))
                    {
                        echo '<option value="' . $pr1['id'] . '">'.$pr1['text'].'</option>';
                    }
                    echo '</select><br/>';
                }
                echo '<br /><input type="submit" name="submit" value="Ok!"/></form><p><a href="news.php">К новостям</a><br/>';
            }
            break;

        case "edit":
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='news.php'>К новостям</a><br>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (isset($_POST['submit']))
            {

                if (empty($_POST['name']))
                {
                    echo "Вы не ввели заголовок<br/><a href='news.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (empty($_POST['text']))
                {
                    echo "Вы не ввели текст<br/><a href='news.php?act=edit&amp;id=" . $id . "'>Повторить</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $name = check($_POST['name']);
                $text = mysql_real_escape_string($_POST['text']);

                mysql_query("update `news` set name='" . $name . "', text='" . $text . "' where id='" . $id . "';");
                echo "Новость изменена.<p><a href='news.php'>Продолжить</a><br/>";
            } else
            {
                $n = mysql_query("select * from `news` where id='" . $id . "';");
                $n1 = mysql_fetch_array($n);
                echo "Редактирование новости<br/><form action='news.php?act=edit&amp;id=" . $id . "' method='post'>Заголовок:<br/><input type='text' name='name' value='" . $n1[name] . "'/><br/>Текст:<br/><textarea cols='20' rows='4' name='text'>" . $n1[text] .
                    "</textarea><br/><input type='submit' name='submit' value='Ok!'/></form><p><a href='news.php'>К новостям</a><br/>";
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
                        echo "<p>База новостей очищена!<br/><a href='news.php'>К новостям</a><br/>";
                    } else
                    {
                        echo "<p>Вы уверены,что хотите удалить все новости?<br/><a href='news.php?act=del&amp;all&amp;yes'>Да</a> | <a href='news.php'>Нет</a><br/>";
                    }
                } else
                {
                    echo "Ошибка!<br/><a href='news.php'>К новостям</a><br>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
            } else
            {
                $id = intval(check($_GET['id']));
                if (isset($_GET['yes']))
                {
                    mysql_query("delete from `news` where `id`='" . $id . "';");
                    echo "<p>Новость удалена!<br/><a href='news.php'>К новостям</a><br/>";
                } else
                {
                    echo "<p>Вы уверены,что хотите удалить новость?<br/><a href='news.php?act=del&amp;id=" . $id . "&amp;yes'>Да</a> | <a href='news.php'>Нет</a><br/>";
                }
            }


            break;

        default:
            echo "<b>НОВОСТИ</b><hr/>";
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
                    $text = htmlentities($nw1['text'], ENT_QUOTES, 'UTF-8');
					$text = str_replace("\r\n", "<br/>", $text);
					$text = tags($text);
                    if ($offsm != 1 && $offgr != 1)
                    {
                        $text = smiles($text);
                        $text = smilescat($text);

                        if ($nw1['from'] == nickadmina || $nw1['from'] == nickadmina2 || $nw11['rights'] >= 1)
                        {
                            $text = smilesadm($text);
                        }
                    }
                    $vr = $nw1['time'] + $sdvig * 3600;
                    $vr1 = date("d.m.y / H:i", $vr);
                    echo "<b>$nw1[name]</b><br/>$text<br/>Добавил: $nw1[avt] ($vr1)<br/>";
                    if ($nw1['kom'] != 0 && $nw1['kom'] != "")
                    {
                        $mes = mysql_query("select * from `forum` where type='m' and refid= '" . $nw1['kom'] . "';");
                        $komm = mysql_num_rows($mes) - 1;
                        echo "<a href='../forum/?id=" . $nw1['kom'] . "'>Комментарии ($komm)</a><br/>";
                    }
                    echo "<a href='news.php?act=del&amp;id=" . $nw1['id'] . "'>Удалить</a> | <a href='news.php?act=edit&amp;id=" . $nw1[id] . "'>Изменить</a><br/><br/>";
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
            echo "Всего: $count<br/>";
            echo "<a href='news.php?act=new'>Добавить новость</a><br/>";
            echo "<a href='news.php?act=del&amp;all'>Удалить все новости</a><br/>";
            break;
    }
    echo "<a href='../" . $admp . "/main.php'>В админку</a></p>";
} else
{
    header("location: ../index.php?err");
}


require_once ("../incfiles/end.php");

?>