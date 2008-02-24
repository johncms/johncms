<?php
/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.0.0 RC2                                                        //
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

$textl = 'Модерка';
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
require ("../incfiles/char.php");
if ($dostmod == 1)
{
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "add":

            if (isset($_POST['submit']))
            {

                if ($_POST['msg'] == "")
                {
                    echo "Вы не ввели сообщение!<br/><a href='moderka.php'>Модерка</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if ($_GET['id'] != "")
                {
                    $id = intval(check(trim($_GET['id'])));
                    $md = mysql_query("select * from `moder` where id='" . $id . "';");
                    $md1 = mysql_fetch_array($md);
                    $to = $md1[avtor];
                } else
                {
                    $to = "";
                }
                $msg = check(trim($_POST['msg']));
                if ($_POST[msgtrans] == 1)
                {
                    $msg = trans($msg);
                }
                $msg = utfwin($msg);
                $msg = substr($msg, 0, 500);
                $msg = winutf($msg);
                $agn = strtok($agn, ' ');
                mysql_query("insert into `moder` values(0,'" . $realtime . "','" . $to . "','" . $login . "','" . $msg . "','" . $ipp . "','" . $agn . "');");
                header("Location: moderka.php");
            } else
            {
                if ($_GET['id'] != "")
                {
                    $id = intval(check(trim($_GET['id'])));
                    $md = mysql_query("select * from `moder` where id='" . $id . "';");
                    $md1 = mysql_fetch_array($md);
                    $to = $md1[avtor];
                    echo "Пишем в модерку для $to ";
                } else
                {
                    echo "Пишем в модерку";
                }


                echo "<br/><br/><form action='moderka.php?act=add&amp;id=" . $id . "' method='post'>
Cообщение(max.500)<br/>
<textarea rows='3' name='msg'></textarea><br/><br/>
<input type='checkbox' name='msgtrans' value='1' /> Транслит<br/>
<input type='submit' name='submit' value='добавить' />  
  </form><br/>";
                echo "<a href='moderka.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
            }
            echo '<br/><br/><a href="moderka.php">Назад</a><br/>';

            break;
            ################################
        case "trans":
            include ("../pages/trans.$ras_pages");
            echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            break;
            ############################
        default:
            echo "<a href='moderka.php?act=add'>Написать</a><br/>";
            $md = mysql_query("select * from `moder` order by time desc;");
            $count = mysql_num_rows($md);
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
            while ($mass = mysql_fetch_array($md))
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
                    if ($_SESSION['pid'] != $mass1[id])
                    {
                        echo "<a href='moderka.php?act=add&amp;id=" . $mass[id] . "'>$mass[avtor]</a>";
                    } else
                    {
                        echo "$mass[avtor]";
                    }
                    $vr = $mass[time] + $sdvig * 3600;
                    $vr1 = date("d.m.Y / H:i", $vr);
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
                    if (!empty($mass[to]))
                    {
                        echo "$mass[to], ";
                    }
                    echo "$tekst<br/>";
                    echo "</div>";
                }
                ++$i;
            }
            #######
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
                    echo '<a href="moderka.php?page=' . ($page - 1) . '">&lt;&lt;</a> ';
                }
                if ($offpg != 1)
                {
                    if ($asd < $count && $asd > 0)
                    {
                        echo ' <a href="moderka.php?page=1&amp;">1</a> .. ';
                    }
                    $page2 = $ba - $page;
                    $pa = ceil($page / 2);
                    $paa = ceil($page / 3);
                    $pa2 = $page + floor($page2 / 2);
                    $paa2 = $page + floor($page2 / 3);
                    $paa3 = $page + (floor($page2 / 3) * 2);
                    if ($page > 13)
                    {
                        echo ' <a href="moderka.php?page=' . $paa . '">' . $paa . '</a> <a href="moderka.php?page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="moderka.php?page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="moderka.php?page=' . ($paa *
                            2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                    } elseif ($page > 7)
                    {
                        echo ' <a href="moderka.php?page=' . $pa . '">' . $pa . '</a> <a href="moderka.php?page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
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
                                echo ' <a href="moderka.php?page=' . $ii . '">' . $ii . '</a> ';
                            }
                        }
                        $i = $i + $kmess;
                    }
                    if ($page2 > 12)
                    {
                        echo ' .. <a href="moderka.php?page=' . $paa2 . '">' . $paa2 . '</a> <a href="moderka.php?page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="moderka.php?page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="moderka.php?page=' . ($paa3 +
                            1) . '">' . ($paa3 + 1) . '</a> ';
                    } elseif ($page2 > 6)
                    {
                        echo ' .. <a href="moderka.php?page=' . $pa2 . '">' . $pa2 . '</a> <a href="moderka.php?page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                    }
                    if ($asd2 < $count)
                    {
                        echo ' .. <a href="moderka.php?page=' . $ba . '">' . $ba . '</a>';
                    }
                } else
                {
                    echo "<b>[$page]</b>";
                }


                if ($count > $start + $kmess)
                {
                    echo ' <a href="moderka.php?page=' . ($page + 1) . '">&gt;&gt;</a>';
                }
                echo "<form action='moderka.php'>Перейти к странице:<br/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
            }

            echo "<br/>Всего сообщений: $count";
            echo '<br/><a href="main.php">В админку</a><br/>';


            break;
    }
} else
{
    header("location: ../index.php?err");
}
require ("../incfiles/end.php");
?>