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


$textl = 'Форум-поиск';
$headmod = "forums";
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");
if (!empty($_SESSION['uid']))
{
    $tti = round(($datauser['ftime'] - $realtime) / 60);
    if ($datauser['fban'] == "1" && $tti > 0)
    {

        echo "Вас пнули из форума<br/>Кто: <font color='" . $cdinf . "'>$datauser[fwho]</font><br/>";
        if ($datauser[fwhy] == "")
        {
            echo "<div>Причина не указана</div>";
        } else
        {
            echo "Причина:<font color='" . $cdinf . "'> $datauser[fwhy]</font><br>";
        }
        echo "Время до окончания: $tti минут<br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
}
if (!empty($_GET['act']))
{
    $act = check($_GET['act']);
}
switch ($act)
{
        ####
    case "go":
        if (!empty($_GET['srh']))
        {
            $srh = check(trim($_GET['srh']));
        } else
        {
            if ($_POST['srh'] == "")
            {
                echo "Вы не ввели условие поиска!<br/><a href='search.php'>К поиску</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $srh = check(trim($_POST['srh']));
        }


        if (!empty($_GET['m']))
        {
            $m = check(trim($_GET['m']));
        } else
        {
            if ($_POST['m'] == "")
            {
                echo "Вы не ввели метод поиска!<br/><a href='search.php'>К поиску</a><br/>";
                require_once ('../incfiles/end.php');
                exit;
            }
            $m = check(trim($_POST['m']));
        }

        switch ($m)
        {
            case "t":

                if ((!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1))
                {
                    if ($dostsadm == 1)
                    {
                        $psk = mysql_query("select * from `forum` where type='t' and moder='1' order by time desc ;");
                    } else
                    {
                        $psk = mysql_query("select * from `forum` where type='t' and moder='1' and close!='1' order by time desc ;");
                    }
                } else
                {
                    if ($dostsadm == 1)
                    {
                        $psk = mysql_query("select * from `forum` where type='t' and moder='1' order by time ;");
                    } else
                    {
                        $psk = mysql_query("select * from `forum` where type='t' and moder='1' and close!='1' order by time ;");
                    }
                }


                echo "Поиск по названию темы<br/>";
                while ($array = mysql_fetch_array($psk))
                {
                    if (stristr($array[text], $srh))
                    {
                        $q3 = mysql_query("select * from `forum` where type='r' and id='" . $array[refid] . "';");
                        $razd = mysql_fetch_array($q3);
                        $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
                        $frm = mysql_fetch_array($q4);
                        if ($array[vip] == 1)
                        {
                            $hd = "<img src='../images/pt.gif' alt=''/>";
                        } elseif ($array[edit] == 1)
                        {
                            $hd = "<img src='../images/tz.gif' alt=''/>";
                        } else
                        {
                            $np = mysql_query("select * from `forum` where type='l' and time>'" . $array[time] . "' and refid='" . $array[id] . "' and `from`='" . $login . "';");
                            $np1 = mysql_num_rows($np);
                            if ($np1 == 0)
                            {
                                $hd = "<img src='../images/np.gif' alt=''/>";
                            } else
                            {
                                $hd = "<img src='../images/op.gif' alt=''/>";
                            }
                        }
                        if ($array[close] == 1)
                        {
                            $tst = "<font color='#FFF000'>Тема удалена!</font><br/>";
                        } else
                        {
                            $tst = "";
                        }


                        $res[] = "$hd <a href='index.php?id=" . $array[id] . "'>$frm[text]/$razd[text]/$array[text]</a><br/>$tst";
                    }
                }


                break;
            case "m":
                if ((!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1))
                {
                    if ($dostsadm == 1)
                    {
                        $psk = mysql_query("select * from `forum` where type='m' order by time desc ;");
                    } else
                    {
                        $psk = mysql_query("select * from `forum` where type='m' and close!='1' order by time desc ;");
                    }
                } else
                {
                    if ($dostsadm == 1)
                    {
                        $psk = mysql_query("select * from `forum` where type='m' order by time ;");
                    } else
                    {
                        $psk = mysql_query("select * from `forum` where type='m' and close!='1' order by time ;");
                    }
                }

                echo "Поиск по сообщениям<br/>";
                while ($array = mysql_fetch_array($psk))
                {
                    ###
                    $pmsg = str_replace("[c]", "", $array[text]);
                    $pmsg = str_replace("[/c]", "", $pmsg);
                    $pmsg = str_replace("[l]", "", $pmsg);
                    $pmsg = str_replace("[l/]", "", $pmsg);
                    $pmsg = str_replace("[/l]", "", $pmsg);
                    $pmsg = str_replace("[b]", "", $pmsg);
                    $pmsg = str_replace("[/b]", "", $pmsg);
                    $pmsg = str_replace("[/c]", "", $pmsg);
                    $pmsg = str_replace("[/c]", "", $pmsg);
                    $pmsg = str_replace("[/c]", "", $pmsg);
                    $pmsg = str_replace("[/c]", "", $pmsg);


                    ###
                    if (stristr($pmsg, $srh))
                    {
                        $tem = mysql_query("select * from `forum` where  type='t' and id='" . $array[refid] . "';");
                        $tem1 = mysql_fetch_array($tem);
                        if (($dostsadm == 1 || $tem1[close] != 1) && ($tem1[moder] == 1))
                        {
                            $q3 = mysql_query("select * from `forum` where type='r' and id='" . $tem1[refid] . "';");
                            $razd = mysql_fetch_array($q3);
                            $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
                            $frm = mysql_fetch_array($q4);
                            if ((!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1))
                            {
                                if ($dostsadm == 1)
                                {
                                    $q1 = mysql_query("select * from `forum` where type='m' and refid='" . $tem1[id] . "'  order by time desc ;");
                                } else
                                {
                                    $q1 = mysql_query("select * from `forum` where type='m' and close!='1' and refid='" . $tem1[id] . "'  order by time desc ;");
                                }
                                $tp = 1;
                            } else
                            {
                                if ($dostsadm == 1)
                                {
                                    $q1 = mysql_query("select * from `forum` where type='m' and refid='" . $tem1[id] . "'  order by time ;");
                                } else
                                {
                                    $q1 = mysql_query("select * from `forum` where type='m' and close!='1' and refid='" . $tem1[id] . "'  order by time ;");
                                }
                            }
                            while ($mass = mysql_fetch_array($q1))
                            {
                                $msg[] = $mass[text];
                            }
                            $cn = count($msg);
                            for ($k = 0; $k < $cn; $k++)
                            {
                                if ($msg[$k] == $array[text])
                                {
                                    $npg = $k + 1;
                                }
                            }
                            $msg = array();
                            $page = ceil($npg / $kmess);
                            $vrp = $array[time] + $sdvig * 3600;
                            $vr = date("d.m.Y / H:i", $vrp);
                            $uz = @mysql_query("select * from `users` where name='" . check($array[from]) . "';");
                            $mass1 = @mysql_fetch_array($uz);
                            switch ($mass1[rights])
                            {
                                case 7:
                                    $stat = "Adm";
                                    break;
                                case 6:
                                    $stat = "Smd";
                                    break;
                                case 3:
                                    $stat = "Mod";
                                    break;
                                case 1:
                                    $stat = "Kil";
                                    break;
                                default:
                                    $stat = "";
                                    break;
                            }
                            switch ($mass1[sex])
                            {
                                case "m":
                                    $pol = "<img src='../images/m.gif' alt=''/>";
                                    break;
                                case "zh":
                                    $pol = "<img src='../images/f.gif' alt=''/>";
                                    break;
                            }
                            $hd = "$pol <b>$array[from]</b> $stat ($vr)<br/>";
                            if ($array[close] == 1)
                            {
                                $hd = "$hd <font color='#FFF000'>Пост удалён!</font><br/>";
                            }
                            if (!empty($array[to]))
                            {
                                $hd = "$hd $array[to], ";
                            }


                            ##
                            $array[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $array[text]);
                            $array[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $array[text]);
                            $array[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $array[text]);

                            if (stristr($array[text], "<a href="))
                            {
                                $array[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                                    "<a href='\\1\\3'>\\3</a>", $array[text]);
                            } else
                            {
                                $array[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $array[text]);
                            }
                            if ($offsm != 1 && $offgr != 1)
                            {
                                $tekst = smiles($array[text]);
                                $tekst = smilescat($tekst);

                                if ($array[from] == nickadmina || $array[from] == nickadmina2 || $array1[rights] >= 1)
                                {
                                    $tekst = smilesadm($tekst);
                                }
                            } else
                            {
                                $tekst = $array[text];
                            }
                            if (!empty($array[attach]))
                            {
                                $fls = filesize("./files/$array[attach]");
                                $fls = round($fls / 1024, 2);
                                $tekst = "$tekst<br/>Прикрепленный файл: <a href='index.php?act=file&amp;id=" . $array[id] . "'>$array[attach]</a> ($fls кб.)";
                            }
                            ##
                            if ($tem1[vip] == 1)
                            {
                                $tinf = "<img src='../images/pt.gif' alt=''/>";
                            } elseif ($tem1[edit] == 1)
                            {
                                $tinf = "<img src='../images/tz.gif' alt=''/>";
                            } else
                            {
                                $np = mysql_query("select * from `forum` where type='l' and time>'" . $tem1[time] . "' and refid='" . $tem1[id] . "' and `from`='" . $login . "';");
                                $np1 = mysql_num_rows($np);
                                if ($np1 == 0)
                                {
                                    $tinf = "<img src='../images/np.gif' alt=''/>";
                                } else
                                {
                                    $tinf = "<img src='../images/op.gif' alt=''/>";
                                }
                            }
                            if ($tem1[close] == 1)
                            {
                                $tst = "<font color='#FFF000'>Тема удалена!</font><br/>";
                            } else
                            {
                                $tst = "";
                            }
                            $res[] = "$tinf <a href='index.php?id=" . $tem1[id] . "&amp;page=" . $page . "'>[$frm[text]/$razd[text]/$tem1[text]]</a><br/>$tst $hd $tekst<br/>";
                        }
                    }
                }

                break;
        }


        $g = count($res);
        if ($g == 0)
        {
            echo "<br/>По вашему запросу ничего не найдено<br/>";
        } else
        {
            echo "Результаты поиска<br/>";
        }
        if (empty($_GET['page']))
        {
            $page = 1;
        } else
        {
            $page = intval($_GET['page']);
        }
        $start = $page * $kmess - $kmess;
        if ($g < $start + $kmess)
        {
            $end = $g;
        } else
        {
            $end = $start + $kmess;
        }

        for ($i = $start; $i < $end; $i++)
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
            echo "$div $res[$i]</div>";
        }
        if ($g > $kmess)
        {
            echo "<hr/>";


            $ba = ceil($g / $kmess);


            if ($offpg != 1)
            {
                echo "Страницы:<br/>";
            } else
            {
                echo "Страниц: $ba<br/>";
            }
            $asd = $start - ($kmess * 2);
            $asd2 = $start + ($kmess * 2);

            if ($start != 0)
            {
                echo '<a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
            }
            if ($offpg != 1)
            {
                if ($asd < $g && $asd > 0)
                {
                    echo ' <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=1&amp;">1</a> .. ';
                }
                $page2 = $ba - $page;
                $pa = ceil($page / 2);
                $paa = ceil($page / 3);
                $pa2 = $page + floor($page2 / 2);
                $paa2 = $page + floor($page2 / 3);
                $paa3 = $page + (floor($page2 / 3) * 2);
                if ($page > 13)
                {
                    echo ' <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) .
                        '</a> .. <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 +
                        1) . '</a> .. ';
                } elseif ($page > 7)
                {
                    echo ' <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                }
                for ($i = $asd; $i < $asd2; )
                {
                    if ($i < $g && $i >= 0)
                    {
                        $ii = floor(1 + $i / $kmess);

                        if ($start == $i)
                        {
                            echo " <b>$ii</b>";
                        } else
                        {
                            echo ' <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                        }
                    }
                    $i = $i + $kmess;
                }
                if ($page2 > 12)
                {
                    echo ' .. <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) .
                        '</a> .. <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                } elseif ($page2 > 6)
                {
                    echo ' .. <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                }
                if ($asd2 < $g)
                {
                    echo ' .. <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . $ba . '">' . $ba . '</a>';
                }
            } else
            {
                echo "<b>[$page]</b>";
            }

            if ($g > $start + $kmess)
            {
                echo ' <a href="search.php?act=go&amp;m=' . $m . '&amp;srh=' . $srh . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
            }
            echo "<form action='search.php' method='get'>Перейти к странице:<br/><input type='hidden' name='m' value='" . $m . "'/><input type='hidden' name='act' value='go'/><input type='hidden' name='srh' value='" . $srh .
                "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
        }


        if ($g != 0)
        {
            echo "<br/>Найдено совпадений: $g";
        }
        echo '<br/><a href="search.php?">К поиску</a><br/>';
        break;
    default:
        echo "<form action='search.php?act=go' method='post'>";
        echo "Поиск по форуму: <br/><input type='text' name='srh' value=''/><br/>
<select name='m'>Метод поиска:<br/><option value='t'>По названию темы</option>
<option value='m'>По сообщениям</option></select><br/>
<input type='submit' value='Найти!'/></form><br/>";
        break;
}
echo "<a href='index.php'>В форум</a><br/>";
require_once ('../incfiles/end.php');
?>