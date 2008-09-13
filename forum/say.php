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

if (empty($_GET['id']) || !$user_id || $ban['1'] || $ban['11'])
{
    require_once ("../incfiles/head.php");
    echo "Ошибка!<br/><a href='index.php?'>В форум</a><br/>";
    require_once ("../incfiles/end.php");
    exit;
}
$id = intval($_GET['id']);
$type = mysql_query("select * from `forum` where id= '" . $id . "';");
$type1 = mysql_fetch_array($type);
$tip = $type1['type'];
switch ($tip)
{
    case "t":
        if ($type1['edit'] == 1)
        {
            require_once ("../incfiles/head.php");
            echo '<p>Вы не можете писать в закрытую тему</p><p><a href="index.php?id=' . $id . '">&lt;&lt; Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;
        }
        if (isset($_POST['submit']))
        {
            $flt = $realtime - 30;
            $af = mysql_query("select * from `forum` where type='m' and time >='" . $flt . "' and `from` = '" . check(trim($login)) . "';");
            $af1 = mysql_num_rows($af);
            if ($af1 > 0)
            {
                require_once ("../incfiles/head.php");
                echo '<p><b>Антифлуд!</b><br />Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд</p><p><a href="?id=' . $id . '">&lt;&lt; Назад</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            if (empty($_POST['msg']))
            {
                require_once ("../incfiles/head.php");
                echo '<p>Вы не ввели сообщение!</p><p><a href="index.php?act=say&amp;id=' . $id . '">&lt;&lt; Повторить</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $msg = check(trim($_POST['msg']));
            if ($_POST['msgtrans'] == 1)
            {
                $msg = trans($msg);
            }
            $agn = strtok($agn, ' ');
			mysql_query("insert into `forum` set 
			`refid`='" . $id . "',
			`type`='m',
			`time`='" . $realtime . "',
			`from`='" . $login . "',
			`ip`='" . $ipp . "',
			`soft`='" . mysql_real_escape_string($agn) . "',
			`text`='" . $msg . "';");
            $fadd = mysql_insert_id();
            mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $id . "';");
            $fpst = $datauser['postforum'] + 1;
            mysql_query("update `users` set  postforum='" . $fpst . "' where id='" . intval($_SESSION['uid']) . "';");
            $pa = mysql_query("select `id` from `forum` where type='m' and refid= '" . $id . "';");
            $pa2 = mysql_num_rows($pa);
            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                $page = 1;
            } else
            {
                $page = ceil($pa2 / $kmess);
            }
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
            $addfiles = intval($_POST['addfiles']);
            if ($addfiles == 1)
            {
                header("Location: index.php?id=$fadd&act=addfile");
            } else
            {
                header("Location: index.php?id=$id&page=$page");
            }
        } else
        {
            require_once ("../incfiles/head.php");
            if ($datauser['postforum'] == 0)
            {
                if (!isset($_GET['yes']))
                {
                    include ("../pages/forum.txt");
                    echo "<a href='index.php?act=say&amp;id=" . $id . "&amp;yes'>Согласен</a>|<a href='index.php?id=" . $id . "'>Не согласен</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
            }
            echo "Добавление сообщения в тему <font color='" . $cntem . "'>$type1[text]</font>:<br/><form action='index.php?act=say&amp;id=" . $id .
                "' method='post' enctype='multipart/form-data'><textarea cols='20' rows='3' title='Введите текст сообщения' name='msg'></textarea><br/><input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
        }
        echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
        echo "<a href='?id=" . $id . "'>Назад</a><br/>";
        break;

    case "m":
        $th = $type1['refid'];
        $th2 = mysql_query("select * from `forum` where id= '" . $th . "';");
        $th1 = mysql_fetch_array($th2);
        if (isset($_POST['submit']))
        {
            $flt = $realtime - 30;
            $af = mysql_query("select * from `forum` where type='m' and time>'" . $flt . "' and `from`= '" . $login . "';");
            $af1 = mysql_num_rows($af);
            if ($af1 != 0)
            {
                require_once ("../incfiles/head.php");
                echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 30 секунд<br/><a href='?id=" . $th . "'>В тему</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (empty($_POST['msg']))
            {
                require_once ("../incfiles/head.php");
                echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $msg = check(trim($_POST['msg']));
            if ($_POST['msgtrans'] == 1)
            {
                $msg = trans($msg);
            }
            $to = $type1['from'];
            if (isset($_GET['cyt']))
            {
                if (!empty($_POST['citata']))
                {
                    $citata = trim($_POST['citata']);
                } else
                {
                    $citata = $type1['text'];
                }
                $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
                $citata = strip_tags($citata, "<br/>");
                $citata = mb_strcut($citata, 0, 200);
                $citata = check($citata);
                $citata = str_replace("&lt;br/&gt;", "<br/>", $citata);
                $tp = date("d.m.Y/H:i", $type1['time']);
                $msg = "[c]$to($tp):&quot; $citata &quot;[/c]$msg";
                $to = "";
            }
            mysql_query("insert into `forum` values(0,'" . $th . "','m','" . $realtime . "','" . $login . "','" . $to . "','','" . $ipp . "','" . $agn . "','" . $msg . "','','','','','','','','');");
            $fadd = mysql_insert_id();
            mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $th . "';");
            if (empty($datauser['postforum']))
            {
                $fpst = 1;
            } else
            {
                $fpst = $datauser['postforum'] + 1;
            }
            mysql_query("update `users` set  postforum='" . $fpst . "' where id='" . intval($_SESSION['uid']) . "';");
            $pa = mysql_query("select * from `forum` where type='m' and refid= '" . $th . "';");
            $pa2 = mysql_num_rows($pa);

            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                $page = 1;
            } else
            {
                $page = ceil($pa2 / $kmess);
            }
            $np = mysql_query("select * from `forum` where type='l' and refid='" . $th . "' and `from`='" . $login . "';");
            $np1 = mysql_num_rows($np);
            if ($np1 == 0)
            {
                mysql_query("insert into `forum` values(0,'" . $th . "','l','" . $realtime . "','" . $login . "','','','','','','','','','','','','');");
            } else
            {
                $np2 = mysql_fetch_array($np);
                mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $np2['id'] . "';");
            }
            $addfiles = intval($_POST['addfiles']);
            if ($addfiles == 1)
            {
                header("Location: index.php?id=$fadd&act=addfile");
            } else
            {
                header("Location: index.php?id=$th&page=$page");
            }
        } else
        {
            require_once ("../incfiles/head.php");
            if (!empty($type1['to']))
            {
                $qt = "$type1[to], $type1[text]";
            } else
            {
                $qt = " $type1[text]";
            }
            $user = mysql_query("select * from `users` where name='" . $type1['from'] . "';");
            $udat = mysql_fetch_array($user);
            echo "<b><font color='" . $conik . "'>$type1[from]</font></b>";
            echo " (id: $udat[id])";
            $ontime = $udat['lastdate'];
            $ontime2 = $ontime + 300;
            if ($realtime > $ontime2)
            {
                echo "<font color='" . $coffs . "'> [Off]</font><br/>";
            } else
            {
                echo "<font color='" . $cons . "'> [ON]</font><br/>";
            }
            if ($udat['dayb'] == $day && $udat['monthb'] == $mon)
            {
                echo "<font color='" . $cdinf . "'>ИМЕНИННИК!!!</font><br/>";
            }
            switch ($udat['rights'])
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
            if (!empty($udat['status']))
            {
                $stats = $udat['status'];
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
            if (!empty($_SESSION['uid']))
            {
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
                            if (!empty($udat['mail']))
                            {
                                echo "$nmen[$v]: ";
                                if ($udat['mailvis'] == 1)
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
                            if (!empty($udat['www']))
                            {
                                $sit = str_replace("http://", "", $udat['www']);
                                echo "$nmen[$v]: <a href='$udat[www]'>$sit</a><br/>";
                            }
                        }
                        if ($v == 7)
                        {
                            if ((!empty($udat['dayb'])) && (!empty($udat['monthb'])))
                            {
                                $mnt = $udat['monthb'];
                                echo "$nmen[$v]: $udat[dayb] $mesyac[$mnt]<br/>";
                            }
                        }
                    }
                }
                if ($dostkmod == 1)
                {
                    echo '<a href="../' . $admp . '/zaban.php?do=ban&amp;id=' . $udat['id'] . '&amp;fid=' . $id . '">Банить</a><br/>';
                } elseif ($dostfmod == 1)
                {
                    echo "<a href='../" . $admp . "/zaban.php?user=" . $udat['id'] . "&amp;forum&amp;id=" . $id . "'>Пнуть</a><br/>";
                }
                echo "<a href='../str/anketa.php?user=" . $udat['id'] . "'>Подробнее...</a><br/>";
                $contacts = mysql_query("select * from `privat` where me='$login' and cont='" . $udat['name'] . "';");
                $conts = mysql_num_rows($contacts);
                if ($conts != 1)
                {
                    echo "<a href='../str/cont.php?act=edit&amp;nik=" . $udat['name'] . "&amp;add=1'>Добавить в контакты</a><br/>";
                } else
                {
                    echo "<a href='../str/cont.php?act=edit&amp;nik=" . $udat['name'] . "'>Удалить из контактов</a><br/>";
                }
                $igns = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $udat['name'] . "';");
                $ignss = mysql_num_rows($igns);
                if ($igns != 1)
                {
                    echo "<a href='../str/ignor.php?act=edit&amp;nik=" . $udat['name'] . "&amp;add=1'>Добавить в игнор</a><br/>";
                } else
                {
                    echo "<a href='../str/ignor.php?act=edit&amp;nik=" . $udat['name'] . "'>Удалить из игнора</a><br/>";
                }
                echo "<a href='../str/pradd.php?act=write&amp;adr=" . $udat['id'] . "'>Написать в приват</a><br/>";
            }
            if ($th1['edit'] == 1)
            {
                echo "Вы не можете писать в закрытую тему<br/><a href='?id=" . $th . "'>В тему</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (isset($_GET['cyt']))
            {
                if (($datauser['postforum'] == "" || $datauser['postforum'] == 0))
                {
                    if (!isset($_GET['yes']))
                    {
                        include ("../pages/forum.txt");

                        echo "<a href='?act=say&amp;id=" . $id . "&amp;yes&amp;cyt'>Согласен</a>|<a href='?id=" . $type1['refid'] . "'>Не согласен</a><br/>";
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                }
                echo "Ответ с цитированием в тему <font color='" . $cntem . "'>$th1[text]</font><br/> Пост <font color='" . $conik . "'>$type1[from]</font>  :<br/>
&quot;$qt&quot;<br/><a href='index.php?act=say&amp;id=" . $id . "&amp;edit'>(Редактировать)</a><br/>Ответ:<br/><form action='index.php?act=say&amp;id=" . $id . "&amp;cyt' method='post' enctype='multipart/form-data'>";
            } elseif (isset($_GET['edit']))
            {
                $qt = str_replace("<br/>", "\r\n", $qt);
                echo "Ответ с цитированием в тему <font color='" . $cntem . "'>$th1[text]</font><br/> Пост <font color='" . $conik . "'>$type1[from]</font>  :<br/><form action='?act=say&amp;id=" . $id .
                    "&amp;cyt' method='post' enctype='multipart/form-data'><textarea cols='20' title='Редактирование цитаты' rows='3' name='citata'>$qt</textarea><br/>Ответ(max. 500):<br/>";
            } else
            {
                if (($datauser['postforum'] == "" || $datauser['postforum'] == 0))
                {
                    if (!isset($_GET['yes']))
                    {
                        include ("../pages/forum.txt");

                        echo "<a href='?act=say&amp;id=" . $id . "&amp;yes'>Согласен</a>|<a href='?id=" . $type1['refid'] . "'>Не согласен</a><br/>";
                        require_once ("../incfiles/end.php");
                        exit;
                    }
                }
                echo "Добавление сообщения в тему <font color='" . $cntem . "'>$th1[text]</font> для <font color='" . $conik . "'>$type1[from]</font>:<br/><form action='?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'>";
            }
            echo "<textarea cols='20' rows='3' title='Введите ответ' name='msg'></textarea><br/><input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
        }
        echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
        echo "<a href='?id=" . $type1['refid'] . "'>Назад</a><br/>";
        break;

    default:
        require_once ("../incfiles/head.php");
        echo "Ошибка:тема удалена или не существует!<br/>&#187;<a href='?'>В форум</a><br/>";
        break;
}

?>