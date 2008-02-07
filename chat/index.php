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

$textl = 'Чат';
$headmod = "chat";
require ("../incfiles/db.php");
require ("../incfiles/func.php");
require ("../incfiles/data.php");

require ("../incfiles/char.php");
require ("../incfiles/stat.php");

if (!empty($_SESSION['pid']))
{
    $tti = round(($datauser['chtime'] - $realtime) / 60);
    if ($datauser['chban'] == "1" && $tti > 0)
    {
        require ("../incfiles/head.php");
        require ("../incfiles/inc.php");
        echo "Вас пнули из чата<br/>Кто: <font color='" . $cdinf . "'>$datauser[chwho]</font><br/>";
        if ($datauser[chwhy] == "")
        {
            echo "<div>Причина не указана</div>";
        } else
        {
            echo "Причина:<font color='" . $cdinf . "'> $datauser[chwhy]</font><br/>";
        }
        echo "Время до окончания: $tti минут<br/>";
        require ("../incfiles/end.php");
        exit;
    }

    if (!empty($_GET['id']))
    {
        $id = intval(check($_GET['id']));
        $where = "chat,$id";
    } else
    {
        $where = "chat";
    }
    mysql_query("insert into `count` values(0,'" . $ipp . "','" . $agn . "','" . $realtime . "','" . $where . "','" . $login . "','0');");
    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "delpost":
            if ($dostcmod == 1)
            {
                if (empty($_GET['id']))
                {
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $id = intval(check($_GET['id']));

                $typ = mysql_query("select * from `chat` where id='" . $id . "';");
                $ms = mysql_fetch_array($typ);
                if ($ms[type] != "m")
                {
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if (isset($_GET['yes']))
                {

                    mysql_query("delete from `chat` where `id`='" . $id . "';");
                    header("Location: index.php?id=$ms[refid]");
                } else
                {
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Вы действительно хотите удалить пост?<br/>";
                    echo "<a href='index.php?act=delpost&amp;id=" . $id . "&amp;yes'>Удалить</a>|<a href='index.php?id=" . $ms[refid] . "'>Отмена</a><br/>";
                }
            } else
            {
                echo "Доступ закрыт!!!<br/>";
            }
            break;

        case "room":
            if ($dostcmod == 1)
            {
                if (empty($_GET['id']))
                {
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                $id = intval(check($_GET['id']));

                $typ = mysql_query("select * from `chat` where id='" . $id . "';");
                $ms = mysql_fetch_array($typ);
                if ($ms[type] != "r")
                {
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
                    require ("../incfiles/end.php");
                    exit;
                }
                if (isset($_GET['yes']))
                {

                    $mes = mysql_query("select * from `chat` where refid='" . $id . "';");
                    while ($mes1 = mysql_fetch_array($mes))
                    {

                        mysql_query("delete from `chat` where `id`='" . $mes1[id] . "';");
                    }
                    header("Location: index.php?id=$id");
                } else
                {
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Вы действительно хотите очистить комнату?<br/>";
                    echo "<a href='index.php?act=room&amp;id=" . $id . "&amp;yes'>Да</a>|<a href='index.php?id=" . $id . "'>Нет</a><br/>";
                }
            } else
            {
                echo "Доступ закрыт!!!<br/>";
            }
            break;

        case "moders":
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            echo "<b>Модераторы чата</b><br/>";
            if (!empty($_GET['id']))
            {
                $id = intval(check($_GET['id']));
            }
            $mod = mysql_query("select * from `users` where rights='2';");
            $mod2 = mysql_num_rows($mod);
            if ($mod2 != 0)
            {
                while ($mod1 = mysql_fetch_array($mod))
                {

                    if ($login != $mod1[name])
                    {
                        echo "<a href='../str/anketa.php?user=" . $mod1[id] . "'><font color='" . $conik . "'>$mod1[name]</font></a>";
                    } else
                    {
                        echo "<font color='" . $csnik . "'>$mod1[name]</font>";
                    }
                    $ontime = $mod1[lastdate];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2)
                    {
                        echo "<font color='" . $coffs . "'> [Off]</font><br/>";
                    } else
                    {
                        echo "<font color='" . $cons . "'> [ON]</font><br/>";
                    }
                }
            } else
            {
                echo "Не назначены<br/>";
            }
            echo "<a href='index.php?id=" . $id . "'>Назад</a><br/>";
            break;

        case "trans":
            require ("../incfiles/head.php");
            require ("../incfiles/inc.php");
            include ("../pages/trans.$ras_pages");
            echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            break;

        case "say":
            if (getenv("HTTP_CLIENT_IP"))
                $ipp = getenv("HTTP_CLIENT_IP");
            else
                if (getenv("REMOTE_ADDR"))
                    $ipp = getenv("REMOTE_ADDR");
                else
                    if (getenv("HTTP_X_FORWARDED_FOR"))
                        $ipp = getenv("HTTP_X_FORWARDED_FOR");
                    else
                    {
                        $ipp = "not detected";
                    }
                    $ipp = check($ipp);
            $agn = check(getenv(HTTP_USER_AGENT));
            $agn = strtok($agn, ' ');
            if (empty($_GET['id']))
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Ошибка!<br/><a href='?'>В чат</a><br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $id = intval(check($_GET['id']));
            if (empty($_SESSION['pid']))
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                echo "Вы не авторизованы!<br/>";
                require ("../incfiles/end.php");
                exit;
            }
            $type = mysql_query("select * from `chat` where id= '" . $id . "';");
            $type1 = mysql_fetch_array($type);
            $tip = $type1[type];
            switch ($tip)
            {
                case "r":
                    if (isset($_POST['submit']))
                    {
                        $flt = $realtime - 10;
                        $af = mysql_query("select * from `chat` where type='m' and time >='" . $flt . "' and `from` = '" . trim($login) . "';");
                        $af1 = mysql_num_rows($af);
                        if ($af1 > 0)
                        {
                            require ("../incfiles/head.php");
                            require ("../incfiles/inc.php");
                            echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 10 секунд<br/><a href='index.php?id=" . $id . "'>Назад</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        if (empty($_POST['msg']))
                        {
                            require ("../incfiles/head.php");
                            require ("../incfiles/inc.php");
                            echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        $msg = check(trim($_POST['msg']));
                        $msg = utfwin($msg);

                        $msg = substr($msg, 0, 500);
                        if ($o >= 496)
                        {
                            $o = strrpos($msg, "<");
                            $msg = substr($msg, 0, $o);
                        }
                        $msg = winutf($msg);
                        if ($_POST[msgtrans] == 1)
                        {
                            $msg = trans($msg);
                        }

                        mysql_query("insert into `chat` values(0,'" . $id . "','','m','" . $realtime . "','" . $login . "','','0','" . $msg . "','" . $ipp . "','" . $agn . "','','');");
                        if (empty($datauser[postchat]))
                        {
                            $fpst = 1;
                        } else
                        {
                            $fpst = $datauser[postchat] + 1;
                        }
                        mysql_query("update `users` set  postchat='" . $fpst . "' where id='" . intval($_SESSION['pid']) . "';");
                        if ($type1[dpar] == "vik")
                        {
                            $protv = mysql_query("select * from `chat` where dpar='vop' and type='m' order by time desc;");
                            while ($protv2 = mysql_fetch_array($protv))
                            {
                                $prr[] = $protv2[id];
                            }
                            $pro = mysql_query("select * from `chat` where dpar='vop' and type='m' and id='" . $prr[0] . "';");
                            $protv1 = mysql_fetch_array($pro);
                            $prr = array();

                            $ans = $protv1[realid];
                            $vopr = mysql_query("select * from `vik` where id='" . $ans . "';");
                            $vopr1 = mysql_fetch_array($vopr);
                            $answer = $vopr1[otvet];
                            if (!empty($msg) && !empty($answer) && $protv1[otv] != 1)
                            {
                                if (preg_match("/$answer/i", "$msg"))
                                {
                                    $itg = $datauser[otvetov] + 1;
                                    switch ($protv1[otv])
                                    {
                                        case "2":
                                            $pods = ", не используя подсказок";
                                            $bls = 5;
                                            break;
                                        case "3":
                                            $pods = ", используя одну подсказку";
                                            $bls = 3;
                                            break;
                                        case "4":
                                            $pods = ", используя две подсказки";
                                            $bls = 2;
                                            break;
                                    }
                                    $balans = $datauser[balans] + $bls;
                                    $otvtime = $realtime - $protv1[time];
                                    if ($datauser[sex] == "m")
                                    {
                                        $tx = "молодец! Ты угадал правильный ответ:  $answer за $otvtime секунд $pods ,и заработал $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    } else
                                    {
                                        $tx = "молодец! Ты угадала правильный ответ:  $answer за $otvtime секунд $pods ,и заработала $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    }
                                    $mtim = $realtime + 1;
                                    mysql_query("INSERT INTO `chat` VALUES(
'0','" . $id . "','','m','" . $mtim . "','Умник','" . $login . "','', '" . $tx . "', '127.0.0.1', 'Nokia3310', '','');");
                                    mysql_query("update `chat` set otv='1' where id='" . $protv1[id] . "';");
                                    mysql_query("update `users` set otvetov='" . $itg . "',balans='" . $balans . "' where id='" . intval($_SESSION['pid']) . "';");
                                }
                            }
                        }

                        header("location: index.php?id=$id");
                    } else
                    {
                        require ("../incfiles/head.php");
                        require ("../incfiles/inc.php");
                        echo "Добавление сообщения в комнату <font color='" . $cntem . "'>$type1[text]</font>(max. 500):<br/><form action='index.php?act=say&amp;id=" . $id .
                            "' method='post'><textarea cols='40' rows='3' title='Введите текст сообщения' name='msg'></textarea><br/>";
                        if ($offtr != 1)
                        {
                            echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                        }
                        echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
                        echo "<a href='index.php?act=trans'>Транслит</a><br /><a href='../str/smile.php'>Смайлы</a><br/>";
                        echo "<a href='index.php?id=" . $id . "'>Назад</a><br/>";
                    }
                    break;

                case "m":
                    $th = $type1[refid];
                    $th2 = mysql_query("select * from `chat` where id= '" . $th . "';");
                    $th1 = mysql_fetch_array($th2);
                    if (isset($_POST['submit']))
                    {
                        $flt = $realtime - 10;
                        $af = mysql_query("select * from `chat` where type='m' and time>'" . $flt . "' and `from`= '" . $login . "';");
                        $af1 = mysql_num_rows($af);
                        if ($af1 != 0)
                        {
                            require ("../incfiles/head.php");
                            require ("../incfiles/inc.php");
                            echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 10 секунд<br/><a href='index.php?id=" . $th . "'>Назад</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        if (empty($_POST['msg']))
                        {
                            require ("../incfiles/head.php");
                            require ("../incfiles/inc.php");
                            echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require ("../incfiles/end.php");
                            exit;
                        }
                        $to = $type1[from];
                        $priv = intval(check($_POST['priv']));
                        $nas = check($_POST['nas']);
                        $msg = check(trim($_POST['msg']));
                        $msg = utfwin($msg);
                        $msg = substr($msg, 0, 500);

                        $o = strrpos($msg, "<");
                        if ($o >= 496)
                        {
                            $msg = substr($msg, 0, $o);
                        }

                        $msg = winutf($msg);
                        if ($_POST[msgtrans] == 1)
                        {
                            $msg = trans($msg);
                        }

                        mysql_query("insert into `chat` values(0,'" . $th . "','','m','" . $realtime . "','" . $login . "','" . $to . "','" . $priv . "','" . $msg . "','" . $ipp . "','" . $agn . "','" . $nas . "','');");
                        if (empty($datauser[postchat]))
                        {
                            $fpst = 1;
                        } else
                        {
                            $fpst = $datauser[postchat] + 1;
                        }
                        mysql_query("update `users` set  postchat='" . $fpst . "' where id='" . intval($_SESSION['pid']) . "';");
                        if ($th1[dpar] == "vik")
                        {
                            $protv = mysql_query("select * from `chat` where dpar='vop' and type='m' order by time desc;");
                            while ($protv2 = mysql_fetch_array($protv))
                            {
                                $prr[] = $protv2[id];
                            }
                            $pro = mysql_query("select * from `chat` where dpar='vop' and type='m' and id='" . $prr[0] . "';");
                            $protv1 = mysql_fetch_array($pro);
                            $prr = array();
                            $ans = $protv1[realid];
                            $vopr = mysql_query("select * from `vik` where id='" . $ans . "';");
                            $vopr1 = mysql_fetch_array($vopr);
                            $answer = $vopr1[otvet];
                            if (!empty($msg) && !empty($answer) && $protv1[otv] != 1)
                            {
                                if (preg_match("/$answer/i", "$msg"))
                                {
                                    $itg = $datauser[otvetov] + 1;
                                    switch ($protv1[otv])
                                    {
                                        case "2":
                                            $pods = ", не используя подсказок";
                                            $bls = 5;
                                            break;
                                        case "3":
                                            $pods = ", используя одну подсказку";
                                            $bls = 3;
                                            break;
                                        case "4":
                                            $pods = ", используя две подсказки";
                                            $bls = 2;
                                            break;
                                    }
                                    $balans = $datauser[balans] + $bls;
                                    $otvtime = $realtime - $protv1[time];
                                    if ($datauser[sex] == "m")
                                    {
                                        $tx = "молодец! Ты угадал правильный ответ:  $answer за $otvtime секунд $pods ,и заработал $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    } else
                                    {
                                        $tx = "молодец! Ты угадала правильный ответ:  $answer за $otvtime секунд $pods ,и заработала $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    }
                                    $mtim = $realtime + 1;
                                    mysql_query("INSERT INTO `chat` VALUES(
'0','" . $th . "','','m','" . $mtim . "','Умник','" . $login . "','', '" . $tx . "', '127.0.0.1', 'Nokia3310', '','');");
                                    mysql_query("update `chat` set otv='1' where id='" . $protv1[id] . "';");
                                    mysql_query("update `users` set otvetov='" . $itg . "',balans='" . $balans . "' where id='" . intval($_SESSION['pid']) . "';");
                                }
                            }
                        }

                        header("location: index.php?id=$th");
                    } else
                    {

                        require ("../incfiles/head.php");
                        require ("../incfiles/inc.php");
                        $user = mysql_query("select * from `users` where name='" . $type1[from] . "';");
                        $ruz = mysql_num_rows($user);
                        if ($ruz != 0)
                        {
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
                            echo "<br/>";
                            if (!empty($udat[status]))
                            {
                                $stats = $udat[status];
                                $stats = smiles($stats);
                                $stats = smilescat($stats);

                                $stats = smilesadm($stats);
                                echo "<font color='" . $cdinf . "'>$stats</font><br/>";
                            }

                            if ($udat['sex'] == "m")
                            {
                                echo "Парень<br/>";
                            }
                            if ($udat['sex'] == "zh")
                            {
                                echo "Девушка<br/>";
                            }
                            if (!empty($udat[balans]))
                            {
                                echo "Игровой баланс: $udat[balans] баллов<br/>";
                            }
                            if ($udat['ban'] == "1" && $udat['bantime'] > $realtime || $udat['ban'] == "2")
                            {
                                echo "<font color='" . $cdinf . "'>Бан!</font><br/>";
                            }
                            if (empty($udat[nastroy]))
                            {
                                $nstr = "без настроения";
                            } else
                            {
                                $nstr = $udat[nastroy];
                            }
                            echo "Настроение: $udat[nastroy]<br/>";
                        }
                        echo "Добавление сообщения в комнату <font color='" . $cntem . "'>$th1[text]</font> для <font color='" . $conik . "'>$type1[from]</font>(max. 500):<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post'>";
                        echo "<textarea cols='40' rows='3' title='Введите ответ' name='msg'></textarea><br/>";
                        if ($offtr != 1)
                        {
                            echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
                        }
                        echo "<select name='priv'>";
                        echo "<option value='0'>Всем</option>";
                        echo "<option value='1'>Приватно</option>";
                        echo "</select><br/>";
                        echo "Эмоции:<br/><select name='nas'>
<option value=''>Бeз эмoций</option>
<option value='[Paдocтнo] '>Paдocтнo</option>
<option value='[Пeчaльнo] '>Пeчaльнo</option>
<option value='[Удивлённo] '>Удивлённo</option>
<option value='[Лacкoвo] '>Лacкoвo</option>
<option value='[Смyщённo] '>Cмyщённo</option>
<option value='[Koкeтливo] '>Koкeтливo</option>
<option value='[Oбижeннo] '>Oбижeннo</option>
<option value='[Нacтoйчивo] '>Нacтойчивo</option>
<option value='[Шёпoтoм] '>Шёпoтoм</option>
<option value='[Агрессивно] '>Агрессивно</option>
<option value='[Шокированно] '>Шокированно</option>
<option value='[Огорченно] '>Огорченно</option>
<option value='[Издевательски] '>Издевательски</option>
<option value='[Испацтула] '>Испацтула</option>
<option value='[Нагло] '>Нагло</option>
<option value='[Испуганно] '>Испуганно</option>
<option value='[Злобно] '>Злобно</option>
<option value='[Улыбаясь] '>Улыбаясь</option>
<option value='[Подмигивая] '>Подмигивая</option>
<option value='[Удрученно] '>Удрученно</option>
<option value='[Устало] '>Устало</option>
<option value='[Задумчиво] '>Задумчиво</option>
<option value='[Откровенно] '>Откровенно</option>
</select><br/>";
                        echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
                        echo "<a href='index.php?act=trans'>Транслит</a><br/><a href='../str/smile.php'>Смайлы</a><br/>";
                        if ($ruz != 0)
                        {
                            echo "<br/><a href='../str/pradd.php?act=write&amp;adr=" . $udat[id] . "'>Написать в приват</a><br/>";

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

                            echo "<a href='../str/anketa.php?user=" . $udat[id] . "'>Подробнее...</a><br/>";
                            if ($dostkmod == 1)
                            {
                                echo "<a href='../" . $admp . "/zaban.php?user=" . $udat[id] . "&amp;chat&amp;id=" . $id . "'>Банить</a><br/>";
                            } elseif ($dostcmod == 1)
                            {
                                echo "<a href='../" . $admp . "/zaban.php?user=" . $udat[id] . "&amp;chat&amp;id=" . $id . "'>Пнуть</a><br/>";
                            }

                            $contacts = mysql_query("select * from `privat` where me='" . $login . "' and cont='" . $udat[name] . "';");
                            $conts = mysql_num_rows($contacts);
                            if ($conts == 0)
                            {
                                echo "<a href='../str/cont.php?act=edit&amp;nik=" . $udat[name] . "&amp;add=1'>Добавить в контакты</a><br/>";
                            } else
                            {
                                echo "<a href='../str/cont.php?act=edit&amp;nik=" . $udat[name] . "'>Удалить из контактов</a><br/>";
                            }
                            $igns = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $udat[name] . "';");
                            $ignss = mysql_num_rows($igns);
                            if ($ignss == 0)
                            {
                                echo "<a href='../str/ignor.php?act=edit&amp;nik=" . $udat[name] . "&amp;add=1'>Добавить в игнор</a><br/>";
                            } else
                            {
                                echo "<a href='../str/ignor.php?act=edit&amp;nik=" . $udat[name] . "'>Удалить из игнора</a><br/>";
                            }
                        }
                        echo "<a href='index.php?id=" . $type1[refid] . "'>Назад</a><br/>";
                    }
                    break;

                default:
                    require ("../incfiles/head.php");
                    require ("../incfiles/inc.php");
                    echo "Ошибка!<br/>&#187;<a href='?'>В чат</a><br/>";
                    break;
            }
            break;

        case "chpas":
            $id = intval($_GET['id']);
            $_SESSION['intim'] = "";
            header("location: index.php?id=$id");
            break;

        case "pass":

            $id = intval($_GET['id']);
            $parol = check($_POST['parol']);
            $_SESSION['intim'] = $parol;
            mysql_query("update `users` set alls='" . $parol . "' where id='" . intval($_SESSION['pid']) . "';");
            header("location: index.php?id=$id");
            break;

        default:

            if (empty($_GET['id']))
            {
                require ("../incfiles/head.php");
                require ("../incfiles/inc.php");
                $_SESSION['intim'] = "";
                $q = mysql_query("select * from `chat` where type='r' order by realid ;");
                while ($mass = mysql_fetch_array($q))
                {
                    echo "<a href='index.php?id=" . $mass[id] . "'><font color='" . $cntem . "'>$mass[text]</font></a> (";
                    wch($mass[id]);
                    echo ")<br/>";
                }
                echo "<hr/>";
       ##############
                echo "<a href='who.php'>Кто в чате(".wch().")</a><br/>";  ####7.02.08########
       ################         
                echo "<a href='../str/usset.php?act=chat'>Настройки чата</a><br/>";
            }

            if (!empty($_GET['id']))
            {
                $id = intval(check($_GET['id']));
                $type = mysql_query("select * from `chat` where id= '" . $id . "';");
                $type1 = mysql_fetch_array($type);
                $tip = $type1[type];
                switch ($tip)
                {
                        ###
                    case "r":
                        ##############
                        if ($type1[dpar] != "in")
                        {
                            $_SESSION['intim'] = "";
                        }
                        if ($type1[dpar] == "in")
                        {
                            if (empty($_SESSION['intim']))
                            {
                                require ("../incfiles/head.php");
                                require ("../incfiles/inc.php");
                                echo "<form action='index.php?act=pass&amp;id=" . $id .
                                    "' method='post'><br/>Введите пароль(max. 10):<br/><input type='text' name='parol' maxlength='10'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href='index.php'>В чат</a><br/>";
                                require ("../incfiles/end.php");
                                exit;
                            }
                        }

                        if ($type1[dpar] == "vik")
                        {
                            $prvik = mysql_query("select * from `chat` where dpar='vop' and type='m';");
                            $prvik1 = mysql_num_rows($prvik);
                            if ($prvik1 == "0")
                            {
                                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm', '" . $realtime . "','Умник','','vop','Начинаем Викторину', '127.0.0.1', 'Nokia3310','','5');");
                            }


                            $protv = mysql_query("select * from `chat` where dpar='vop' and type='m' order by time desc;");
                            while ($protv1 = mysql_fetch_array($protv))
                            {
                                $prr[] = $protv1[id];
                            }
                            $pro = mysql_query("select * from `chat` where dpar='vop' and type='m' and id='" . $prr[0] . "';");
                            $prov = mysql_fetch_array($pro);
                            $prr = array();
                            if ($prov[otv] == "2" && $prov[time] < intval($realtime - 15))
                            {
                                $vopr = mysql_query("select * from `vik` where id='" . $prov['realid'] . "';");
                                $vopr1 = mysql_fetch_array($vopr);
                                $ans = $vopr1[otvet];
                                $b = strlen($ans);
                                $b = $b / 2;
                                $c = substr($ans, 0, 2);
                                for ($i = 2; $i <= $b; ++$i)
                                {
                                    $c = "$c*";
                                }

                                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm','" . $realtime . "','Умник','','', 'Первая подсказка " . $c . "', '127.0.0.1', 'Nokia3310', '', '');");
                                mysql_query("update `chat` set otv='3' where id='" . $prov[id] . "';");
                            }

                            if ($prov[otv] == "3" && $prov[time] < intval($realtime - 30))
                            {
                                $vopr = mysql_query("select * from `vik` where id='" . $prov[realid] . "';");
                                $vopr1 = mysql_fetch_array($vopr);
                                $ans = $vopr1[otvet];
                                $b = strlen($ans);
                                $b = $b / 2;
                                $c = substr($ans, 0, 4);
                                for ($i = 3; $i <= $b; ++$i)
                                {
                                    $c = "$c*";
                                }
                                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm','" . $realtime . "','Умник','','', 'Вторая подсказка " . $c . "', '127.0.0.1', 'Nokia3310', '', '');");
                                mysql_query("update `chat` set otv='4' where id='" . $prov[id] . "';");
                            }
                            if ($prov[otv] == "5" && $prov[time] < intval($realtime - 15))
                            {
                                $v = mysql_query("select * from `vik` ;");
                                $c = mysql_num_rows($v);
                                $num = rand(1, $c);
                                $vik = mysql_query("select * from `vik` where id='" . $num . "';");
                                $vik1 = mysql_fetch_array($vik);
                                $vopros = $vik1[vopros];
                                $len = strlen($vik1[otvet]) / 2;
                                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','" . $num . "', 'm','" . $realtime . "','Умник','','vop', '<b>Вопрос: " . $vopros . " (" . $len . " букв)</b>', '127.0.0.1', 'Nokia3310', '', '2');");
                            }

                            if (!empty($prov[time]) && $prov[time] < intval($realtime - 60))
                            {
                                if ($prov[otv] == "1")
                                {

                                    $v = mysql_query("select * from `vik` ;");
                                    $c = mysql_num_rows($v);
                                    $num = rand(1, $c);
                                    $vik = mysql_query("select * from `vik` where id='" . $num . "';");
                                    $vik1 = mysql_fetch_array($vik);
                                    $vopros = $vik1[vopros];
                                    $len = strlen($vik1[otvet]) / 2;
                                    mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','" . $num . "', 'm','" . $realtime . "','Умник','','vop', '<b>Вопрос: " . $vopros . " (" . $len . " букв)</b>', '127.0.0.1', 'Nokia3310', '', '2');");
                                }
                                if ($prov[otv] == "4")
                                {
                                    mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm','" . $realtime . "','Умник','','', 'Время истекло! Вопрос не был угадан!','127.0.0.1', 'Nokia3310', '', '1');");
                                    mysql_query("update `chat` set otv='1' where id='" . $prov[id] . "';");
                                }
                            }
                        }
                        $refr = rand(0, 999);
                        if ($gzip == "1")
                        {
                            ob_start('ob_gzhandler');
                        }
                        $agent = $_SERVER['HTTP_USER_AGENT'];

                        header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                        header("Cache-Control: no-cache, must-revalidate");
                        header("Pragma: no-cache");
                        header("Last-Modified: " . gmdate("D, d M Y H:i:s") . "GMT");

                        if (eregi("msie", $agent) && eregi("windows", $agent))
                        {
                            header('Content-type: text/html; charset=UTF-8');
                        } else
                        {
                            header('Content-type: application/xhtml+xml; charset=UTF-8');
                        }
                        echo '<?xml version="1.0" encoding="utf-8"?>';
                        echo "\n" . '<!DOCTYPE html PUBLIC "-//WAPFORUM//DTD XHTML Mobile 1.0//EN" ';
                        echo "\n" . '"http://www.wapforum.org/DTD/xhtml-mobile10.dtd">';
                        echo "\n" . '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru">
<head>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=utf-8"/>';

                        echo "<meta http-equiv='refresh' content='" . $obn . ";URL=index.php?id=" . intval($_GET['id']) . "&amp;refr=" . $refr . "'/>";

                        echo "<link rel='shortcut icon' href='favicon.ico' />
      <title>
      $textl
      </title>
<style type='text/css'>


############################7.02.08


body { background-color: #ffffff;  color: black;  font-family: Arial, Tahoma, sans-serif;  font-size: 8pt;  margin: 0px;  border: 0px;  padding: 0px;}
form {padding: 0px; margin: 0px; font-size: small;}
.header  {
	background-color: #586776;
	color: #FFFFFF;
	font-size: 18px;
	font-weight: bold;
	font-family: Arial, Helvetica, sans-serif;
	padding-top: 5px;
	padding-right: 0px;
	padding-bottom: 5px;
	padding-left: 2px;
	margin: 0px;
}
.maintxt {
	font-weight: normal;
	font-size: 8pt;
	PADDING-RIGHT: 2px;
	PADDING-LEFT: 2px;
	PADDING-BOTTOM: 0px;
	MARGIN: 0px;
	PADDING-TOP: 0px;
}
.ackey {
	text-decoration: underline;
	font-size: 11px;
}
.topmenu {
	color: #003300;
	font-size: 11px;
	font-weight: normal;
	padding: 1px 0px 2px 3px;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #FFFFFF;
	margin: 0px;
	border-top: 1px solid #000000;
	border-bottom: 1px solid #586776;
}
.menu {
	background-color: #d6dce2;
	margin: 0px;
	border-top-width: 1px;
	border-right-width: 0px;
	border-bottom-width: 1px;
	border-left-width: 0px;
	border-top-style: solid;
	border-right-style: solid;
	border-bottom-style: solid;
	border-left-style: solid;
	border-top-color: #FFFFFF;
	border-bottom-color: #CCCCCC;
}
.fmenu {
	color: #003300;
	font-size: 11px;
	font-weight: normal;
	font-family: Arial, Helvetica, sans-serif;
	padding-top: 1px;
	padding-right: 1px;
	padding-bottom: 2px;
	padding-left: 3px;
	margin: 0px;
	border-top-width: 1px;
	border-right-width: 0px;
	border-bottom-width: 1px;
	border-left-width: 0px;
	border-top-style: solid;
	border-right-style: solid;
	border-bottom-style: solid;
	border-left-style: solid;
	border-top-color: #586776;
	border-right-color: #003300;
	border-bottom-color: #003300;
	border-left-color: #003300;
}
.footer  {
	color: #FFFFFF;
	background-color: #586776;
	font-family: Arial, Helvetica, sans-serif;
	font-size: 11px;
	font-weight: normal;
	padding-top: 2px;
	padding-right: 0;
	padding-bottom: 3px;
	padding-left: 3px;
	margin: 0px;
}
.footer a:link, .footer a:visited {
	color: #FFFFFF;
}

a:link, a:visited {
	text-decoration: underline;
	color: $clink;
}

a:hover {
	text-decoration: none;
	color: #ff6600;
}

a:active {
	text-decoration: underline;
	color: #666600;
}

body {
	color: $colt;
	background-color: $fon;
}
hr{
	margin: 0;
	border: 0;
	border-top: 1px solid #586776;
}
.a 	{
	background-color: $fon;
	border: 1px solid #b5bec7;
	margin-bottom: 3px;
	padding: 2px;
}

.b 	{
	background-color: $clb;
	padding: 2px;
	margin: 0px;
}

.c, .e {
	background-color: $clc;
	padding: 2px;
	margin: 0px;
}

.d {
	background-color: $fon;
	text-align: left;
	font-size: 12px;
	color: $clink;
}

.end{
	text-align: center;
	color: #000000;
}

.hdr{
	font-weight: bold;
	border-bottom: 1px dotted #0000ff;
	padding-left: 2px;
	background-color: #f1f1f1;
}

</style>
      </head>
      <body>";
    // Выводим логотип. Если нужно, то раскомментируйте строку ниже
    //echo '<div><center><img src="' . $home . '/images/logo.gif" alt=""/></center></div>';

    // Выводим название сайта
    echo '<div class="header">' . $textl . '</div>';

    // Выводим меню пользователя
    echo '<div class="topmenu">';
    $tvr = $realtime + $sdvig * 3600;
    $vrem = date("H:i / d.m.Y", $tvr);

    // Выводим текущее время. Если нужно, то раскомментируйте
    //if ($headmod == "mainpage")
    //{
    //    echo $vrem . '<br/>';
    //}

    // Выводим приветствие
    //if (!empty($_SESSION['pid']))
    //{
    //    echo "Привет,<b> " . $login . "</b>!<br/>";
    //} else
    //{
    //    echo "Привет, прохожий!<br/>";
    //}

    // Выводим меню пользователя вверху сайта
    if ($headmod != "mainpage" || isset($_GET['do']))
    {
        echo '<a href=\'' . $home . '\'>На главную</a> | ';
    }
    if (!empty($_SESSION['pid']))
    {
        echo "<a href='" . $home . "/index.php?do=cab'>Личное</a> | <a href='" . $home . "/exit.php'>Выход</a><br/>";
    } else
    {
        echo "<a href='" . $home . "/in.php'>Вход</a> | <a href='" . $home . "/registration.php'>Регистрация</a><br/>";
    }
    echo '</div><div class="maintxt">';
    
     ###################
     
                        require ("../incfiles/inc.php");


                        echo "<b><font color='" . $cntem . "'>$type1[text]</font></b><br/>";
                        if ($type1[dpar] == "in")
                        {
                            echo "<a href='index.php?act=chpas&amp;id=" . $id . "'>Сменить пароль</a><br/>";
                        }
                        echo "<a href='index.php?id=" . $id . "&amp;refr=" . $refr . "'>Обновить</a><br/>";
                        if ($carea == 1)
                        {
                            echo "Написать<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
                            if ($offtr != 1)
                            {
                                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                            }
                            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form>";
                        } else
                        {
                            echo "<a href='index.php?act=say&amp;id=" . $id . "'>Написать</a>";
                        }
                        $q2 = mysql_query("select * from `chat` where type='m' and refid='" . $id . "';");
                        while ($masss = mysql_fetch_array($q2))
                        {
                            $q3 = mysql_query("select * from `users` where name='" . $masss[from] . "';");
                            $q4 = mysql_fetch_array($q3);
                            $pasw = $q4[alls];
                            if (($masss[dpar] != 1 || $masss[to] == $login || $masss[from] == $login || $dostsadm == 1) && ($ign1 == 0 || $dostcmod == 1))
                            {
                                if ($type1[dpar] != "in" || $pasw == $datauser[alls])
                                {
                                    $cm[] = $masss[id];
                                }
                            }
                        }
                        $colmes = count($cm);

                        if (empty($_GET['page']))
                        {
                            $page = 1;
                        } else
                        {
                            $page = intval($_GET['page']);
                        }
                        $start = $page * $chmes - $chmes;
                        if ($colmes < $start + $chmes)
                        {
                            $end = $colmes;
                        } else
                        {
                            $end = $start + $chmes;
                        }

                        $q1 = mysql_query("select * from `chat` where type='m' and refid='" . $id . "'  order by time desc ;");
                        $i = 0;
                        while ($mass = mysql_fetch_array($q1))
                        {
                            if ($i >= $start && $i < $end)
                            {
                                $ign = mysql_query("select * from `privat` where me='" . $login . "' and ignor='" . $mass[from] . "';");
                                $ign1 = mysql_num_rows($ign);
                                $als = mysql_query("select * from `users` where name='" . $mass[from] . "';");
                                $als1 = mysql_fetch_array($als);
                                $psw = $als1[alls];
                                if (($mass[dpar] != 1 || $mass[to] == $login || $mass[from] == $login || $dostsadm == 1) && ($ign1 == 0 || $dostcmod == 1))
                                {
                                    if ($type1[dpar] != "in" || $psw == $datauser[alls])
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
                                        if ($mass[from] != "Умник")
                                        {
                                            $uz = @mysql_query("select * from `users` where name='" . $mass[from] . "';");
                                            $mass1 = @mysql_fetch_array($uz);
                                        }
                                        echo "$div";
                                        if ($pfon == 1)
                                        {
                                            echo "<div style='background:" . $cpfon . ";'>";
                                        }
                                        if ($mass[from] != "Умник")
                                        {
                                            switch ($mass1[sex])
                                            {
                                                case "m":
                                                    echo "<img src='../images/m.gif' alt=''/>";
                                                    break;
                                                case "zh":
                                                    echo "<img src='../images/f.gif' alt=''/>";
                                                    break;
                                            }
                                        }
                                        if ($mass[from] != "Умник")
                                        {
                                            if ((!empty($_SESSION['pid'])) && ($_SESSION['pid'] != $mass1[id]))
                                            {
                                                echo "<a href='index.php?act=say&amp;id=" . $mass[id] . "'><b><font color='" . $conik . "'>$mass[from]</font></b></a> ";
                                            } else
                                            {
                                                echo "<b><font color='" . $csnik . "'>$mass[from]</font></b>";
                                            }
                                        } else
                                        {
                                            echo "<b><font color='" . $conik . "'>$mass[from]</font></b>";
                                        }
                                        $vrp = $mass[time] + $sdvig * 3600;
                                        $vr = date("d.m.Y / H:i", $vrp);
                                        if ($mass[from] != "Умник")
                                        {
                                            switch ($mass1[rights])
                                            {
                                                case 7:
                                                    echo "<font color='" . $cadms . "'> Adm </font>";
                                                    break;
                                                case 6:
                                                    echo "<font color='" . $cadms . "'> Smd </font>";
                                                    break;
                                                case 2:
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
                                        }
                                        echo "<font color='" . $cdtim . "'>($vr)</font><br/>";

                                        if ($pfon == 1)
                                        {
                                            echo "</div>";
                                        }
                                        if (!empty($mass[nas]))
                                        {
                                            echo "<font color='" . $cdinf . "'>$mass[nas]</font><br/>";
                                        }
                                        if ($mass[dpar] == 1)
                                        {
                                            echo "<font color='" . $clink . "'>[П!]</font>";
                                        }
                                        if (!empty($mass[to]))
                                        {

                                            if ($mass[to] == $login)
                                            {
                                                echo "<font color='" . $cdinf . "'><b>";
                                            }
                                            echo "$mass[to], ";
                                            if ($mass[to] == $login)
                                            {
                                                echo "</b></font>";
                                            }
                                        }
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
                                        if ($mass[to] == $login)
                                        {
                                            echo "<font color='" . $cdinf . "'><b>";
                                        }
                                        echo "$tekst<br/>";
                                        if ($mass[to] == $login)
                                        {
                                            echo "</b></font>";
                                        }
                                        if ($dostcmod == 1)
                                        {
                                            echo "<a href='index.php?act=delpost&amp;id=" . $mass[id] . "'>Удалить</a><br/>";
                                            echo "$mass[ip] - $mass[soft]<br/>";
                                        }
                                        echo "</div>";
                                    }
                                }
                            }
                            if (($mass[dpar] != 1 || $mass[to] == $login || $mass[from] == $login || $dostsadm == 1) && ($ign1 == 0 || $dostcmod == 1))
                            {
                                if ($type1[dpar] != "in" || $psw == $datauser[alls])
                                {
                                    ++$i;
                                }
                            }
                        }
                        ##

                        if ($colmes > $chmes)
                        {
                            echo "<hr/>";


                            $ba = ceil($colmes / $chmes);
                            if ($offpg != 1)
                            {
                                echo "Страницы:<br/>";
                            } else
                            {
                                echo "Страниц: $ba<br/>";
                            }
                            $asd = $start - ($chmes);
                            $asd2 = $start + ($chmes * 2);

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
                                        $ii = floor(1 + $i / $chmes);

                                        if ($start == $i)
                                        {
                                            echo " <b>$ii</b>";
                                        } else
                                        {
                                            echo ' <a href="?id=' . $id . '&amp;page=' . $ii . '">' . $ii . '</a> ';
                                        }
                                    }
                                    $i = $i + $chmes;
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


                            if ($colmes > $start + $chmes)
                            {
                                echo ' <a href="?id=' . $id . '&amp;page=' . ($page + 1) . '">&gt;&gt;</a>';
                            }
                        }
         ###############               
                        echo "<hr/><a href='who.php?id=" . $id . "'>Кто здесь?(".wch($id).")</a><br/>";
                        
                        echo "<a href='who.php'>Кто в чате(".wch().")</a><br/>";  #####7.02.08######
         ##############
                        echo "<a href='index.php?'>В чат</a><br/>";
                        if ($dostcmod == 1)
                        {
                            echo "<a href='index.php?act=room&amp;id=" . $id . "'>Очистить комнату</a><br/>";
                        }

                        break;

                        ##
                        ###
                    default:
                        require ("../incfiles/head.php");
                        require ("../incfiles/inc.php");
                        echo "Ошибка!<br/>&#187;<a href='index.php?'>В чат</a><br/>";
                        break;
                }
            }
            if ($dostsmod == 1)
            {
                echo "<a href='../" . $admp . "/chat.php'>Управление комнатами</a><br/>";
            }
            echo "<a href='index.php?act=moders&amp;id=" . $id . "'>Модераторы</a><br/>";

            break;
    }
} else
{
    require ("../incfiles/head.php");
    require ("../incfiles/inc.php");
    echo "Вы не авторизованы!<br/><a href='../in.php'>Вход</a><br/>";
}
require ("../incfiles/end.php");

?>




