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

defined('_IN_JOHNCMS') or die('Error:restricted access');

////////////////////////////////////////////////////////////
// Комнаты Чата                                           //
////////////////////////////////////////////////////////////
$type = mysql_query("select * from `chat` where `id`= '" . $id . "';");
$type1 = mysql_fetch_array($type);
$tip = $type1['type'];
switch ($tip)
{
    case "r":
        if ($type1[dpar] != "in")
        {
            $_SESSION['intim'] = "";
        }
        if ($type1[dpar] == "in")
        {
            if (empty($_SESSION['intim']))
            {
                require_once ("../incfiles/head.php");
                echo "<form action='index.php?act=pass&amp;id=" . $id .
                    "' method='post'><br/>Введите пароль(max. 10):<br/><input type='text' name='parol' maxlength='10'/><br/><input type='submit' name='submit' value='Ok!'/><br/></form><a href='index.php'>В чат</a><br/>";
                require_once ("../incfiles/end.php");
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

            ////////////////////////////////////////////////////////////
            // Первая подсказка Умника                                //
            ////////////////////////////////////////////////////////////
            if ($prov[otv] == "2" && $prov[time] < intval($realtime - 50)) // Время ожидания от начала до первой подсказки
            {
                $vopr = mysql_query("select * from `vik` where id='" . $prov['realid'] . "';");
                $vopr1 = mysql_fetch_array($vopr);
                $ans = $vopr1[otvet];
                $b = mb_strlen($ans);
                if ($b < 4)
                {
                    $e = 4;
                } else
                {
                    $e = 3;
                }
                $d = round($b / 4);
                $c = mb_substr($ans, 0, $d);
                for ($i = $d; $i < $b; ++$i)
                {
                    $c = "$c*";
                }
                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm','" . $realtime . "','Умник','','', 'Подсказка " . $c . "', '127.0.0.1', 'Nokia3310', '', '');");
                mysql_query("update `chat` set otv='" . $e . "' where id='" . $prov[id] . "';");
            }

            ////////////////////////////////////////////////////////////
            // Вторая подсказка Умника                                //
            ////////////////////////////////////////////////////////////
            if ($prov[otv] == "3" && $prov[time] < intval($realtime - 100)) // Время ожидания от начала до второй подсказки
            {
                $vopr = mysql_query("select * from `vik` where id='" . $prov[realid] . "';");
                $vopr1 = mysql_fetch_array($vopr);
                $ans = $vopr1[otvet];
                $b = mb_strlen($ans);
                $d = (round($b / 3)) + 1;
                //if ($d == 1)
                //    $d = 2;
                $c = mb_substr($ans, 0, $d);
                for ($i = $d; $i < $b; ++$i)
                {
                    $c = "$c*";
                }
                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm','" . $realtime . "','Умник','','', 'Вторая подсказка " . $c . "', '127.0.0.1', 'Nokia3310', '', '');");
                mysql_query("update `chat` set otv='4' where id='" . $prov[id] . "';");
            }

            if ($prov[otv] == "5" && $prov[time] < intval($realtime - 15)) // Пауза перед новым вопросом
            {
                $v = mysql_query("select * from `vik` ;");
                $c = mysql_num_rows($v);
                $num = rand(1, $c);
                $vik = mysql_query("select * from `vik` where id='" . $num . "';");
                $vik1 = mysql_fetch_array($vik);
                $vopros = $vik1[vopros];
                $len = mb_strlen($vik1[otvet]);
                mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','" . $num . "', 'm','" . $realtime . "','Умник','','vop', '<b>Вопрос: " . $vopros . " (" . $len . " букв)</b>', '127.0.0.1', 'Nokia3310', '', '2');");
            }

            ////////////////////////////////////////////////////////////
            // Диалог Умника в викторине                              //
            ////////////////////////////////////////////////////////////
            if (!empty($prov[time]) && $prov[time] < intval($realtime - 150)) // Общее время ожидания ответа на вопрос

            {
                // Задаем вопрос в викторине
                if ($prov[otv] == "1")
                {
                    $v = mysql_query("select * from `vik` ;");
                    $c = mysql_num_rows($v);
                    $num = rand(1, $c);
                    $vik = mysql_query("select * from `vik` where id='" . $num . "';");
                    $vik1 = mysql_fetch_array($vik);
                    $vopros = $vik1[vopros];
                    $len = mb_strlen($vik1[otvet]);
                    mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','" . $num . "', 'm','" . $realtime . "','Умник','','vop', '<b>Вопрос: " . $vopros . " (" . $len . " букв)</b>', '127.0.0.1', 'Nokia3310', '', '2');");
                }
                // Если не было правильного ответа, то выводим сообшение
                if ($prov[otv] == "4")
                {
                    mysql_query("INSERT INTO `chat` VALUES(
'0', '" . $id . "','', 'm','" . $realtime . "','Умник','','', 'Время истекло! Вопрос не был угадан!','127.0.0.1', 'Nokia3310', '', '1');");
                    mysql_query("update `chat` set otv='1' where id='" . $prov[id] . "';");
                }
            }
        }
        $refr = rand(0, 999);
        $arefresh = true;
        require_once ('chat_header.php');
        if ($datauser['carea'] == 1)
        {
            echo "<form action='index.php?act=say&amp;id=" . $id . "' method='post'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Сказать'/><br/></form>";
        } else
        {
            echo '[1] <a href="index.php?act=say&amp;id=' . $id . '" accesskey="1">Сказать</a><br />';
        }
        echo '[2] <a href="index.php?id=' . $id . '&amp;refr=' . $refr . '" accesskey="2">Обновить</a><br/>';
        echo '<div class="title1">' . $type1[text] . '</div>';
        $q2 = mysql_query("select * from `chat` where type='m' and refid='" . $id . "';");
        while ($masss = mysql_fetch_array($q2))
        {
            $q3 = mysql_query("select * from `users` where name='" . $masss[from] . "';");
            $q4 = mysql_fetch_array($q3);
            $pasw = $q4[alls];
            if (($masss[dpar] != 1 || $masss[to] == $login || $masss[from] == $login || $dostsadm == 1) && ($ign1 == 0 || $dostcmod == 1))
            {
                if ($type1['dpar'] != "in" || $pasw == $datauser['alls'])
                {
                    $cm[] = $masss['id'];
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
        $q1 = mysql_query("select * from `chat` where type='m' and `refid`='" . $id . "'  order by time desc ;");
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
                    if ($type1[dpar] != "in" || $psw == $datauser['alls'])
                    {
                        if ($mass[from] != "Умник")
                        {
                            $uz = @mysql_query("select * from `users` where name='" . $mass[from] . "';");
                            $mass1 = @mysql_fetch_array($uz);
                        }
                        echo '<div class="text">';
                        if ($mass[from] != "Умник")
                        {
                            // Выводим значек пола
                            //switch ($mass1[sex])
                            //{
                            //    case "m":
                            //        echo "<img src='../images/m.gif' alt=''/>";
                            //        break;
                            //    case "zh":
                            //        echo "<img src='../images/f.gif' alt=''/>";
                            //        break;
                            //}
                        }
                        if ($mass[from] != "Умник")
                        {
                            if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1[id]))
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

                        // Дата и время
                        $vrp = $mass[time] + $sdvig * 3600;
                        $vr = date("H:i", $vrp); // Только время
                        //$vr = date("d.m.Y / H:i", $vrp); // Дата и время

                        if ($mass[from] != "Умник")
                        {
                            // Выводим метку должности
                            switch ($mass1[rights])
                            {
                                case 7:
                                    echo " [Adm] ";
                                    break;
                                case 6:
                                    echo " [Smd] ";
                                    break;
                                case 2:
                                    echo " [Mod] ";
                                    break;
                                case 1:
                                    echo " [Kil] ";
                                    break;
                            }

                            // Выводим метку Онлайн / Офлайн
                            //$ontime = $mass1[lastdate];
                            //$ontime2 = $ontime + 300;
                            //if ($realtime > $ontime2)
                            //{
                            //    echo "<font color='" . $coffs . "'> [Off]</font>";
                            //} else
                            //{
                            //    echo "<font color='" . $cons . "'> [ON]</font>";
                            //}

                            // Выводим метку именнинника
                            //if ($mass1[dayb] == $day && $mass1[monthb] == $mon)
                            //{
                            //    echo "<font color='" . $cdinf . "'>!!!</font><br/>";
                            //}
                        }
                        echo "($vr): ";
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

                        // Удаление постов и информация о браузере
                        //if ($dostcmod == 1)
                        //{
                        //    echo "<a href='index.php?act=delpost&amp;id=" . $mass[id] . "'>Удалить</a><br/>";
                        //    echo "$mass[ip] - $mass[soft]<br/>";
                        //}
                        echo "</div>";
                    }
                }
            }
            if (($mass[dpar] != 1 || $mass[to] == $login || $mass[from] == $login || $dostsadm == 1) && ($ign1 == 0 || $dostcmod == 1))
            {
                if ($type1[dpar] != "in" || $psw == $datauser['alls'])
                {
                    ++$i;
                }
            }
        }
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
        echo '<div class="title2"><a href="who.php?id=' . $id . '">Кто в чате(' . wch($id) . '/' . wch() . ')</a></div>';
        echo '[0] <a href="index.php?" accesskey="0">Прихожая</a><br/>';
        if ($type1[dpar] == "in")
        {
            echo '[3] <a href="index.php?act=chpas&amp;id=' . $id . '" accesskey="3">Сменить пароль</a><br/>';
        }
        if ($dostcmod == 1)
        {
            echo '[5] <a href="index.php?act=room&amp;id=' . $id . '" accesskey="5">Очистить комнату</a><br/>';
        }
        require_once ('chat_footer.php');
        break;

    default:
        require_once ("../incfiles/head.php");
        echo "Ошибка!<br/>&#187;<a href='index.php?'>В чат</a><br/>";
        require_once ('chat_footer.php');
}

?>