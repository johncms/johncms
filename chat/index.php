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

$textl = 'Чат';
$headmod = 'chat';
require_once ("../incfiles/core.php");

// Закрываем доступ в чат
if (!$set['mod_chat'] && $dostadm != 1)
{
    require_once ("../incfiles/head.php");
    echo '<p>' . $set['mod_chat_msg'] . '</p>';
    require_once ("../incfiles/end.php");
    exit;
}

if ($ban['1'] || $ban['12'])
{
    require_once ("../incfiles/head.php");
    echo '<p>Для Вас доступ в Чат закрыт.</p>';
    require_once ("../incfiles/end.php");
    exit;
}

if ($user_id)
{
    // Фиксируем местонахождение пользователя
    $where = !empty($id) ? 'chat,' . $id : 'chat';
    mysql_query("INSERT INTO `count` SET
	`ip`='" . $ipp . "',
	`browser`='" . mysql_real_escape_string($agn) . "',
	`time`='" . $realtime . "',
	`where`='" . $where . "',
	`name`='" . $login . "';");

    if (!empty($_GET['act']))
    {
        $act = check($_GET['act']);
    }
    switch ($act)
    {
        case "room":
            if ($dostcmod == 1)
            {
                if (empty($id))
                {
                    require_once ("../incfiles/head.php");
                    echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $typ = mysql_query("select * from `chat` where id='" . $id . "';");
                $ms = mysql_fetch_array($typ);
                if ($ms['type'] != "r")
                {
                    require_once ("../incfiles/head.php");
                    echo "Ошибка!<br/><a href='index.php?'>В чат</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                if (isset($_GET['yes']))
                {

                    $mes = mysql_query("select * from `chat` where refid='" . $id . "';");
                    while ($mes1 = mysql_fetch_array($mes))
                    {

                        mysql_query("delete from `chat` where `id`='" . $mes1['id'] . "';");
                    }
                    header("Location: $home/chat/index.php?id=$id");
                } else
                {
                    require_once ("../incfiles/head.php");
                    echo "Вы действительно хотите очистить комнату?<br/>";
                    echo "<a href='index.php?act=room&amp;id=" . $id . "&amp;yes'>Да</a>|<a href='index.php?id=" . $id . "'>Нет</a><br/>";
                }
            } else
            {
                echo "Доступ закрыт!!!<br/>";
            }
            require_once ("../incfiles/end.php");
            break;

        case "moders":
            require_once ("../incfiles/head.php");
            echo "<b>Модераторы чата</b><br/>";
            $mod = mysql_query("select * from `users` where rights='2';");
            $mod2 = mysql_num_rows($mod);
            if ($mod2 != 0)
            {
                while ($mod1 = mysql_fetch_array($mod))
                {
                    if ($login != $mod1['name'])
                    {
                        echo "<a href='../str/anketa.php?user=" . $mod1['id'] . "'><font color='" . $conik . "'>$mod1[name]</font></a>";
                    } else
                    {
                        echo "<font color='" . $csnik . "'>$mod1[name]</font>";
                    }
                    $ontime = $mod1['lastdate'];
                    $ontime2 = $ontime + 300;
                    if ($realtime > $ontime2)
                    {
                        echo '<font color="#FF0000"> [Off]</font>';
                    } else
                    {
                        echo '<font color="#00AA00"> [ON]</font>';
                    }
                }
            } else
            {
                echo "Не назначены<br/>";
            }
            echo "<a href='index.php?id=" . $id . "'>Назад</a><br/>";
            require_once ("../incfiles/end.php");
            break;

        case "trans":
            require_once ("../incfiles/head.php");
            include ("../pages/trans.$ras_pages");
            echo '<br/><br/><a href="' . htmlspecialchars(getenv("HTTP_REFERER")) . '">Назад</a><br/>';
            require_once ("../incfiles/end.php");
            break;

        case "say":
            if (empty($id))
            {
                require_once ("../incfiles/head.php");
                echo "Ошибка!<br/><a href='?'>В чат</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }

            // Проверка на спам
            $old = ($rights > 0 || $dostsadm = 1) ? 5: 10;
            if ($lastpost > ($realtime - $old))
            {
                require_once ("../incfiles/head.php");
                echo '<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог ' . $old . ' секунд<br/><br/><a href="index.php?id=' . $id . '">Назад</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }

            $type = mysql_query("SELECT * FROM `chat` WHERE `id` = '" . $id . "' LIMIT 1");
            $type1 = mysql_fetch_array($type);
            $tip = $type1['type'];
            switch ($tip)
            {
                case "r":
                    if (isset($_POST['submit']))
                    {
                        if (empty($_POST['msg']))
                        {
                            require_once ("../incfiles/head.php");
                            echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                            require_once ("../incfiles/end.php");
                            exit;
                        }

                        // Принимаем сообщение и записываем в базу
                        $msg = check(trim($_POST['msg']));
                        $msg = mb_substr($msg, 0, 500);
                        if ($_POST['msgtrans'] == 1)
                        {
                            $msg = trans($msg);
                        }
                        $agn = strtok($agn, ' ');
                        mysql_query("insert into `chat` values(0,'" . $id . "','','m','" . $realtime . "','" . $login . "','','0','" . $msg . "','" . $ipp . "','" . mysql_real_escape_string($agn) . "','','');");
                        if (empty($datauser['postchat']))
                        {
                            $fpst = 1;
                        } else
                        {
                            $fpst = $datauser['postchat'] + 1;
                        }
                        mysql_query("UPDATE `users` SET
						`postchat` = '" . $fpst . "',
						`lastpost` = '" . $realtime . "'
						WHERE `id` = '" . $user_id . "';");
                        if ($type1['dpar'] == "vik")
                        {
                            $protv = mysql_query("select * from `chat` where dpar='vop' and type='m' order by time desc;");
                            while ($protv2 = mysql_fetch_array($protv))
                            {
                                $prr[] = $protv2['id'];
                            }
                            $pro = mysql_query("select * from `chat` where dpar='vop' and type='m' and id='" . $prr[0] . "';");
                            $protv1 = mysql_fetch_array($pro);
                            $prr = array();
                            $ans = $protv1['realid'];
                            $vopr = mysql_query("select * from `vik` where id='" . $ans . "';");
                            $vopr1 = mysql_fetch_array($vopr);
                            $answer = $vopr1['otvet'];
                            if (!empty($msg) && !empty($answer) && $protv1['otv'] != 1)
                            {
                                if (preg_match("/$answer/i", "$msg"))
                                {
                                    $itg = $datauser['otvetov'] + 1;
                                    switch ($protv1['otv'])
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
                                    $balans = $datauser['balans'] + $bls;
                                    $otvtime = $realtime - $protv1['time'];
                                    if ($datauser['sex'] == "m")
                                    {
                                        $tx = "молодец! Ты угадал правильный ответ:  $answer за $otvtime секунд $pods ,и заработал $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    } else
                                    {
                                        $tx = "молодец! Ты угадала правильный ответ:  $answer за $otvtime секунд $pods ,и заработала $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    }
                                    $mtim = $realtime + 1;
                                    mysql_query("INSERT INTO `chat` VALUES(
'0','" . $id . "','','m','" . $mtim . "','Умник','" . $login . "','', '" . $tx . "', '127.0.0.1', 'Nokia3310', '','');");
                                    mysql_query("update `chat` set otv='1' where id='" . $protv1['id'] . "';");
                                    mysql_query("update `users` set otvetov='" . $itg . "',balans='" . $balans . "' where id='" . intval($_SESSION['uid']) . "';");
                                }
                            }
                        }
                        header("location: $home/chat/index.php?id=$id");
                    } else
                    {
                        require_once ("chat_header.php");
                        echo 'Добавление сообщения<br />(max. 500)';
                        echo '<div class="title1">' . $type1['text'] . '</div>';
                        echo "<form action='index.php?act=say&amp;id=" . $id . "' method='post'><textarea cols='40' rows='3' title='Введите текст сообщения' name='msg'></textarea><br/>";
                        if ($offtr != 1)
                        {
                            echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                        }
                        echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/></form>";
                        echo "<div class='title2'><a href='index.php?act=trans'>Транслит</a> | <a href='../str/smile.php'>Смайлы</a><br/>";
                        echo "</div><br />[0] <a href='index.php?id=" . $id . "' accesskey='0'>Назад</a>";
                    }
                    break;

                case "m":
                    $th = $type1['refid'];
                    $th2 = mysql_query("select * from `chat` where id= '" . $th . "';");
                    $th1 = mysql_fetch_array($th2);
                    if (isset($_POST['submit']))
                    {
                        $flt = $realtime - 10;
                        $af = mysql_query("select * from `chat` where type='m' and time>'" . $flt . "' and `from`= '" . $login . "';");
                        $af1 = mysql_num_rows($af);
                        if ($af1 != 0)
                        {
                            require_once ("../incfiles/head.php");
                            echo "Антифлуд!Вы не можете так часто добавлять сообщения<br/>Порог 10 секунд<br/><a href='index.php?id=" . $th . "'>Назад</a><br/>";
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
                        $to = $type1['from'];
                        $priv = intval($_POST['priv']);
                        $nas = check($_POST['nas']);
                        $msg = check(trim($_POST['msg']));

                        $msg = mb_substr($msg, 0, 500);
                        if ($_POST['msgtrans'] == 1)
                        {
                            $msg = trans($msg);
                        }

                        mysql_query("insert into `chat` values(0,'" . $th . "','','m','" . $realtime . "','" . $login . "','" . $to . "','" . $priv . "','" . $msg . "','" . $ipp . "','" . mysql_real_escape_string($agn) . "','" . $nas . "','');");
                        if (empty($datauser['postchat']))
                        {
                            $fpst = 1;
                        } else
                        {
                            $fpst = $datauser['postchat'] + 1;
                        }
                        mysql_query("update `users` set  postchat='" . $fpst . "' where id='" . intval($_SESSION['uid']) . "';");
                        if ($th1[dpar] == "vik")
                        {
                            $protv = mysql_query("select * from `chat` where dpar='vop' and type='m' order by time desc;");
                            while ($protv2 = mysql_fetch_array($protv))
                            {
                                $prr[] = $protv2['id'];
                            }
                            $pro = mysql_query("select * from `chat` where dpar='vop' and type='m' and id='" . $prr[0] . "';");
                            $protv1 = mysql_fetch_array($pro);
                            $prr = array();
                            $ans = $protv1['realid'];
                            $vopr = mysql_query("select * from `vik` where id='" . $ans . "';");
                            $vopr1 = mysql_fetch_array($vopr);
                            $answer = $vopr1['otvet'];
                            if (!empty($msg) && !empty($answer) && $protv1['otv'] != 1)
                            {
                                if (preg_match("/$answer/i", "$msg"))
                                {
                                    $itg = $datauser['otvetov'] + 1;
                                    switch ($protv1['otv'])
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
                                    $balans = $datauser['balans'] + $bls;
                                    $otvtime = $realtime - $protv1['time'];
                                    if ($datauser['sex'] == "m")
                                    {
                                        $tx = "молодец! Ты угадал правильный ответ:  $answer за $otvtime секунд $pods ,и заработал $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    } else
                                    {
                                        $tx = "молодец! Ты угадала правильный ответ:  $answer за $otvtime секунд $pods ,и заработала $bls баллов. Всего правильных ответов:<b>$itg</b>, твой игровой баланс $balans баллов.";
                                    }
                                    $mtim = $realtime + 1;
                                    mysql_query("INSERT INTO `chat` VALUES(
'0','" . $th . "','','m','" . $mtim . "','Умник','" . $login . "','', '" . $tx . "', '127.0.0.1', 'Nokia3310', '','');");
                                    mysql_query("update `chat` set otv='1' where id='" . $protv1['id'] . "';");
                                    mysql_query("update `users` set otvetov='" . $itg . "',balans='" . $balans . "' where id='" . intval($_SESSION['uid']) . "';");
                                }
                            }
                        }
                        header("location: $home/chat/index.php?id=$th");
                    } else
                    {
                        require_once ("chat_header.php");
                        $user = mysql_query("select * from `users` where name='" . $type1['from'] . "';");
                        $ruz = mysql_num_rows($user);
                        if ($ruz != 0)
                        {
                            $udat = mysql_fetch_array($user);
                            echo "<a href='../str/anketa.php?user=" . $udat['id'] . "'><b>$type1[from]</b></a>";
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
                            echo '<br/>';
                            if (!empty($udat['status']))
                            {
                                echo $udat['status'] . '<br/>';
                            }
                            if ($udat['sex'] == "m")
                            {
                                echo "Парень<br/>";
                            }
                            if ($udat['sex'] == "zh")
                            {
                                echo "Девушка<br/>";
                            }
                            if (!empty($udat['balans']))
                            {
                                echo "Игровой баланс: $udat[balans] баллов<br/>";
                            }
                            if ($udat['ban'] == "1" && $udat['bantime'] > $realtime || $udat['ban'] == "2")
                            {
                                echo "<font color='" . $cdinf . "'>Бан!</font><br/>";
                            }
                            if (empty($udat['nastroy']))
                            {
                                $nstr = "без настроения";
                            } else
                            {
                                $nstr = $udat['nastroy'];
                            }
                            echo "Настроение: $udat[nastroy]<br/>";
                        }
                        echo "Добавление сообщения в комнату <b>$th1[text]</b><br />для <b>$type1[from]</b>(max. 500):<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post'>";
                        echo "<textarea cols='40' rows='3' title='Введите ответ' name='msg'></textarea><br/>";
                        if ($offtr != 1)
                        {
                            echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения
      <br/>";
                        }
                        echo '<select name="priv">';
                        echo '<option value="0">Всем</option>';
                        echo '<option value="1">Приватно</option>';
                        echo '</select><br/>';
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
                            echo "<br/><a href='../str/pradd.php?act=write&amp;adr=" . $udat['id'] . "'>Написать в приват</a><br/>";
                            if ($dostcmod == 1)
                            {
                                echo "<a href='../" . $admp . "/zaban.php?do=ban&amp;id=" . $udat['id'] . "&amp;chat'>Пнуть</a><br/>";
                            }
                        }
                        echo "<a href='index.php?id=" . $type1['refid'] . "'>Назад</a><br/>";
                    }
                    break;

                default:
                    require_once ("../incfiles/head.php");
                    echo "Ошибка!<br/>&#187;<a href='?'>В чат</a><br/>";
                    break;
            }
            require_once ('chat_footer.php');
            break;

        case "chpas":
            $_SESSION['intim'] = "";
            header("location: $home/chat/index.php?id=$id");
            break;

        case "pass":
            $parol = check($_POST['parol']);
            $_SESSION['intim'] = $parol;
            mysql_query("update `users` set alls='" . $parol . "' where id='" . intval($_SESSION['uid']) . "';");
            header("location: $home/chat/index.php?id=$id");
            break;

        default:
            if (!empty($id))
            {
                // Отображаем комнату Чата
                require_once ('room.php');
            } else
            {
                // Отображаем прихожую Чата
                require_once ('hall.php');
            }
    }
} else
{
    require_once ("../incfiles/head.php");
    echo "Вы не авторизованы!<br/><a href='../in.php'>Вход</a><br/>";
    require_once ("../incfiles/end.php");
}

?>