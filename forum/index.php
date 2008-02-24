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

$textl = 'Форум';
$headmod = "forum";
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");
require ("../incfiles/stat.php");
require ("../incfiles/head.php");
require ("../incfiles/inc.php");
require ("../incfiles/char.php");
if (!empty($_SESSION['pid']))
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
        require ("../incfiles/end.php");
        exit;
    }
    if (!empty($_GET['id']))
    {
        $id = intval(check($_GET['id']));
        $where = "forum,$id";
    } else
    {
        $where = "forum";
    }
    mysql_query("insert into `count` values(0,'" . $ipp . "','" . $agn . "','" . $realtime . "','" . $where . "','" . $login . "','0');");
}
if (!empty($_GET['act']))
{
    $act = check($_GET['act']);
}
switch ($act)
{
        ########
    case "file":
        if (empty($_GET['id']))
        {
            echo "Ошибка!<br/><a href='index.php'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $id = intval($_GET['id']);
        $fil = mysql_query("select * from `forum` where id='" . $id . "';");
        $mas = mysql_fetch_array($fil);

        if (!empty($mas[attach]))
        {
            $tfl = strtolower(format(trim($mas[attach])));
            $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
            if (in_array($tfl, $df))
            {
                echo "Ошибка!<br/>&#187;<a href='index.php'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (file_exists("./files/$mas[attach]"))
            {

                header("location: ./files/$mas[attach]");
            }
        } else
        {
            echo "Ошибка!<br/>&#187;<a href='index.php'>В форум</a><br/>";
        }
        break;
        ###############
    case "moders":

        if (empty($_GET['id']))
        {
            echo "<font color='" . $cdinf . "'>Модераторы по подфорумам</font><hr/>";
            $f = mysql_query("select * from `forum` where type='f'  order by realid;");
            while ($f1 = mysql_fetch_array($f))
            {
                $mod = mysql_query("select * from `forum` where type='a' and refid='" . $f1[id] . "';");
                $mod2 = mysql_num_rows($mod);
                if ($mod2 != 0)
                {
                    echo "$f1[text]<br/><br/>";
                    while ($mod1 = mysql_fetch_array($mod))
                    {
                        $uz = mysql_query("select * from `users` where name='" . $mod1[from] . "';");
                        $uz1 = mysql_fetch_array($uz);
                        if ($uz1[rights] == 3)
                        {
                            if ((!empty($_SESSION['pid'])) && ($login != $mod1[from]))
                            {
                                echo "<a href='../str/anketa.php?user=" . $uz1[id] . "'><font color='" . $conik . "'>$mod1[from]</font></a>";
                            } else
                            {
                                echo "<font color='" . $csnik . "'>$mod1[from]</font>";
                            }
                            $ontime = $uz1[lastdate];
                            $ontime2 = $ontime + 300;
                            if ($realtime > $ontime2)
                            {
                                echo "<font color='" . $coffs . "'> [Off]</font><br/>";
                            } else
                            {
                                echo "<font color='" . $cons . "'> [ON]</font><br/>";
                            }
                        }
                    }
                    echo "<hr/>";
                }
            }
        } else
        {
            $id = intval(check($_GET['id']));
            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            switch ($ms[type])
            {
                case "t":
                    $q3 = mysql_query("select * from `forum` where type='r' and id='" . $ms[refid] . "';");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
                    $fr = mysql_fetch_array($q4);
                    $mid = $razd[refid];
                    $pfr = $fr[text];
                    break;
                case "r":
                    $mid = $ms[refid];
                    $q3 = mysql_query("select * from `forum` where type='f' and id='" . $ms[refid] . "';");
                    $fr = mysql_fetch_array($q3);
                    $pfr = $fr[text];
                    break;
                case "f":
                    $mid = $id;
                    $pfr = $ms[text];
                    break;
                default:
                    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                    break;
            }
            $mod = mysql_query("select * from `forum` where type='a' and refid='" . $mid . "';");
            $mod2 = mysql_num_rows($mod);
            echo "Модеры подфорума <font color='" . $cntem . "'>$pfr</font><br/><br/>";
            if ($mod2 != 0)
            {
                while ($mod1 = mysql_fetch_array($mod))
                {
                    $uz = mysql_query("select * from `users` where name='" . $mod1[from] . "';");
                    $uz1 = mysql_fetch_array($uz);
                    if ($uz1[rights] == 3)
                    {
                        if ((!empty($_SESSION['pid'])) && ($login != $mod1[from]))
                        {
                            echo "<a href='../str/anketa.php?user=" . $uz1[id] . "'><font color='" . $conik . "'>$mod1[from]</font></a>";
                        } else
                        {
                            echo "<font color='" . $csnik . "'>$mod1[from]</font>";
                        }
                        $ontime = $uz1[lastdate];
                        $ontime2 = $ontime + 300;
                        if ($realtime > $ontime2)
                        {
                            echo "<font color='" . $coffs . "'> [Off]</font><br/>";
                        } else
                        {
                            echo "<font color='" . $cons . "'> [ON]</font><br/>";
                        }
                    }
                }
                echo "<hr/>";
            } else
            {
                echo "Не назначены<br/>";
            }
        }
        echo "<a href='index.php?id=" . $id . "'>Назад</a><br/>";
        break;
        #################
    case "per":
        if ($dostfmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "t")
            {
                echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (isset($_POST['submit']))
            {
                if (empty($_POST['razd']))
                {
                    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $razd = intval(check($_POST['razd']));
                $typ1 = mysql_query("select * from `forum` where id='" . $razd . "';");
                $ms1 = mysql_fetch_array($typ1);
                if ($ms1[type] != "r")
                {
                    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                mysql_query("update `forum` set  refid='" . $razd . "' where id='" . $id . "';");
                header("Location: index.php?id=$id");
            } else
            {
                if (empty($_GET['other']))
                {
                    $rz = mysql_query("select * from `forum` where id='" . $ms[refid] . "';");
                    $rz1 = mysql_fetch_array($rz);
                    $other = $rz1[refid];
                } else
                {
                    $other = intval(check($_GET['other']));
                }
                $raz = mysql_query("select * from `forum` where refid='" . $other . "';");
                $fr = mysql_query("select * from `forum` where id='" . $other . "';");
                $fr1 = mysql_fetch_array($fr);
                echo "Перенос темы внутри подфорума $fr1[text]<br/>Выберите раздел:<br/>";
                echo "<form action='index.php?act=per&amp;id=" . $id . "' method='post'><select name='razd'>";
                while ($raz1 = mysql_fetch_array($raz))
                {
                    if ($raz1[id] != $ms[refid])
                    {
                        echo "<option value='" . $raz1[id] . "'>$raz1[text]</option>";
                    }
                }
                echo "</select><br/>
<input type='submit' name='submit' value='Ok!'/></form>";
                echo "<hr/>Другие подфорумы:<br/>";
                $frm = mysql_query("select * from `forum` where type='f';");

                while ($frm1 = mysql_fetch_array($frm))
                {
                    if ($frm1[id] != $other)
                    {
                        echo "<a href='index.php?act=per&amp;id=" . $id . "&amp;other=" . $frm1[id] . "'>$frm1[text]</a><br/>";
                    }
                }
                echo "<hr/>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        echo "<a href='index.php?'>В форум</a><br/>";
        break;
        ###
    case "fmoder":
        if ($dostfmod == 1)
        {
            if (!empty($_GET['id']))
            {
                $id = intval(check($_GET['id']));
                $typ = mysql_query("select * from `forum` where id='" . $id . "';");
                $type = mysql_fetch_array($typ);
                if ($type[type] != "t")
                {
                    echo "Ошибка!<br/><a href='index.php'>В форум</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                mysql_query("update `forum` set  moder='1' where id='" . $id . "';");
                header("Location: index.php?id=$id");
            } else
            {
                echo "Темы, ожидающие модерации<br/>";
                $tm = mysql_query("select * from `forum` where type='t' and moder!='1';");
                $tm1 = mysql_num_rows($tm);
                while ($tm2 = mysql_fetch_array($tm))
                {
                    $d = $i / 2;
                    $d1 = ceil($d);
                    $d2 = $d1 - $d;
                    $d3 = ceil($d2);
                    if ($d3 == 0)
                    {
                        $div = "<div class='b'>";
                    } else
                    {
                        $div = "<div class='c'>";
                    }
                    echo "$div <a href='index.php?id=" . $tm2[id] . "'>$tm2[text]</a><br/>$tm2[from]</div>";
                    ++$i;
                }
                echo "Всего: $tm1<br/>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        echo "<a href='index.php?'>В форум</a><br/>";
        break;

        ###
    case "ren":

        if ($dostfmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));

            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "t")
            {
                echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (isset($_POST['submit']))
            {
                if (empty($_POST['nn']))
                {
                    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $nn = check(trim($_POST['nn']));
                ##

                $pt = mysql_query("select * from `forum` where type='t' and refid='" . $ms[refid] . "' and text='" . $nn . "';");
                if (mysql_num_rows($pt) != 0)
                {
                    echo "Ошибка!Тема с таким названием уже есть в этом разделе<br/><a href='index.php?act=ren&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                mysql_query("update `forum` set  text='" . $nn . "' where id='" . $id . "';");
                header("Location: index.php?id=$id");
            } else
            {
                echo "<form action='index.php?act=ren&amp;id=" . $id . "' method='post'>Переименование темы:<br/><input type='text' name='nn' value='" . $ms[text] . "'/><br/><input type='submit' name='submit' value='Ok!'/></form>";
            }
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        echo "<a href='index.php?'>В форум</a><br/>";
        break;

    case "deltema":

        if ($dostfmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));

            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "t")
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (isset($_GET['yes']))
            {
                if ($dostsadm == 1)
                {
                    $delp = mysql_query("select * from `forum` where type='m' and refid='" . $id . "';");
                    while ($arrd = mysql_fetch_array($delp))
                    {
                        if (!empty($arrd[attach]))
                        {
                            unlink("files/$arrd[attach]");
                        }
                        mysql_query("delete from `forum` where `id`='" . $arrd[id] . "';");
                    }

                    mysql_query("delete from `forum` where `id`='" . $id . "';");


                } else
                {
                    mysql_query("update `forum` set  close='1' where id='" . $id . "';");
                }
                header("Location: ?id=$ms[refid]");
            }
            if (isset($_GET['hid']))
            {
                if ($dostsadm == 1)
                {
                    mysql_query("update `forum` set  close='1' where id='" . $id . "';");
                }
                header("Location: ?id=$ms[refid]");
            }
            echo "Вы действительно хотите удалить тему?<br/>";
            echo "<a href='?act=deltema&amp;id=" . $id . "&amp;yes'>Удалить</a>";
            if (($dostsadm == 1) && ($ms[close] != 1))
            {
                echo "|<a href='?act=deltema&amp;id=" . $id . "&amp;hid'>Скрыть</a>";
            }
            echo "|<a href='?id=" . $ms[refid] . "'>Отмена</a><br/>";
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        break;

        #######
    case "vip":
        if ($dostfmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));

            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "t")
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (isset($_GET['vip']))
            {
                mysql_query("update `forum` set  vip='1' where id='" . $id . "';");
                header("Location: ?id=$id");
            } else
            {
                mysql_query("update `forum` set  vip='0' where id='" . $id . "';");
                header("Location: ?id=$id");
            }
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        break;
        #############
    case "close":
        if ($dostfmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));

            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "t")
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (isset($_GET['closed']))
            {
                mysql_query("update `forum` set  edit='1' where id='" . $id . "';");
                header("Location: ?id=$ms[refid]");
            } else
            {
                mysql_query("update `forum` set  edit='0' where id='" . $id . "';");
                header("Location: ?id=$id");
            }
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        break;

        ##########
    case "delpost":
        if ($dostfmod == 1)
        {
            if (empty($_GET['id']))
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));

            $typ = mysql_query("select * from `forum` where id='" . $id . "';");
            $ms = mysql_fetch_array($typ);
            if ($ms[type] != "m")
            {
                echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (isset($_GET['yes']))
            {
                if ($dostsadm == 1)
                {
                    if (!empty($ms[attach]))
                    {
                        unlink("files/$ms[attach]");
                    }
                    mysql_query("delete from `forum` where `id`='" . $id . "';");
                } else
                {
                    mysql_query("update `forum` set  close='1' where id='" . $id . "';");
                }
                header("Location: ?id=$ms[refid]");
            }
            if (isset($_GET['hid']))
            {
                if ($dostsadm == 1)
                {
                    mysql_query("update `forum` set  close='1' where id='" . $id . "';");
                }
                header("Location: ?id=$ms[refid]");
            }
            echo "Вы действительно хотите удалить пост?<br/>";
            echo "<a href='?act=delpost&amp;id=" . $id . "&amp;yes'>Удалить</a>";
            if (($dostsadm == 1) && ($ms[close] != 1))
            {
                echo "|<a href='?act=delpost&amp;id=" . $id . "&amp;hid'>Скрыть</a>";
            }
            echo "|<a href='?id=" . $ms[refid] . "'>Отмена</a><br/>";
        } else
        {
            echo "Доступ закрыт!!!<br>";
        }
        break;
        #######
    case "read":
        include ("../pages/forum.txt");
        echo "<a href='?'>В форум</a><br/>";
        break;
        ##################
    case "faq":
        include ("../pages/forumfaq.txt");
        echo "<a href='?'>В форум</a><br/>";
        break;
        ##################
    case "editpost":
        if (empty($_GET['id']))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $id = intval(check($_GET['id']));
        if (empty($_SESSION['pid']))
        {
            echo "Вы не авторизованы!<br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $typ = mysql_query("select * from `forum` where id='" . $id . "';");
        $ms = mysql_fetch_array($typ);
        if ($ms[type] != "m")
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }

        $lp = mysql_query("select * from `forum` where type='m' and refid='" . $ms[refid] . "'  order by time desc ;");
        while ($arr = mysql_fetch_array($lp))
        {
            $idpp[] = $arr[id];
        }
        $idpr = $idpp[0];
        $tpp = $realtime - 300;
        $lp1 = mysql_query("select * from `forum` where id='" . $idpr . "';");
        $arr1 = mysql_fetch_array($lp1);
        if (($dostfmod != 1) && (($ms[from] != $login) || ($arr1[id] != $ms[id]) || ($ms[time] < $tpp)))
        {
            echo "Ошибка!Вероятно,прошло более 5 минут со времени написания поста,или он уже не последний<br/><a href='?id=" . $ms[refid] . "'>В тему</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        if (($dostfmod == 1) || (($arr1[from] == $login) && ($arr1[id] == $ms[id]) && ($ms[time] > $tpp)))
        {
            if (isset($_POST['submit']))
            {
                if (empty($_POST['msg']))
                {
                    echo "Вы не ввели сообщение!<br/><a href='?act=editpost&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $msg = check(trim($_POST['msg']));
                if ($_POST[msgtrans] == 1)
                {
                    $msg = trans($msg);
                }
                $koled = $ms[kedit] + 1;
                mysql_query("update `forum` set  tedit='" . $realtime . "', edit='" . $login . "', kedit='" . $koled . "', text='" . $msg . "' where id='" . $id . "';");
                $pa = mysql_query("select * from `forum` where type='m' and refid= '" . $id . "';");
                $pa2 = mysql_num_rows($pa);

                if ((!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1))
                {
                    $page = 1;
                } else
                {
                    $page = ceil($pa2 / $kmess);
                }
                echo "Сообщение изменено.<br/><a href='index.php?id=" . $ms[refid] . "&amp;page=" . $page . "'>Продолжить</a><br/>";
            } else
            {
                $ms[text] = str_replace("<br/>", "\r\n", $ms[text]);
                echo "Редактирование сообщения (max. 500):<br/><form action='?act=editpost&amp;id=" . $id . "' method='post'><textarea cols='40' rows='3' title='Введите текст сообщения' name='msg'>$ms[text]</textarea><br/>";
                if ($offtr != 1)
                {
                    echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
                }
                echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
            }
        }
        echo "<a href='?id=" . $ms[refid] . "'>Назад</a><br/>";
        break;
        ##############
    case "nt":
        if (empty($_GET['id']))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $id = intval(check($_GET['id']));
        if (empty($_SESSION['pid']))
        {
            echo "Вы не авторизованы!<br/>";
            require ("../incfiles/end.php");
            exit;
        }

        $type = mysql_query("select * from `forum` where id= '" . $id . "';");
        $type1 = mysql_fetch_array($type);
        $tip = $type1[type];
        if ($tip != "r")
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        if (isset($_POST['submit']))
        {
            $flt = $realtime - 30;
            $af = mysql_query("select * from `forum` where type='m' and time>'" . $flt . "' and `from`= '" . $login . "';");
            $af1 = mysql_num_rows($af);
            if ($af1 != 0)
            {
                echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='?id=" . $id . "'>В раздел</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (empty($_POST['th']))
            {
                echo "Вы не ввели название темы!<br/><a href='?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if (empty($_POST['msg']))
            {
                echo "Вы не ввели сообщение!<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            #############
            $fname = $_FILES['fail']['name'];
            $fsize = $_FILES['fail']['size'];


            if ($fname != "")
            {
                $tfl = strtolower(format(trim($fname)));
                $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                if (in_array($tfl, $df))
                {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if ($fsize >= 1024 * $flsz)
                {
                    echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if (eregi("[^a-z0-9.()+_-]", $fname))
                {
                    echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess"))
                {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if (file_exists("files/$fname"))
                {
                    $fname = "$realtime.$fname";
                }
                if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "./files/$fname")) == true)
                {
                    $ch = $fname;
                    @chmod("$ch", 0777);
                    @chmod("files/$ch", 0777);
                    echo "Файл прикреплен!<br/>";
                } else
                {
                    echo "Ошибка при прикреплении файла<br/>";
                }
            }
            if (!empty($_POST['fail1']))
            {
                $uploaddir = "./files";
                $uploadedfile = $_POST['fail1'];
                if (strlen($uploadedfile) > 0)
                {
                    $array = explode('file=', $uploadedfile);
                    $tmp_name = $array[0];
                    $filebase64 = $array[1];
                }
                $tfl = strtolower(format(trim($tmp_name)));
                $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                if (in_array($tfl, $df))
                {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if (strlen(base64_decode($filebase64)) >= 1024 * $flsz)
                {
                    echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if (eregi("[^a-z0-9.()+_-]", $tmp_name))
                {
                    echo "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if ((preg_match("/php/i", $tmp_name)) or (preg_match("/.pl/i", $tmp_name)) or ($tmp_name == ".htaccess"))
                {
                    echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                    require ('../incfiles/end.php');
                    exit;
                }
                if (strlen($filebase64) > 0)
                {
                    $fname = $tmp_name;
                    if (file_exists("files/$fname"))
                    {
                        $fname = "$realtime.$fname";
                    }
                    $FileName = "$uploaddir/$fname";
                    $filedata = base64_decode($filebase64);
                    $fid = @fopen($FileName, "wb");

                    if ($fid)
                    {
                        if (flock($fid, LOCK_EX))
                        {
                            fwrite($fid, $filedata);
                            flock($fid, LOCK_UN);
                        }
                        fclose($fid);
                    }
                    if (file_exists($FileName) && filesize($FileName) == strlen($filedata))
                    {
                        echo 'Файл ', $tmp_name, ' успешно прикреплён';
                        $ch = $fname;
                    } else
                    {
                        echo 'Ошибка при прикреплении файла ', $tmp_name, '';
                    }
                }
            }
            ############

            $th = check(trim($_POST['th']));
            $th = utfwin($th);
            $th = substr($th, 0, 100);
            $o1 = strrpos($th, "<");
            if ($o1 >= 96)
            {
                $th = substr($th, 0, $o1);
            }
            $th = winutf($th);
            $msg = check(trim($_POST['msg']));
            //$msg=utfwin($msg);
            //$msg=substr($msg,0,500);
            //$o=strrpos($msg,"<");
            //if ($o>=496){
            //$msg=substr($msg,0,$o);}
            //$msg=winutf($msg);
            if ($_POST['msgtrans'] == 1)
            {
                $msg = trans($msg);
            }
            $pt = mysql_query("select * from `forum` where type='t' and refid='" . $id . "' and text='" . $th . "';");
            if (mysql_num_rows($pt) != 0)
            {
                echo "Ошибка!Тема с таким названием уже есть в этом разделе<br/><a href='index.php?act=nt&amp;id=" . $id . "'>Повторить</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            if ($fmod != 1)
            {
                $fmd = 1;
            } else
            {
                $fmd = 0;
            }

            mysql_query("insert into `forum` values(0,'" . $id . "','t','" . $realtime . "','" . $login . "','','','','','" . $th . "','','','" . $fmd . "','','','','');");
            $rid = mysql_insert_id();
            $thm = mysql_query("select * from `forum` where type='t'  and id= '" . $rid . "';");
            $tem1 = mysql_fetch_array($thm);
            $agn = strtok($agn, ' ');
            mysql_query("insert into `forum` values(0,'" . $tem1[id] . "','m','" . $realtime . "','" . $login . "','','','" . $ipp . "','" . $agn . "','" . $msg . "','','','','','','','" . $ch . "');");
            if (empty($datauser[postforum]))
            {
                $fpst = 1;
            } else
            {
                $fpst = $datauser[postforum] + 1;
            }
            mysql_query("update `users` set  postforum='" . $fpst . "' where id='" . intval($_SESSION['pid']) . "';");

            if ($fmod != 1)
            {
                $hid = $rid;
            } else
            {
                $hid = $tem1[refid];
            }
            echo "Тема добавлена<br/><a href='index.php?id=" . $hid . "'>Продолжить</a><br/>";
            $np = mysql_query("select * from `forum` where type='l' and refid='" . $tem1[id] . "' and `from`='" . $login . "';");
            $np1 = mysql_num_rows($np);
            if ($np1 == 0)
            {
                mysql_query("insert into `forum` values(0,'" . $tem1[id] . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','');");
            } else
            {
                $np2 = mysql_fetch_array($np);
                mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
            }


        } else
        {
            if (($datauser[postforum] == "" || $datauser[postforum] == 0))
            {
                if (!isset($_GET['yes']))
                {
                    include ("../pages/forum.txt");

                    echo "<a href='?act=nt&amp;id=" . $id . "&amp;yes'>Согласен</a>|<a href='?id=" . $id . "'>Не согласен</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
            }
            if ($fmod == 1)
            {
                echo "Внимание!В данный момент в форуме включена премодерация тем,то есть Ваша тема будет открыта для общего доступа только после проверки модератором.<br/>";
            }


            echo "Добавление темы в раздел <font color='" . $cntem . "'>$type1[text]</font>:<br/><form action='?act=nt&amp;id=" . $id .
                "' method='post' enctype='multipart/form-data'>Название(max. 100):<br/><input type='text' size='40' maxlength='100' title='Введите название темы' name='th'/><br/>Сообщение(max. 500):<br/><textarea cols='40' rows='3' title='Введите сообщение' name='msg'></textarea><br/>Прикрепить файл(max. $flsz kb):<br />
         <input type='file' name='fail'/><hr/>
Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><hr/>";
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
            }
            echo "<input type='submit' name='submit' title='Нажмите для отправки' value='Отправить'/><br/></form>";
            echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
            echo "<a href='?id=" . $id . "'>Назад</a><br/>";
        }
        break;
        ###############
    case "tema":
        $delf = opendir("temtemp");
        while ($tt = readdir($delf))
        {
            if ($tt != "." && $tt != ".." && $tt != "index.php")
            {
                $tm[] = $tt;
            }
        }
        closedir($delf);
        $totalt = count($tm);
        for ($it = 0; $it < $totalt; $it++)
        {
            $filtime[$it] = filemtime("temtemp/$tm[$it]");
            $tim = time();
            $ftime1 = $tim - 300;
            if ($filtime[$it] < $ftime1)
            {
                unlink("temtemp/$tm[$it]");
            }
        }
        if (empty($_GET['id']))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $id = intval(check($_GET['id']));
        $type = mysql_query("select * from `forum` where id= '" . $id . "';");
        $type1 = mysql_fetch_array($type);
        $tip = $type1[type];
        if ($tip != "t")
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        if (isset($_POST['submit']))
        {
            $tema = mysql_query("select * from `forum` where type='m' and refid= '" . $id . "' order by time;");


            $mod = check(trim($_POST['mod']));
            switch ($mod)
            {
                    ##
                case "txt":
                    $text = "$type1[text]\r\n";
                    while ($arr = mysql_fetch_array($tema))
                    {
                        $arr[text] = str_replace("[c]", "Цитата:{", $arr[text]);
                        $arr[text] = str_replace("[/c]", "}-Ответ:", $arr[text]);
                        $arr[text] = str_replace("&quot;", "\"", $arr[text]);
                        $arr[text] = str_replace("[l]", "", $arr[text]);
                        $arr[text] = str_replace("[l/]", "-", $arr[text]);
                        $arr[text] = str_replace("[/l]", "", $arr[text]);
                        if (!empty($arr[to]))
                        {
                            $stroka = "$arr[from](" . date("d.m.Y/H:i", $arr[time]) . ")-$arr[to], $arr[text]\r\n";
                        } else
                        {
                            $stroka = "$arr[from](" . date("d.m.Y/H:i", $arr[time]) . ")-$arr[text]\r\n";
                        }
                        $text = "$text$stroka";
                    }
                    $num = "$realtime$id";
                    $fp = fopen("temtemp/$num.txt", "a+");
                    flock($fp, LOCK_EX);
                    fputs($fp, "$text\r\n");
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    @chmod("$fp", 0777);
                    @chmod("temtemp/$num.txt", 0777);

                    echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
                    break;
                    ##
                case "xml":
                    $text = "<?xml version='1.0' encoding='utf-8'?>
<!DOCTYPE html PUBLIC '-//WAPFORUM//DTD XHTML Mobile 1.0//EN' 
'http://www.wapforum.org/DTD/xhtml-mobile10.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml' xml:lang='ru'>
<head>
<meta http-equiv='content-type' content='application/xhtml+xml; charset=utf-8'/><link rel='shortcut icon' href='favicon.ico' />
      <title>
      Форум
      </title>
<style type='text/css'>

body { font-weight: normal; font-family: Arial; font-size: 12px; color: #99FF99; background-color: #000000}
a:link { text-decoration: underline; color : #D3ECFF}
a:active { text-decoration: underline; color : #2F3528 }
a:visited { text-decoration: underline; color : #31F7D4}
a:hover { text-decoration: none; font-size: 12px; color : #E4F992 }
div { margin: 1px 0px 1px 0px; padding: 0px 0px 0px 0px;font-size: 14px; font-weight: normal;}  
table { margin: 1px 1px 1px 1px; padding: 1px 1px 1px 1px; font-size: 13px; font-weight: normal;}

.a {background-color: #000022;  text-align: left; font-size: 13px;font-weight: normal; color: #99FF99; border-left:1px solid #FCFCFC; border-right:1px solid #FCFCFC; border-bottom:1px solid #FCFCFC; border-top:1px solid #FCFCFC;}
.b {background-color: #000033;  text-align: left; font-size: 12px; color: #D9F51E; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
.c {background-color: #000044;  text-align: left; font-size: 12px;  border-left:0px solid #FCFCFC; border-right:0px solid #FCFCFC; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
.d {background-color: $fon;  text-align: left; font-size: 12px; color: olive; border-left:0px solid #FCFCFC; border-right:0px solid #FCFCFC; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
</style>
      </head>
      <body><div class = 'a' ><center><b>Форум</b></center><br/><b>$type1[text]</b><hr/>";
                    $i = 1;
                    while ($arr = mysql_fetch_array($tema))
                    {
                        $d = $i / 2;
                        $d1 = ceil($d);
                        $d2 = $d1 - $d;
                        $d3 = ceil($d2);
                        if ($d3 == 0)
                        {
                            $div = "<div class='b'>";
                        } else
                        {
                            $div = "<div class='c'>";
                        }
                        $arr[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $arr[text]);
                        $arr[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $arr[text]);

                        if (stristr($arr[text], "<a href="))
                        {
                            $arr[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                                "<a href='\\1\\3'>\\3</a>", $arr[text]);
                        } else
                        {
                            $arr[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $arr[text]);
                        }
                        if (!empty($arr[to]))
                        {
                            $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[to], $arr[text]</div>";
                        } else
                        {
                            $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[text]</div>";
                        }
                        $text = "$text $stroka";
                        ++$i;
                    }
                    $text = "$text<center><b>$copyright</b></center><br/></div></body></html>";
                    $num = "$realtime$id";
                    $fp = fopen("temtemp/$num.xml", "a+");
                    flock($fp, LOCK_EX);
                    fputs($fp, "$text\r\n");
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    @chmod("$fp", 0777);
                    @chmod("temtemp/$num.xml", 0777);
                    echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
                    break;
                    ########
                case "htm":
                    $text = "<!DOCTYPE html PUBLIC '-//W3C//DTD HTML 4.01 Transitional//EN'><html><head><meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<link rel='shortcut icon' href='favicon.ico'><title>Форум</title>
<style type='text/css'>
body { font-weight: normal; font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #FFFFFF; background-color: #000000 }
a:link { text-decoration: underline; color : #999999 }
a:active { text-decoration: underline; color : #FFFFFF }
a:visited { text-decoration: underline; color : #333333 }
a:hover { text-decoration: none; font-size: 14px; color : #FFFFFF }
div { margin: 1px 0px 1px 0px; padding: 5px 5px 5px 5px;}  
table { margin: 1px 0px 1px 0px; padding: 1px 1px 1px 1px; font-size: 13px;}
.a {margin: 0px; border-top: 7px solid #000046; border-left: 7px solid #000034; border-right: 7px solid #000034; border-bottom: 7px solid #000015; padding: 5px; vertical-align: middle; background-color: #000022;  text-align: center; font-size: 15px; color: #FFFFFF;} 
.b {margin: 0px; border-top: 7px solid #000055; border-left: 7px solid #000049; border-right: 7px solid #000049; border-bottom: 7px solid #000019; padding: 5px; vertical-align: middle; background-color: #000033;  text-align: left; font-size: 13px; color: #FFFFFF; }
.c {margin: 0px; border-top: 7px solid #000077; border-left: 7px solid #000059; border-right: 7px solid #000059; border-bottom: 7px solid #000029; padding: 5px; vertical-align: middle; background-color: #000049;  text-align: left; font-size: 13px; color: #FFFFFF; }
.d {background-color: $fon;  text-align: left; font-size: 13px; color: olive; border-left:0px solid #FCFCFC; border-right:0px solid #FCFCFC; border-bottom:0px solid #FCFCFC; border-top:0px solid #FCFCFC;}
</style></head>
      <body><div class = 'a' ><center><b>Форум</b></center><br/><b>$type1[text]</b><br/>";
                    $i = 1;
                    while ($arr = mysql_fetch_array($tema))
                    {
                        $d = $i / 2;
                        $d1 = ceil($d);
                        $d2 = $d1 - $d;
                        $d3 = ceil($d2);
                        if ($d3 == 0)
                        {
                            $div = "<div class='b'>";
                        } else
                        {
                            $div = "<div class='c'>";
                        }
                        $arr[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class=\'d\'>\1<br/></div>', $arr[text]);
                        $arr[text] = eregi_replace("\\[l\\]([[:alnum:]_=:/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $arr[text]);

                        if (stristr($arr[text], "<a href="))
                        {
                            $arr[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                                "<a href='\\1\\3'>\\3</a>", $arr[text]);
                        } else
                        {
                            $arr[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $arr[text]);
                        }
                        if (!empty($arr[to]))
                        {
                            $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[to], $arr[text]</div>";
                        } else
                        {
                            $stroka = "$div <b>$arr[from]</b>(" . date("d.m.Y/H:i", $arr[time]) . ")<br/>$arr[text]</div>";
                        }
                        $text = "$text $stroka";
                        ++$i;
                    }
                    $text = "$text<center><b>$copyright</b></center><br/></div></body></html>";
                    $num = "$realtime$id";
                    $fp = fopen("temtemp/$num.htm", "a+");
                    flock($fp, LOCK_EX);
                    fputs($fp, "$text\r\n");
                    fflush($fp);
                    flock($fp, LOCK_UN);
                    fclose($fp);
                    @chmod("$fp", 0777);
                    @chmod("temtemp/$num.htm", 0777);
                    echo "<a href='?act=loadtem&amp;n=" . $num . "'>Скачать</a><br/>Ссылка активна 5 минут!<br/><a href='?'>В форум</a><br/>";
                    break;
            }
        } else
        {
            echo "Выберите формат<br/><form action='?act=tema&amp;id=" . $id . "' method='post'><br/><select name='mod'>
	<option value='txt'>.txt</option>
	<option value='xml'>.xml</option>
	<option value='htm'>.htm</option>
	</select><br/>
<input type='submit' name='submit' value='Ok!'/><br/></form>";
        }


        break;
        #########################
    case "loadtem":
        if (empty($_GET['n']))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $n = $_GET['n'];
        $o = opendir("temtemp");
        while ($f = readdir($o))
        {
            if ($f != "." && $f != ".." && $f != "index.php" && $f != ".htaccess")
            {
                $ff = format($f);
                $f1 = str_replace(".$ff", "", $f);
                $a[] = $f;
                $b[] = $f1;
            }
        }
        $tt = count($a);
        if (!in_array($n, $b))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        for ($i = 0; $i < $tt; $i++)
        {
            $tf = format($a[$i]);
            $tf1 = str_replace(".$tf", "", $a[$i]);
            if ($n == $tf1)
            {
                header("Location: temtemp/$n.$tf");
            }
        }
        break;


        ################################
    case "trans":
        include ("../pages/trans.$ras_pages");
        echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
        break;
        ############################

    case "say":
        $agn = strtok($agn, ' ');
        if (empty($_GET['id']))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $id = intval(check($_GET['id']));
        if (empty($_SESSION['pid']))
        {
            echo "Вы не авторизованы!<br/>";
            require ("../incfiles/end.php");
            exit;
        }

        $type = mysql_query("select * from `forum` where id= '" . $id . "';");
        $type1 = mysql_fetch_array($type);
        $tip = $type1[type];
        switch ($tip)
        {
                ###
            case "t":
                if ($type1[edit] == 1)
                {
                    echo "Вы не можете писать в закрытую тему<br/><a href='?id=" . $id . "'>В тему</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if (isset($_POST['submit']))
                {
                    $flt = $realtime - 30;
                    $af = mysql_query("select * from `forum` where type='m' and time >='" . $flt . "' and `from` = '" . check(trim($login)) . "';");
                    $af1 = mysql_num_rows($af);
                    if ($af1 > 0)
                    {
                        echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='?id=" . $id . "'>В тему</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    if (empty($_POST['msg']))
                    {
                        echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }

                    #############
                    $fname = $_FILES['fail']['name'];
                    $fsize = $_FILES['fail']['size'];


                    if ($fname != "")
                    {
                        $tfl = strtolower(format($fname));
                        $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                        if (in_array($tfl, $df))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        if ($fsize >= 1024 * $flsz)
                        {
                            echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (eregi("[^a-z0-9.()+_-]", $fname))
                        {
                            echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (file_exists("files/$fname"))
                        {
                            $fname = "$realtime.$fname";
                        }
                        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "./files/$fname")) == true)
                        {
                            $ch = $fname;
                            @chmod("$ch", 0777);
                            @chmod("files/$ch", 0777);
                            echo "Файл прикреплен!<br/>";
                        } else
                        {
                            echo "Ошибка при прикреплении файла<br/>";
                        }
                    }
                    if (!empty($_POST['fail1']))
                    {
                        $uploaddir = "./files";
                        $uploadedfile = $_POST['fail1'];
                        if (strlen($uploadedfile) > 0)
                        {
                            $array = explode('file=', $uploadedfile);
                            $tmp_name = $array[0];
                            $filebase64 = $array[1];
                        }
                        $tfl = strtolower(format($tmp_name));
                        $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                        if (in_array($tfl, $df))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        if (strlen(base64_decode($filebase64)) >= 1024 * $flsz)
                        {
                            echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (eregi("[^a-z0-9.()+_-]", $tmp_name))
                        {
                            echo "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/php/i", $tmp_name)) or (preg_match("/.pl/i", $tmp_name)) or ($tmp_name == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (strlen($filebase64) > 0)
                        {
                            $fname = $tmp_name;
                            if (file_exists("files/$fname"))
                            {
                                $fname = "$realtime.$fname";
                            }
                            $FileName = "$uploaddir/$fname";
                            $filedata = base64_decode($filebase64);
                            $fid = @fopen($FileName, "wb");

                            if ($fid)
                            {
                                if (flock($fid, LOCK_EX))
                                {
                                    fwrite($fid, $filedata);
                                    flock($fid, LOCK_UN);
                                }
                                fclose($fid);
                            }
                            if (file_exists($FileName) && filesize($FileName) == strlen($filedata))
                            {
                                echo 'Файл ', $tmp_name, ' успешно прикреплён';
                                $ch = $fname;
                            } else
                            {
                                echo 'Ошибка при прикреплении файла ', $tmp_name, '';
                            }
                        }
                    }
                    ############
                    $msg = check(trim($_POST['msg']));
                    //$msg=utfwin($msg);

                    //$msg=substr($msg,0,500);
                    //if ($o>=496){
                    //$o=strrpos($msg,"<");
                    //$msg=substr($msg,0,$o);}
                    //$msg=winutf($msg);
                    if ($_POST[msgtrans] == 1)
                    {
                        $msg = trans($msg);
                    }
                    mysql_query("insert into `forum` values(0,'" . $id . "','m','" . $realtime . "','" . $login . "','','','" . $ipp . "','" . $agn . "','" . $msg . "','','','','','','','" . $ch . "');");
                    mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $id . "';");
                    if (empty($datauser[postforum]))
                    {
                        $fpst = 1;
                    } else
                    {
                        $fpst = $datauser[postforum] + 1;
                    }
                    mysql_query("update `users` set  postforum='" . $fpst . "' where id='" . intval($_SESSION['pid']) . "';");
                    $pa = mysql_query("select * from `forum` where type='m' and refid= '" . $id . "';");
                    $pa2 = mysql_num_rows($pa);

                    if (((empty($_SESSION['pid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['pid'])) && $upfp == 1))
                    {
                        $page = 1;
                    } else
                    {
                        $page = ceil($pa2 / $kmess);
                    }

                    echo "Сообщение добавлено<br/><a href='index.php?id=" . $id . "&amp;page=" . $page . "'>Продолжить</a><br/>";
                    $np = mysql_query("select * from `forum` where type='l' and refid='" . $id . "' and `from`='" . $login . "';");
                    $np1 = mysql_num_rows($np);
                    if ($np1 == 0)
                    {
                        mysql_query("insert into `forum` values(0,'" . $id . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','');");
                    } else
                    {
                        $np2 = mysql_fetch_array($np);
                        mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
                    }
                } else
                {

                    if (($datauser[postforum] == "" || $datauser[postforum] == 0))
                    {
                        if (!isset($_GET['yes']))
                        {
                            include ("../pages/forum.txt");

                            echo "<a href='?act=say&amp;id=" . $id . "&amp;yes'>Согласен</a>|<a href='?id=" . $id . "'>Не согласен</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                    }
                    echo "Добавление сообщения в тему <font color='" . $cntem . "'>$type1[text]</font>(max. 500):<br/><form action='?act=say&amp;id=" . $id .
                        "' method='post' enctype='multipart/form-data'><textarea cols='40' rows='3' title='Введите текст сообщения' name='msg'></textarea><br/>Прикрепить файл(max. $flsz kb):<br />
         <input type='file' name='fail'/><hr/>
Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><hr/>";
                    if ($offtr != 1)
                    {
                        echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                    }
                    echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
                }
                echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
                echo "<a href='?id=" . $id . "'>Назад</a><br/>";
                break;
                ###
            case "m":
                $th = $type1[refid];
                $th2 = mysql_query("select * from `forum` where id= '" . $th . "';");
                $th1 = mysql_fetch_array($th2);
                if (isset($_POST['submit']))
                {
                    $flt = $realtime - 30;
                    $af = mysql_query("select * from `forum` where type='m' and time>'" . $flt . "' and `from`= '" . $login . "';");
                    $af1 = mysql_num_rows($af);
                    if ($af1 != 0)
                    {
                        echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='?id=" . $th . "'>В тему</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    if (empty($_POST['msg']))
                    {
                        echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }

                    #############
                    $fname = $_FILES['fail']['name'];
                    $fsize = $_FILES['fail']['size'];


                    if ($fname != "")
                    {
                        $tfl = strtolower(format($fname));
                        $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                        if (in_array($tfl, $df))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        if ($fsize >= 1024 * $flsz)
                        {
                            echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (eregi("[^a-z0-9.()+_-]", $fname))
                        {
                            echo "В названии файла <b>$fname</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/php/i", $fname)) or (preg_match("/.pl/i", $fname)) or ($fname == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (file_exists("files/$fname"))
                        {
                            $fname = "$realtime.$fname";
                        }
                        if ((move_uploaded_file($_FILES["fail"]["tmp_name"], "./files/$fname")) == true)
                        {
                            $ch = $fname;
                            @chmod("$ch", 0777);
                            @chmod("files/$ch", 0777);
                            echo "Файл прикреплен!<br/>";
                        } else
                        {
                            echo "Ошибка при прикреплении файла<br/>";
                        }
                    }
                    if (!empty($_POST['fail1']))
                    {
                        $uploaddir = "./files";
                        $uploadedfile = $_POST['fail1'];
                        if (strlen($uploadedfile) > 0)
                        {
                            $array = explode('file=', $uploadedfile);
                            $tmp_name = $array[0];
                            $filebase64 = $array[1];
                        }
                        $tfl = strtolower(format($tmp_name));
                        $df = array("asp", "aspx", "shtml", "htd", "php", "php3", "php4", "php5", "phtml", "htt", "cfm", "tpl", "dtd", "hta", "pl", "js", "jsp");
                        if (in_array($tfl, $df))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        if (strlen(base64_decode($filebase64)) >= 1024 * $flsz)
                        {
                            echo "Вес файла превышает $flsz кб<br/>
<a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (eregi("[^a-z0-9.()+_-]", $tmp_name))
                        {
                            echo "В названии файла <b>$tmp_name</b> присутствуют недопустимые символы<br/>Разрешены только латинские символы, цифры и некоторые знаки ( .()+_- )<br /><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if ((preg_match("/php/i", $tmp_name)) or (preg_match("/.pl/i", $tmp_name)) or ($tmp_name == ".htaccess"))
                        {
                            echo "Попытка отправить файл запрещенного типа.<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ('../incfiles/end.php');
                            exit;
                        }
                        if (strlen($filebase64) > 0)
                        {
                            $fname = $tmp_name;
                            if (file_exists("files/$fname"))
                            {
                                $fname = "$realtime.$fname";
                            }
                            $FileName = "$uploaddir/$fname";
                            $filedata = base64_decode($filebase64);
                            $fid = @fopen($FileName, "wb");

                            if ($fid)
                            {
                                if (flock($fid, LOCK_EX))
                                {
                                    fwrite($fid, $filedata);
                                    flock($fid, LOCK_UN);
                                }
                                fclose($fid);
                            }
                            if (file_exists($FileName) && filesize($FileName) == strlen($filedata))
                            {
                                echo 'Файл ', $tmp_name, ' успешно прикреплён';
                                $ch = $fname;
                            } else
                            {
                                echo 'Ошибка при прикреплении файла ', $tmp_name, '';
                            }
                        }
                    }
                    $msg = check(trim($_POST['msg']));
                    //$msg=utfwin($msg);
                    //$msg=substr($msg,0,500);

                    //$o=strrpos($msg,"<");
                    //if ($o>=496){
                    //$msg=substr($msg,0,$o);}

                    //$msg=winutf($msg);
                    if ($_POST[msgtrans] == 1)
                    {
                        $msg = trans($msg);
                    }
                    $to = $type1[from];
                    if (isset($_GET['cyt']))
                    {
                        if (!empty($_POST['citata']))
                        {
                            $citata = check(trim($_POST['citata']));
                            $citata = utfwin($citata);
                            $citata = substr($citata, 0, 500);
                            $o1 = strrpos($citata, "<");
                            if ($o1 >= 496)
                            {
                                $citata = substr($citata, 0, $o1);
                            }
                            $citata = winutf($citata);
                        } else
                        {
                            $citata = $type1[text];
                        }
                        $tp = date("d.m.Y/H:i", $type1[time]);
                        $msg = "[c]$to($tp):&quot; $citata &quot;[/c]$msg";
                        $to = "";
                    }


                    mysql_query("insert into `forum` values(0,'" . $th . "','m','" . $realtime . "','" . $login . "','" . $to . "','','" . $ipp . "','" . $agn . "','" . $msg . "','','','','','','','" . $ch . "');");
                    mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $th . "';");
                    if (empty($datauser[postforum]))
                    {
                        $fpst = 1;
                    } else
                    {
                        $fpst = $datauser[postforum] + 1;
                    }
                    mysql_query("update `users` set  postforum='" . $fpst . "' where id='" . intval($_SESSION['pid']) . "';");
                    $pa = mysql_query("select * from `forum` where type='m' and refid= '" . $th . "';");
                    $pa2 = mysql_num_rows($pa);

                    if (((empty($_SESSION['pid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['pid'])) && $upfp == 1))
                    {
                        $page = 1;
                    } else
                    {
                        $page = ceil($pa2 / $kmess);
                    }

                    echo "Сообщение добавлено<br/><a href='index.php?id=" . $th . "&amp;page=" . $page . "'>Продолжить</a><br/>";
                    $np = mysql_query("select * from `forum` where type='l' and refid='" . $th . "' and `from`='" . $login . "';");
                    $np1 = mysql_num_rows($np);
                    if ($np1 == 0)
                    {
                        mysql_query("insert into `forum` values(0,'" . $th . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','');");
                    } else
                    {
                        $np2 = mysql_fetch_array($np);
                        mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
                    }
                } else
                {
                    if (!empty($type1[to]))
                    {
                        $qt = "$type1[to], $type1[text]";
                    } else
                    {
                        $qt = " $type1[text]";
                    }
                    ##
                    $user = mysql_query("select * from `users` where name='" . $type1[from] . "';");
                    $udat = mysql_fetch_array($user);
                    echo "<b><font color='" . $conik . "'>$type1[from]</font></b>";
                    echo " (id: $udat[id])";
                    $ontime = $udat[lastdate];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2)
                    {
                        echo "<font color='" . $coffs . "'> [Off]</font><br/>";
                    } else
                    {
                        echo "<font color='" . $cons . "'> [ON]</font><br/>";
                    }
                    if ($udat[dayb] == $day && $udat[monthb] == $mon)
                    {
                        echo "<font color='" . $cdinf . "'>ИМЕНИННИК!!!</font><br/>";
                    }
                    switch ($udat[rights])
                    {
                        case 7:
                            echo ' Админ ';
                            break;
                        case 6:
                            echo ' Супермодер ';
                            break;
                        case 5:
                            echo ' Зам. админа по библиотеке ';
                            break;
                        case 4:
                            echo ' Зам. админа по загрузкам ';
                            break;
                        case 3:
                            echo ' Модер форума ';
                            break;
                        case 2:
                            echo ' Модер чата ';
                            break;
                        case 1:
                            echo ' Киллер ';
                            break;
                        default:
                            echo ' юзер ';
                            break;
                    }
                    if (!empty($udat[status]))
                    {
                        $stats = $udat[status];
                        $stats = smiles($stats);
                        $stats = smilescat($stats);

                        $stats = smilesadm($stats);
                        echo "<br/><font color='" . $cdinf . "'>$stats</font><br/>";
                    }

                    if ($udat['sex'] == "m")
                    {
                        echo "Парень<br/>";
                    }
                    if ($udat['sex'] == "zh")
                    {
                        echo "Девушка<br/>";
                    }
                    if ($udat['ban'] == "1" && $udat['bantime'] > $realtime || $udat['ban'] == "2")
                    {
                        echo "<font color='" . $cdinf . "'>Бан!</font><br/>";
                    }
                    if (!empty($_SESSION['pid']))
                    {
                        ####
                        ######
                        ########
                        $nmen = array(1 => "Имя", "Город", "Инфа", "ICQ", "E-mail", "Мобила", "Дата рождения", "Сайт");
                        $nmen1 = array(1 => "imname", "live", "about", "icq", "mail", "mibila", "Дата рождения ", "www");
                        if (!empty($nmenu))
                        {
                            $nmenu1 = explode(",", $nmenu);
                            foreach ($nmenu1 as $v)
                            {

                                if ($v != 7 && $v != 5 && $v != 8)
                                {
                                    $dus = $nmen1[$v];
                                    if (!empty($udat[$dus]))
                                    {
                                        echo "$nmen[$v]: $udat[$dus]<br/>";
                                    }
                                }

                                if ($v == 5)
                                {
                                    if (!empty($udat[mail]))
                                    {
                                        echo "$nmen[$v]: ";
                                        if ($udat[mailvis] == 1)
                                        {
                                            echo "$udat[mail]<br/>";
                                        } else
                                        {
                                            echo "скрыт<br/>";
                                        }
                                    }
                                }
                                if ($v == 8)
                                {
                                    if (!empty($udat[www]))
                                    {
                                        $sit = str_replace("http://", "", $udat[www]);
                                        echo "$nmen[$v]: <a href='$udat[www]'>$sit</a><br/>";
                                    }
                                }
                                if ($v == 7)
                                {
                                    if ((!empty($udat[dayb])) && (!empty($udat[monthb])))
                                    {
                                        $mnt = $udat[monthb];
                                        echo "$nmen[$v]: $udat[dayb] $mesyac[$mnt]<br/>";
                                    }
                                }
                            }
                        }
                        ####ник меню
                        ########
                        ############

                        if ($dostkmod == 1)
                        {
                            echo "<a href='../" . $admp . "/zaban.php?user=" . $udat[id] . "&amp;forum&amp;id=" . $id . "'>Банить</a><br/>";
                        } elseif ($dostfmod == 1)
                        {
                            echo "<a href='../" . $admp . "/zaban.php?user=" . $udat[id] . "&amp;forum&amp;id=" . $id . "'>Пнуть</a><br/>";
                        }
                        echo "<a href='../str/anketa.php?user=" . $udat[id] . "'>Подробнее...</a><br/>";
                        $contacts = mysql_query("select * from `privat` where me='$login' and cont='" . $udat['name'] . "';");
                        $conts = mysql_num_rows($contacts);
                        if ($conts != 1)
                        {
                            echo "<a href='../str/cont.php?act=edit&amp;nik=" . $udat[name] . "&amp;add=1'>Добавить в контакты</a><br/>";
                        } else
                        {
                            echo "<a href='../str/cont.php?act=edit&amp;nik=" . $udat[name] . "'>Удалить из контактов</a><br/>";
                        }
                        $igns = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $udat['name'] . "';");
                        $ignss = mysql_num_rows($igns);
                        if ($igns != 1)
                        {
                            echo "<a href='../str/ignor.php?act=edit&amp;nik=" . $udat[name] . "&amp;add=1'>Добавить в игнор</a><br/>";
                        } else
                        {
                            echo "<a href='../str/ignor.php?act=edit&amp;nik=" . $udat[name] . "'>Удалить из игнора</a><br/>";
                        }
                        echo "<a href='../str/pradd.php?act=write&amp;adr=" . $udat[id] . "'>Написать в приват</a><br/>";
                    }
                    if ($th1[edit] == 1)
                    {
                        echo "Вы не можете писать в закрытую тему<br/><a href='?id=" . $th . "'>В тему</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    ##
                    if (isset($_GET['cyt']))
                    {
                        if (($datauser[postforum] == "" || $datauser[postforum] == 0))
                        {
                            if (!isset($_GET['yes']))
                            {
                                include ("../pages/forum.txt");

                                echo "<a href='?act=say&amp;id=" . $id . "&amp;yes&amp;cyt'>Согласен</a>|<a href='?id=" . $type1[refid] . "'>Не согласен</a><br/>";
                                require ("../incfiles/end.php");
                                exit;
                            }
                        }
                        echo "Ответ с цитированием в тему <font color='" . $cntem . "'>$th1[text]</font><br/> Пост <font color='" . $conik . "'>$type1[from]</font>  :<br/>
&quot;$qt&quot;<br/><a href='?act=say&amp;id=" . $id . "&amp;edit'>(Редактировать)</a><br/>Ответ(max. 500):<br/><form action='?act=say&amp;id=" . $id . "&amp;cyt' method='post' enctype='multipart/form-data'>";
                    } elseif (isset($_GET['edit']))
                    {
                        $qt = str_replace("<br/>", "\r\n", $qt);
                        echo "Ответ с цитированием в тему <font color='" . $cntem . "'>$th1[text]</font><br/> Пост <font color='" . $comik . "'>$type1[from]</font>  :<br/><form action='?act=say&amp;id=" . $id .
                            "&amp;cyt' method='post' enctype='multipart/form-data'><textarea cols='40' title='Редактирование цитаты' rows='3' name='citata'>$qt</textarea><br/>Ответ(max. 500):<br/>";
                    } else
                    {
                        if (($datauser[postforum] == "" || $datauser[postforum] == 0))
                        {
                            if (!isset($_GET['yes']))
                            {
                                include ("../pages/forum.txt");

                                echo "<a href='?act=say&amp;id=" . $id . "&amp;yes'>Согласен</a>|<a href='?id=" . $type1[refid] . "'>Не согласен</a><br/>";
                                require ("../incfiles/end.php");
                                exit;
                            }
                        }


                        echo "Добавление сообщения в тему <font color='" . $cntem . "'>$th1[text]</font> для <font color='" . $conik . "'>$type1[from]</font>(max. 500):<br/><form action='?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'>";
                    }
                    echo "<textarea cols='40' rows='3' title='Введите ответ' name='msg'></textarea><br/>Прикрепить файл(max. $flsz kb):<br />
         <input type='file' name='fail'/><hr/>
Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><hr/>";
                    if ($offtr != 1)
                    {
                        echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
                    }
                    echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
                }
                echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
                echo "<a href='?id=" . $type1[refid] . "'>Назад</a><br/>";
                break;
                ###
            default:
                echo "Ошибка:тема удалена или не существует!<br/>&#187;<a href='?'>В форум</a><br/>";
                break;
        }

        break;
        ############################################3
    case "post":
        if (empty($_GET['id']))
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        $id = intval($_GET['id']);
        $s = intval($_GET['s']);
        if (!empty($_GET['page']))
        {
            $page = intval($_GET['page']);
        } else
        {
            $page = 1;
        }
        $typ = mysql_query("select * from `forum` where id='" . $id . "';");
        $ms = mysql_fetch_array($typ);
        if ($ms[type] != "m")
        {
            echo "Ошибка!<br/><a href='?'>В форум</a><br/>";
            require ("../incfiles/end.php");
            exit;
        }
        echo "<b>$ms[from]</b><br/>";
        ;
        $tx = $ms[text];
        $tx = utfwin($tx);
        $strrpos = strrpos($tx, " ");
        $pages = 1;
        $t_si = 0;
        $simvol = 2000;
        while ($t_si < $strrpos)
        {
            $string = substr($tx, $t_si, $simvol);
            $t_ki = strrpos($string, " ");
            $m_sim = $t_ki;
            $kol = strlen($string);
            if ($kol < $simvol)
            {
                $strings[$pages] = $string;
            } else
            {
                $strings[$pages] = substr($string, 0, $m_sim);
            }
            $t_si = $t_ki + $t_si;
            if ($page == $pages)
            {
                $page_text = $strings[$pages];
            }
            if ($strings[$pages] == "")
            {
                //$t_si=
                $strrpos++;
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
        $trans1 = array("– ", "«", "»", "“", "”", "…", "—");
        $trans2 = array(" - ", "\"", "\"", "\"", "\"", "...", "-");
        $page_text = str_replace($trans1, $trans2, $page_text);
        $ntext = "$page_text ";
        $substr_count = substr_count($ntext, "http://");
        $n = 1;
        $ofset = 0;
        while ($n <= $substr_count)
        {
            $pozicn = strpos($ntext, "http://", $ofset);
            $pozick = strpos($ntext, " ", $pozicn);
            $sim = $pozick - $pozicn;
            if ($sim == 0)
            {
                $sim = "";
            }
            if ($sim != "")
            {
                $sim = $sim + 1;
            }
            $zamenstr = substr($ntext, $pozicn, $sim);
            $zamenstr = trim($zamenstr);
            $ntext = str_replace($zamenstr, "<a href=\"$zamenstr\">$zamenstr</a>", $ntext);
            $ofset = $pozick + $pozick1 + 19;
            $n = $n + 1;
        }
        $page_text = $ntext;
        $pages = $pages - 1;
        $page_text = winutf($page_text);
        $page_text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div style=\'background:' . $ccfon . ';color:' . $cctx . ';\'>\1<br/></div>', $page_text);
        $page_text = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $page_text);
        $page_text = eregi_replace("\\[l\\]([[:alnum:]_=/:-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $page_text);
        if (stristr($page_text, "<a href="))
        {
            $page_text = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                "<a href='\\1\\3'>\\3</a>", $page_text);
        } else
        {
            $page_text = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $page_text);
        }
        $uz = @mysql_query("select * from `users` where name='" . $ms[from] . "';");
        $mass1 = @mysql_fetch_array($uz);
        if ($offsm != 1 && $offgr != 1)
        {
            $page_text = smiles($page_text);
            $page_text = smilescat($page_text);

            if ($ms[from] == nickadmina || $ms[from] == nickadmina2 || $mass1[rights] >= 1)
            {
                $tekst = smilesadm($page_text);
            }
        } else
        {
            $tekst = $page_text;
        }
        print "$page_text";
        $next = $page + 1;
        $prev = $page - 1;

        if ($pages > 1)
        {
            echo "</div><div class='a'>";
            if ($offpg != 1)
            {
                echo "Страницы:<br/>";
            } else
            {
                echo "Страниц: $pages<br/>";
            }


            if ($page > 1)
            {
                print " <a href=\"index.php?act=post&amp;id=$id&amp;s=$s&amp;page=$prev\">&lt;&lt;</a> ";
            }
            if ($offpg != 1)
            {

                if ($page > 1)
                {
                    print "<a href=\"index.php?act=post&amp;id=$id&amp;s=$s&amp;page=1\">1</a> ";
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
                    echo ' <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) .
                        '</a> .. <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) . '</a> <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) .
                        '</a> .. ';
                } elseif ($page > 7)
                {
                    echo ' <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                }


                if ($prev > 1)
                {
                    print "<a href=\"index.php?act=post&amp;id=$id&amp;s=$s&amp;page=$prev\">$prev</a> ";
                }
                print "<b>$page</b> ";
                if ($next < $pages)
                {
                    print "<a href=\"index.php?act=post&amp;id=$id&amp;s=$s&amp;page=$next\">$next</a> ";
                }
                if ($page2 > 12)
                {
                    echo ' .. <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) .
                        '</a> .. <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($paa3) . '">' . ($paa3) . '</a> <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                } elseif ($page2 > 6)
                {
                    echo ' .. <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="index.php?act=post&amp;id=' . $id . '&amp;s=' . $s . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                }
                if ($next < ($pages - 1))
                {
                    print " .. ";
                }
                if ($page < $pages)
                {
                    print "<a href=\"index.php?act=post&amp;id=$id&amp;s=$s&amp;page=$pages\">$pages</a> ";
                }
            } else
            {
                echo "<b>[$page]</b>";
            }


            if ($page < $pages)
            {
                print " <a href=\"index.php?act=post&amp;id=$id&amp;s=$s&amp;page=$next\">&gt;&gt;</a>";
            }
            echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id . "'/><input type='hidden' name='s' value='" . $s .
                "'/><input type='hidden' name='act' value='post'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
        }

        echo "<br/>";
        echo "</div><div class='a'>";
        $q5 = mysql_query("select * from `forum` where type='t' and id='" . $ms[refid] . "';");
        $them = mysql_fetch_array($q5);
        $q3 = mysql_query("select * from `forum` where type='r' and id='" . $them[refid] . "';");
        $razd = mysql_fetch_array($q3);
        $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
        $frm = mysql_fetch_array($q4);
        echo "<div class='e'>&#187;<a href='?id=" . $ms[refid] . "&amp;page=" . $s . "'>$them[text]</a><br/>";
        echo "&#187;<a href='?id=" . $type1[refid] . "'>$razd[text]</a><br/>";
        echo "&#187;<a href='?id=" . $razd[refid] . "'>$frm[text]</a><br/>";
        echo "&#187;<a href='?'>В форум</a></div>";
        echo "</div><div class='a'>";


        break;
        ###############################
    default:


        if (empty($_SESSION['pid']))
        {
            if (isset($_GET['newup']))
            {
                $_SESSION['uppost'] = 1;
            }
            if (isset($_GET['newdown']))
            {
                $_SESSION['uppost'] = 0;
            }
        }
        if (!empty($_SESSION['pid']))
        {
            echo "Новые:";
            $lp = mysql_query("select * from `forum` where type='t' and moder='1' and close!='1';");
            $knt = 0;
            while ($arrt = mysql_fetch_array($lp))
            {
                $q3 = mysql_query("select * from `forum` where type='r' and id='" . $arrt[refid] . "';");
                $q4 = mysql_fetch_array($q3);
                $rz = mysql_query("select * from `forum` where type='n' and refid='" . $q4[refid] . "' and `from`='" . $login . "';");
                $np = mysql_query("select * from `forum` where type='l' and time>='" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
                if ((mysql_num_rows($np)) != 1 && (mysql_num_rows($rz)) != 1)
                {
                    $knt = $knt + 1;
                }
            }
            if ($knt != 0)
            {
                $knt = "<a href='new.php'>$knt</a>";
            }
            echo "$knt<br/>";
        } else
        {
            echo "<a href='new.php'>10 новых</a><br/>";
        }
        if ($dostfmod == 1)
        {
            $fm = mysql_query("select * from `forum` where type='t' and moder!='1';");
            $fm1 = mysql_num_rows($fm);
            if ($fm1 != 0)
            {
                echo "Модерацию ожидают <a href='index.php?act=fmoder'>$fm1</a> тем<br/>";
            }
        }
        if (empty($_GET['id']))
        {

            echo "<b>Все форумы</b><hr/>";
            $q = mysql_query("select * from `forum` where type='f' order by realid ;");
            while ($mass = mysql_fetch_array($q))
            {
                $colraz = mysql_query("select * from `forum` where type='r' and refid='" . $mass[id] . "';");
                $colraz1 = mysql_num_rows($colraz);
                echo "<a href='?id=" . $mass[id] . "'><font color='" . $cntem . "'>$mass[text]</font></a> <font color='" . $ccolp . "'>[$colraz1]</font><br/>";
            }
            echo "<hr/>";
            if (!empty($_SESSION['pid']))
            {
                echo '<a href="who.php">Кто в форуме(' . wfrm() . ')</a><br/>';
            }
            echo "<a href='search.php'>Поиск по форуму</a><br/>";
            echo "<a href='index.php?act=faq'>FAQ</a><br/>";
            echo "<a href='../str/usset.php?act=forum'>Настройки форума</a><br/>";
            echo "<a href='index.php?act=read'>Правила форума</a><br/>";
        }
        if (!empty($_GET['id']))
        {
            $id = intval(check($_GET['id']));

            $type = mysql_query("select * from `forum` where id= '" . $id . "';");
            $type1 = mysql_fetch_array($type);
            $tip = $type1[type];
            switch ($tip)
            {
                    ####
                case "f":
                    echo "<b>$type1[text]</b><hr/>";


                    $q1 = mysql_query("select * from `forum` where type='r' and refid='" . $id . "'  order by realid ;");
                    $colraz2 = mysql_num_rows($q1);
                    $i = 0;
                    while ($mass1 = mysql_fetch_array($q1))
                    {
                        $coltem = mysql_query("select * from `forum` where type='t' and moder='1' and refid='" . $mass1[id] . "' order by time desc;");
                        $coltem1 = mysql_num_rows($coltem);
                        $clm = 0;
                        while ($arr = mysql_fetch_array($coltem))
                        {
                            $vpp[] = $arr[time];
                            $colmes = mysql_query("select * from `forum` where type='m' and refid='" . $arr[id] . "' order by time desc;");
                            $colmes1 = mysql_num_rows($colmes);
                            $clm = $clm + $colmes1;
                        }
                        $posl = $vpp[0];
                        $vpp = array();
                        $d = $i / 2;
                        $d1 = ceil($d);
                        $d2 = $d1 - $d;
                        $d3 = ceil($d2);
                        if ($d3 == 0)
                        {
                            $div = "<div class='b'>";
                        } else
                        {
                            $div = "<div class='c'>";
                        }


                        echo "$div<a href='?id=$mass1[id]'><font color='" . $cntem . "'>$mass1[text]</font></a>";
                        if ($coltem1 > 0)
                        {
                            echo " <font color='" . $ccolp . "'>[$coltem1/$clm]</font><br/><font color='" . $dtim . "'>(" . date("H:i /d.m.y", $posl) . ")</font>";
                        }
                        echo "</div>";
                        ++$i;
                    }
                    echo "<hr/><a href='?'>В форум</a><br/>";
                    break;
                    ####
                case "r":


                    if (((empty($_SESSION['pid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['pid'])) && $upfp == 1))
                    {
                        if ($dostsadm == 1)
                        {
                            $q1 = mysql_query("select * from `forum` where type='t' and refid='" . $id . "' and moder='1'  order by vip desc,time desc ;");
                        } else
                        {
                            $q1 = mysql_query("select * from `forum` where type='t' and close!='1' and moder='1' and refid='" . $id . "'  order by vip desc,time desc ;");
                        }
                    } else
                    {
                        if ($dostsadm == 1)
                        {
                            $q1 = mysql_query("select * from `forum` where type='t' and refid='" . $id . "' and moder='1'  order by vip desc,time ;");
                        } else
                        {
                            $q1 = mysql_query("select * from `forum` where type='t' and close!='1' and moder='1' and refid='" . $id . "'  order by vip desc,time ;");
                        }
                    }
                    $coltem = mysql_num_rows($q1);
                    echo "<b><font color='" . $cntem . "'>$type1[text]</font></b><br/><font color='" . $ccolp . "'>Тем в разделе: $coltem</font><br/>";
                    if (!empty($_SESSION['pid']))
                    {
                        echo "<a href='index.php?act=nt&amp;id=" . $id . "'>Новая тема</a>";
                    }
                    echo "<hr/>";
                    ##
                    if (empty($_GET['page']))
                    {
                        $page = 1;
                    } else
                    {
                        $page = intval($_GET['page']);
                    }
                    $start = $page * $kmess - $kmess;
                    if ($coltem < $start + $kmess)
                    {
                        $end = $coltem;
                    } else
                    {
                        $end = $start + $kmess;
                    }
                    ##
                    while ($mass = mysql_fetch_array($q1))
                    {
                        $colmes = mysql_query("select * from `forum` where type='m' and close!='1' and refid='" . $mass[id] . "'order by time desc;");
                        $pp = 0;
                        while ($nik = mysql_fetch_array($colmes))
                        {
                            if ($pp < 1)
                            {
                                $idnik = $nik['id'];
                            }
                            ++$pp;
                        }
                        $colmes1 = mysql_num_rows($colmes) - 1;
                        if ($colmes1 < 0)
                        {
                            $colmes1 = 0;
                        }
                        $nick = mysql_query("select * from `forum` where type='m' and id='" . $idnik . "';");
                        $nam = mysql_fetch_array($nick);
                        if ($i >= $start && $i < $end)
                        {
                            $d = $i / 2;
                            $d1 = ceil($d);
                            $d2 = $d1 - $d;
                            $d3 = ceil($d2);
                            if ($d3 == 0)
                            {
                                $div = "<div class='b'>";
                            } else
                            {
                                $div = "<div class='c'>";
                            }
                            echo "$div";
                            if ($mass[vip] == 1)
                            {
                                echo "<img src='../images/pt.gif' alt=''/>";
                            } elseif ($mass[edit] == 1)
                            {
                                echo "<img src='../images/tz.gif' alt=''/>";
                            } else
                            {
                                $np = mysql_query("select * from `forum` where type='l' and time>='" . $mass[time] . "' and refid='" . $mass[id] . "' and `from`='" . $login . "';");
                                $np1 = mysql_num_rows($np);
                                if ($np1 == 0)
                                {
                                    echo "<img src='../images/np.gif' alt=''/>";
                                } else
                                {
                                    echo "<img src='../images/op.gif' alt=''/>";
                                }
                            }


                            echo "<a href='?id=$mass[id]'><font color='" . $cntem . "'>$mass[text]</font></a><font color='" . $ccolp . "'> [$colmes1]</font><br/>";
                            if ($mass[close] == 1)
                            {
                                echo "<font color='" . $cdinf . "'>Тема удалена!</font><br/>";
                            }
                            echo "<font color='" . $cdtim . "'>(" . date("H:i /d.m.y", $mass[time]) . ")</font><br/><font color='" . $cssip . "'>[$mass[from]</font>";
                            if (!empty($nam[from]))
                            {
                                echo "<font color='" . $cssip . "'>/$nam[from]</font>";
                            }
                            echo "<font color='" . $cssip . "'>]</font></div>";
                        }
                        ++$i;
                    }
                    ###
                    if ($coltem > $kmess)
                    {
                        echo "<hr/>";


                        $ba = ceil($coltem / $kmess);
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
                            echo '<a href="index.php?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                        }
                        if ($offpg != 1)
                        {
                            if ($asd < $coltem && $asd > 0)
                            {
                                echo ' <a href="index.php?id=' . $id . '&amp;page=1&amp;">1</a> .. ';
                            }
                            $page2 = $ba - $page;
                            $pa = ceil($page / 2);
                            $paa = ceil($page / 3);
                            $pa2 = $page + floor($page2 / 2);
                            $paa2 = $page + floor($page2 / 3);
                            $paa3 = $page + (floor($page2 / 3) * 2);
                            if ($page > 13)
                            {
                                echo ' <a href="?id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="?id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="?id=' . $id . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                                    '</a> <a href="?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                            } elseif ($page > 7)
                            {
                                echo ' <a href="?id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                            }
                            for ($i = $asd; $i < $asd2; )
                            {
                                if ($i < $coltem && $i >= 0)
                                {
                                    $ii = floor(1 + $i / $kmess);

                                    if ($start == $i)
                                    {
                                        echo " <b>$ii</b>";
                                    } else
                                    {
                                        echo ' <a href="index.php?id=' . $id . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                                    }
                                }
                                $i = $i + $kmess;
                            }
                            if ($page2 > 12)
                            {
                                echo ' .. <a href="?id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="?id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="?id=' . $id . '&amp;page=' . ($paa3) . '">' . ($paa3) .
                                    '</a> <a href="?id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                            } elseif ($page2 > 6)
                            {
                                echo ' .. <a href="?id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                            }
                            if ($asd2 < $coltem)
                            {
                                echo ' .. <a href="index.php?id=' . $id . '&amp;page=' . $ba . '">' . $ba . '</a>';
                            }
                        } else
                        {
                            echo "<b>[$page]</b>";
                        }
                        if ($coltem > $start + $kmess)
                        {
                            echo ' <a href="index.php?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                        }
                        echo "<form action='index.php'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                            "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form>";
                    }
                    ###


                    $forum = mysql_query("select * from `forum` where type='f' and id='" . $type1[refid] . "';");
                    $forum1 = mysql_fetch_array($forum);
                    echo "<hr/>&#187;<a href='?id=" . $type1[refid] . "'>$forum1[text]</a><br/>";
                    echo "&#187;<a href='?'>В форум</a><br/>";
                    break;

                    ########
                case "t":
                    //блок, фиксирующий факт прочтения темы
                    if (!empty($_SESSION['pid']))
                    {
                        $np = mysql_query("select * from `forum` where type='l' and refid='" . $id . "' and `from`='" . $login . "';");
                        $np1 = mysql_num_rows($np);
                        if ($np1 == 0)
                        {
                            mysql_query("insert into `forum` values(0,'" . $id . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','');");
                        } else
                        {
                            $np2 = mysql_fetch_array($np);
                            mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2[id] . "';");
                        }
                    }
                    //

                    if ($dostsadm != 1 && $type1[close] == 1)
                    {

                        echo "<font color='" . $cdinf . "'>Тема удалена!</font><br/><a href='?id=" . $type1[refid] . "'>В раздел</a><br/>";
                        require ("../incfiles/end.php");
                        exit;
                    }
                    $id = intval(check($_GET['id']));
                    if (((empty($_SESSION['pid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['pid'])) && $upfp == 1))
                    {
                        if ($dostsadm == 1)
                        {
                            $q1 = mysql_query("select * from `forum` where type='m' and refid='" . $id . "'  order by time desc ;");
                        } else
                        {
                            $q1 = mysql_query("select * from `forum` where type='m' and close!='1' and refid='" . $id . "'  order by time desc ;");
                        }
                    } else
                    {
                        if ($dostsadm == 1)
                        {
                            $q1 = mysql_query("select * from `forum` where type='m' and refid='" . $id . "'  order by time ;");
                        } else
                        {
                            $q1 = mysql_query("select * from `forum` where type='m' and close!='1' and refid='" . $id . "'  order by time ;");
                        }
                    }
                    $colmes = mysql_num_rows($q1);
                    echo "<b><font color='" . $cntem . "'>$type1[text]</font></b><br/><font color='" . $ccolp . "'>Сообщений: $colmes</font><br/>";
                    if ($type1[edit] == 1)
                    {
                        echo "<b><font color='" . $cdinf . "'>Тема закрыта</font></b><br/>";
                    }

                    if ($type1[edit] != 1 && $_SESSION['pid'] != "")
                    {
                        if ($farea == 1 && $datauser[postforum] >= 1)
                        {
                            echo "<div class='e'>Написать<br/><form action='?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
                            if (!eregi("Opera/8.01", $agent))
                            {


                                echo "Прикрепить файл(max. $flsz kb):<br />
         <input type='file' name='fail'/><br/>";
                            } else
                            {

                                echo "Прикрепить файл(Opera Mini):<br/><input name='fail1' value =''/>&nbsp;<br/>
<a href='op:fileselect'>Выбрать файл</a><br/>";
                            }
                            if ($offtr != 1)
                            {
                                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                            }
                            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form></div>";
                        } else
                        {
                            echo "<a href='?act=say&amp;id=" . $id . "'>Написать</a>";
                        }
                    }
                    echo "</div><div class='a'>";
                    echo "<div id='up'><a href='#down'>Вниз</a></div>";
                    ##
                    if (empty($_GET['page']))
                    {
                        $page = 1;
                    } else
                    {
                        $page = intval($_GET['page']);
                    }
                    $start = $page * $kmess - $kmess;
                    if ($colmes < $start + $kmess)
                    {
                        $end = $colmes;
                    } else
                    {
                        $end = $start + $kmess;
                    }
                    ##
                    while ($mass = mysql_fetch_array($q1))
                    {

                        if ($i >= $start && $i < $end)
                        {
                            $d = $i / 2;
                            $d1 = ceil($d);
                            $d2 = $d1 - $d;
                            $d3 = ceil($d2);
                            if ($d3 == 0)
                            {
                                $div = "<div class='b'>";
                            } else
                            {
                                $div = "<div class='c'>";
                            }
                            $uz = @mysql_query("select * from `users` where name='" . $mass[from] . "';");
                            $mass1 = @mysql_fetch_array($uz);
                            echo "<div class='a'>$div";
                            if ($pfon == 1)
                            {
                                echo "<div style='background:" . $cpfon . ";'>";
                            }
                            switch ($mass1[sex])
                            {
                                case "m":
                                    echo "<img src='../images/m.gif' alt=''/>";
                                    break;
                                case "zh":
                                    echo "<img src='../images/f.gif' alt=''/>";
                                    break;
                            }
                            if ((!empty($_SESSION['pid'])) && ($_SESSION['pid'] != $mass1[id]))
                            {
                                echo "<a href='?act=say&amp;id=" . $mass[id] . "'><b><font color='" . $conik . "'>$mass[from]</font></b></a> <a href='?act=say&amp;id=" . $mass[id] . "&amp;cyt'><font color='" . $conik . "'> [ц]</font></a>";
                            } else
                            {
                                echo "<b><font color='" . $csnik . "'>$mass[from]</font></b>";
                            }
                            $vrp = $mass[time] + $sdvig * 3600;
                            $vr = date("d.m.Y / H:i", $vrp);

                            switch ($mass1[rights])
                            {
                                case 7:
                                    echo "<font color='" . $cadms . "'> Adm </font>";
                                    break;
                                case 6:
                                    echo "<font color='" . $cadms . "'> Smd </font>";
                                    break;
                                case 3:
                                    echo "<font color='" . $cadms . "'> Mod </font>";
                                    break;
                                case 1:
                                    echo "<font color='" . $cadms . "'> Kil </font>";
                                    break;
                            }
                            $ontime = $mass1[lastdate];
                            $ontime2 = $ontime + 300;
                            if ($realtime > $ontime2)
                            {
                                echo "<font color='" . $coffs . "'> [Off]</font>";
                            } else
                            {
                                echo "<font color='" . $cons . "'> [ON]</font>";
                            }
                            if ($mass1[dayb] == $day && $mass1[monthb] == $mon)
                            {
                                echo "<font color='" . $cdinf . "'>!!!</font><br/>";
                            }
                            echo "<font color='" . $cdtim . "'>($vr)</font><br/>";
                            if ($mass[close] == 1)
                            {
                                echo "<font color='" . $cdinf . "'>Пост удалён!</font><br/>";
                            }
                            if ($pfon == 1)
                            {
                                echo "</div>";
                            }
                            if (!empty($mass[to]))
                            {
                                echo "$mass[to], ";
                            }

                            $lpost = utfwin($mass[text]);
                            $lpost1 = strlen($lpost);

                            $mass[text] = preg_replace('#\[c\](.*?)\[/c\]#si', '<div style=\'background:' . $ccfon . ';color:' . $cctx . ';\'>\1<br/></div>', $mass[text]);
                            $mass[text] = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $mass[text]);

                            $mass[text] = eregi_replace("\\[l\\]([[:alnum:]_=/:-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+.&_=/;%]*)?)?)\\[l/\\]((.*)?)\\[/l\\]", "<a href='http://\\1'>\\6</a>", $mass[text]);
                            if (stristr($mass[text], "<a href="))
                            {
                                $mass[text] = eregi_replace("\\<a href\\='((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)'>[[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)</a>",
                                    "<a href='\\1\\3'>\\3</a>", $mass[text]);
                            } else
                            {
                                $mass[text] = eregi_replace("((https?|ftp)://)([[:alnum:]_=/-]+(\\.[[:alnum:]_=/-]+)*(/[[:alnum:]+&._=/~%]*(\\?[[:alnum:]?+&_=/;%]*)?)?)", "<a href='\\1\\3'>\\3</a>", $mass[text]);
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
                            if ($lpost1 >= 500)
                            {
                                $tekst = utfwin($tekst);

                                $tekst = substr($tekst, 0, 500);
                                if ($o >= 496)
                                {
                                    $o = strrpos($tekst, "<");
                                    $tekst = substr($tekst, 0, $o);
                                }
                                $tekst = winutf($tekst);
                                $tekst = "$tekst...";
                            }


                            echo "$tekst<br/>";
                            if ($mass[kedit] >= 1)
                            {
                                $diz = $mass[tedit] + $sdvig * 3600;
                                $dizm = date("d.m /H:i", $diz);
                                echo "<font color='" . $cdinf . "'>Посл. изм. <b>$mass[edit]</b>  ($dizm) ,всего изм.:<b> $mass[kedit]</b></font><br/>";
                            }
                            if ($lpost1 >= 500)
                            {
                                echo "<a href='index.php?act=post&amp;s=" . $page . "&amp;id=" . $mass[id] . "'>Весь пост</a><br/>";
                            }


                            if (!empty($mass[attach]))
                            {
                                $fls = filesize("./files/$mass[attach]");
                                $fls = round($fls / 1024, 2);
                                echo "<font color='" . $cdinf . "'>Прикреплённый файл: <a href='index.php?act=file&amp;id=" . $mass[id] . "'>$mass[attach]</a> ($fls кб.)</font><br/>";
                            }


                            $lp = mysql_query("select * from `forum` where type='m' and refid='" . $id . "'  order by time desc ;");
                            while ($arr = mysql_fetch_array($lp))
                            {

                                $idpp[] = $arr[id];
                            }
                            $idpr = $idpp[0];
                            $tpp = $realtime - 300;
                            $lp1 = mysql_query("select * from `forum` where id='" . $idpr . "';");
                            $arr1 = mysql_fetch_array($lp1);
                            if (($dostfmod == 1) || (($arr1[from] == $login) && ($arr1[id] == $mass[id]) && ($mass[time] > $tpp)))
                            {
                                echo "<a href='?act=editpost&amp;id=" . $mass[id] . "'>Изменить</a>";
                            }
                            if ($dostfmod == 1)
                            {
                                echo "|<a href='?act=delpost&amp;id=" . $mass[id] . "'>Удалить</a><br/>";
                                echo "$mass[ip] - $mass[soft]<br/>";
                            }
                            echo "</div></div>";
                        }
                        ++$i;
                    }
                    ###
                    echo "<div id='down'><a href='#up'>Вверх</a></div>";
                    if ($colmes > $kmess)
                    {
                        echo "</div><div class='a'><div class='e'>";


                        $ba = ceil($colmes / $kmess);
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
                            echo '<a href="?id=' . $id . '&amp;page=' . ($page - 1) . '">&lt;&lt;</a> ';
                        }
                        if ($offpg != 1)
                        {
                            if ($asd < $colmes && $asd > 0)
                            {
                                echo ' <a href="?id=' . $id . '&amp;page=1&amp;">1</a> .. ';
                            }
                            $page2 = $ba - $page;
                            $pa = ceil($page / 2);
                            $paa = ceil($page / 3);
                            $pa2 = $page + floor($page2 / 2);
                            $paa2 = $page + floor($page2 / 3);
                            $paa3 = $page + (floor($page2 / 3) * 2);
                            if ($page > 13)
                            {
                                echo ' <a href="?id=' . $id . '&amp;page=' . $paa . '">' . $paa . '</a> <a href="?id=' . $id . '&amp;page=' . ($paa + 1) . '">' . ($paa + 1) . '</a> .. <a href="?id=' . $id . '&amp;page=' . ($paa * 2) . '">' . ($paa * 2) .
                                    '</a> <a href="?id=' . $id . '&amp;page=' . ($paa * 2 + 1) . '">' . ($paa * 2 + 1) . '</a> .. ';
                            } elseif ($page > 7)
                            {
                                echo ' <a href="?id=' . $id . '&amp;page=' . $pa . '">' . $pa . '</a> <a href="?id=' . $id . '&amp;page=' . ($pa + 1) . '">' . ($pa + 1) . '</a> .. ';
                            }
                            for ($i = $asd; $i < $asd2; )
                            {
                                if ($i < $colmes && $i >= 0)
                                {
                                    $ii = floor(1 + $i / $kmess);

                                    if ($start == $i)
                                    {
                                        echo " <b>$ii</b>";
                                    } else
                                    {
                                        echo ' <a href="?id=' . $id . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                                    }
                                }
                                $i = $i + $kmess;
                            }
                            if ($page2 > 12)
                            {
                                echo ' .. <a href="?id=' . $id . '&amp;page=' . $paa2 . '">' . $paa2 . '</a> <a href="?id=' . $id . '&amp;page=' . ($paa2 + 1) . '">' . ($paa2 + 1) . '</a> .. <a href="?id=' . $id . '&amp;page=' . ($paa3) . '">' . ($paa3) .
                                    '</a> <a href="?id=' . $id . '&amp;page=' . ($paa3 + 1) . '">' . ($paa3 + 1) . '</a> ';
                            } elseif ($page2 > 6)
                            {
                                echo ' .. <a href="?id=' . $id . '&amp;page=' . $pa2 . '">' . $pa2 . '</a> <a href="?id=' . $id . '&amp;page=' . ($pa2 + 1) . '">' . ($pa2 + 1) . '</a> ';
                            }
                            if ($asd2 < $colmes)
                            {
                                echo ' .. <a href="?id=' . $id . '&amp;page=' . $ba . '">' . $ba . '</a>';
                            }
                        } else
                        {
                            echo "<b>[$page]</b>";
                        }


                        if ($colmes > $start + $kmess)
                        {
                            echo ' <a href="?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                        }
                        echo "<form action='?'>Перейти к странице:<br/><input type='hidden' name='id' value='" . $id .
                            "'/><input type='text' name='page' title='Введите номер страницы'/><br/><input type='submit' title='Нажмите для перехода' value='Go!'/></form></div>";
                    }
                    ###

                    if ($dostfmod == 1)
                    {
                        echo "</div><div class='a'>";
                        if ($type1[moder] != 1)
                        {
                            echo "<a href='?act=fmoder&amp;id=" . $id . "'>Принять тему</a><br/>";
                        }
                        echo "<a href='?act=ren&amp;id=" . $id . "'>Переименовать тему</a><br/>";
                        if ($type1[edit] == 1)
                        {
                            echo "<a href='?act=close&amp;id=" . $id . "'>Открыть тему</a><br/>";
                        } else
                        {
                            echo "<a href='?act=close&amp;id=" . $id . "&amp;closed'>Закрыть тему</a><br/>";
                        }
                        echo "<a href='?act=deltema&amp;id=" . $id . "'>Удалить тему</a><br/>";
                        if ($type1[vip] == 1)
                        {
                            echo "<a href='?act=vip&amp;id=" . $id . "'>Открепить тему</a>";
                        } else
                        {
                            echo "<a href='?act=vip&amp;id=" . $id . "&amp;vip'>Закрепить тему</a>";
                        }
                        echo "<br/><a href='index.php?act=per&amp;id=" . $id . "'>Переместить тему</a>";
                    }
                    echo "</div><div class='a'>";
                    $q3 = mysql_query("select * from `forum` where type='r' and id='" . $type1[refid] . "';");
                    $razd = mysql_fetch_array($q3);
                    $q4 = mysql_query("select * from `forum` where type='f' and id='" . $razd[refid] . "';");
                    $frm = mysql_fetch_array($q4);
                    echo "<div class='e'>&#187;<a href='?id=" . $type1[refid] . "'>$razd[text]</a><br/>";
                    echo "&#187;<a href='?id=" . $razd[refid] . "'>$frm[text]</a><br/>";
                    echo "&#187;<a href='?'>В форум</a></div>";
                    echo "</div><div class='a'>";

                    if (!empty($_SESSION['pid']))
                    {
                        echo "<a href='who.php?id=" . $id . "'>Кто здесь?(";
                        wfrm($id);
                        echo ")</a><br/>";
                    }
                    echo "<a href='?act=tema&amp;id=" . $id . "'>Скачать тему</a><br/>";


                    break;

                    #####
                default:

                    echo "Ошибка:тема удалена или не существует!<br/>&#187;<a href='?'>В форум</a><br/>";
                    break;
            }
        }
        if (empty($_SESSION['pid']))
        {
            if ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0))
            {
                echo "<a href='?id=" . $id . "&amp;page=" . $page . "&amp;newup'>Новые вверху</a><br/>";
            } else
            {
                echo "<a href='?id=" . $id . "&amp;page=" . $page . "&amp;newdown'>Новые внизу</a><br/>";
            }
        }
        if ($dostsmod == 1)
        {
            echo "<a href='../" . $admp . "/forum.php'>Управление разделами</a><br/>";
        }
        echo "<a href='index.php?act=moders&amp;id=" . $id . "'>Модераторы</a><br/>";


        break;
}
require ("../incfiles/end.php");
?>