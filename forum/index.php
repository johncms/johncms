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

$textl = 'Форум';
$headmod = "forum";
require_once ("../incfiles/core.php");

// Закрываем доступ к форуму
if (!$set['mod_forum'] && $dostadm != 1)
{
    require_once ("../incfiles/head.php");
    echo '<p>' . $set['mod_forum_msg'] . '</p>';
    require_once ("../incfiles/end.php");
    exit;
}

if ($user_id)
{
    $tti = round(($datauser['ftime'] - $realtime) / 60);
    if ($id)
    {
        $where = "forum,$id";
    } else
    {
        $where = "forum";
    }
    mysql_query("INSERT INTO `count`  SET
	`ip`='" . $ipp . "',
	`browser`='" . $agn . "',
	`time`='" . $realtime . "',
	`where`='" . $where . "',
	`name`='" . $login . "';");
}

$act = isset($_GET['act']) ? $_GET['act'] : '';
$do = array('new', 'who', 'addfile', 'file', 'moders', 'per', 'fmoder', 'ren', 'deltema', 'vip', 'close', 'delpost', 'editpost', 'nt', 'tema', 'loadtem', 'say', 'post', 'read', 'faq', 'trans', 'massdel');
if (in_array($act, $do))
{
    require_once ($act . '.php');
} else
{
    require_once ("../incfiles/head.php");
    // Если форум закрыт, то для Админов выводим напоминание
    if (!$set['mod_forum'])
        echo '<p><font color="#FF0000"><b>Форум закрыт!</b></font></p>';
    if (!$user_id)
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
    if ($dostfmod == 1)
    {
        $fm = mysql_query("select `id` from `forum` where type='t' and moder!='1';");
        $fm1 = mysql_num_rows($fm);
        if ($fm1 != 0)
        {
            echo "Модерацию ожидают <a href='index.php?act=fmoder'>$fm1</a> тем<br/>";
        }
    }

    if ($id)
    {
        $type = mysql_query("SELECT * FROM `forum` WHERE `id`= '" . $id . "' LIMIT 1;");
        $type1 = mysql_fetch_array($type);
        $tip = $type1['type'];
        switch ($tip)
        {
            case "f":
                ////////////////////////////////////////////////////////////
                // Список разделов                                        //
                ////////////////////////////////////////////////////////////

                // Ссылка на Новые темы
                echo '<p><a href="index.php?act=new">' . ($user_id ? 'Непрочитанное&nbsp;(' . forum_new() . ')' : 'Последние 10 тем') . '</a></p>';

                // Панель навигации
                echo '<div class="phdr">';
                echo '<a href="index.php">Форум</a> &gt;&gt; <b>' . $type1['text'] . '</b>';
                echo '</div>';
                $req = mysql_query("SELECT `id`, `text` FROM `forum` WHERE `type`='r' AND `refid`='" . $id . "' ORDER BY `realid`;");
                $total = mysql_num_rows($req);
                while ($mass1 = mysql_fetch_array($req))
                {
                    echo ceil(ceil($i / 2) - ($i / 2)) == 0 ? '<div class="list1">' : '<div class="list2">';
                    $coltem = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `moder` = '1' AND `refid` = '" . $mass1['id'] . "'");
                    $coltem1 = mysql_result($coltem, 0);
                    echo '<a href="?id=' . $mass1['id'] . '">' . $mass1['text'] . '</a>';
                    if ($coltem1 > 0)
                    {
                        echo " [$coltem1]";
                    }
                    echo "</div>";
                    ++$i;
                }
                echo '<div class="phdr">Всего: ' . $total . '</div>';
                break;

            case "r":
                ////////////////////////////////////////////////////////////
                // Список тем                                             //
                ////////////////////////////////////////////////////////////
                $qz = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='t' AND `refid`='" . $id . "' AND `moder`='1'" . ($dostadm == 1 ? '' : " AND `close`!='1'"));
                $coltem = mysql_result($qz, 0);
                // Ссылка на Новые темы
                echo '<p><a href="index.php?act=new">' . ($user_id ? 'Непрочитанное&nbsp;(' . forum_new() . ')' : 'Последние 10 тем') . '</a></p>';
                // Панель навигации
                $forum = mysql_query("SELECT * FROM `forum` WHERE `type`='f' AND `id`='" . $type1['refid'] . "';");
                $forum1 = mysql_fetch_array($forum);
                echo '<div class="phdr">';
                echo '<a href="index.php">Форум</a> &gt;&gt; <a href="index.php?id=' . $type1['refid'] . '">' . $forum1['text'] . '</a> &gt;&gt; <b>' . $type1['text'] . '</b>';
                echo '</div>';
                if ($user_id && !$ban['1'] && !$ban['11'])
                {
                    echo '<div class="gmenu"><a href="index.php?act=nt&amp;id=' . $id . '">Новая тема</a></div>';
                }
                $q1 = mysql_query("SELECT `id`, `from`, `time`, `vip`, `close`, `edit`, `text` FROM `forum` WHERE `type`='t'" . ($dostadm == 1 ? '' : " AND `close`!='1'") . " AND `refid`='" . $id .
                    "' AND `moder`='1'  ORDER BY `vip` DESC, `time` DESC LIMIT " . $start . "," . $kmess . ";");
                while ($mass = mysql_fetch_array($q1))
                {
                    echo ceil(ceil($i / 2) - ($i / 2)) == 0 ? '<div class="list1">' : '<div class="list2">';
                    $colmes = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $mass['id'] . "'ORDER BY time DESC;");
                    $nikuser = mysql_query("SELECT `from` FROM `forum` WHERE `type` = 'm' AND `close` != '1' AND `refid` = '" . $mass['id'] . "'ORDER BY time DESC LIMIT 1;");
                    $colmes1 = mysql_result($colmes, 0);
                    $cpg = ceil($colmes1 / $kmess);
                    $colmes1 = $colmes1 - 1;
                    if ($colmes1 < 0)
                    {
                        $colmes1 = 0;
                    }
                    $nam = mysql_fetch_array($nikuser);
                    // Выводим список тем
                    if ($mass['vip'] == 1)
                    {
                        echo '<img src="../theme/' . $skin . '/images/pt.gif" alt=""/>';
                    } elseif ($mass['edit'] == 1)
                    {
                        echo '<img src="../theme/' . $skin . '/images/tz.gif" alt=""/>';
                    } elseif ($mass['close'] == 1)
                    {
                        echo '<img src="../theme/' . $skin . '/images/dl.gif" alt=""/>';
                    } else
                    {
                        $np = mysql_query("SELECT * FROM `cms_forum_rdm` WHERE `time`>='" . $mass['time'] . "' AND `topic_id`='" . $mass['id'] . "' AND `user_id`='" . $user_id . "';");
                        $np1 = mysql_num_rows($np);
                        if ($np1 == 0)
                        {
                            echo '<img src="../theme/' . $skin . '/images/np.gif" alt=""/>';
                        } else
                        {
                            echo '<img src="../theme/' . $skin . '/images/op.gif" alt=""/>';
                        }
                    }
                    echo "&nbsp;<a href='index.php?id=$mass[id]'>$mass[text]</a> [$colmes1]";
                    if ($cpg > 1)
                    {
                        echo "<a href='index.php?id=$mass[id]&amp;page=$cpg'>&nbsp;&gt;&gt;</a>";
                    }
                    echo '<div class="sub">';
                    echo $mass['from'];
                    if (!empty($nam['from']))
                    {
                        echo '&nbsp;/&nbsp;' . $nam['from'];
                    }
                    $vrp = $mass['time'] + $sdvig * 3600;
                    echo ' <font color="#777777">' . date("d.m.y / H:i", $vrp) . "</font></div></div>";
                    ++$i;
                }
                echo '<div class="phdr">Всего: ' . $coltem . '</div>';
                if ($coltem > $kmess)
                {
                    echo '<p>' . pagenav('index.php?id=' . $id . '&amp;', $start, $coltem, $kmess) . '</p>';
                    echo '<p><form action="index.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
                }
                break;

            case "t":
                ////////////////////////////////////////////////////////////
                // Читаем топик                                           //
                ////////////////////////////////////////////////////////////
                if ($user_id)
                {
                    //блок, фиксирующий факт прочтения топика
                    $req = mysql_query("SELECT COUNT(*) FROM `cms_forum_rdm` WHERE `topic_id`='" . $id . "' AND `user_id`='" . $user_id . "';");
                    if (mysql_result($req, 0) == 1)
                    {
                        // Обновляем время метки о прочтении
                        mysql_query("UPDATE `cms_forum_rdm` SET `time`='" . $realtime . "' WHERE `topic_id`='" . $id . "' AND `user_id`='" . $user_id . "';");
                    } else
                    {
                        // Ставим метку о прочтении
                        mysql_query("INSERT INTO `cms_forum_rdm` SET  `topic_id`='" . $id . "', `user_id`='" . $user_id . "', `time`='" . $realtime . "';");
                    }
                }
                // Ссылка на Новые темы
                echo '<p><a href="index.php?act=new">' . ($user_id ? 'Непрочитанное&nbsp;(' . forum_new() . ')' : 'Последние 10 тем') . '</a></p>';
                if ($dostsadm != 1 && $type1['close'] == 1)
                {
                    echo "<font color='#FF0000'>Тема удалена!</font><br/><a href='?id=" . $type1['refid'] . "'>В раздел</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                $req = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='m' AND `refid`='" . $id . "'" . ($dostadm == 1 ? '' : " AND `close` != '1'"));
                $colmes = mysql_result($req, 0);
                $order = $datauser['upfp'] == 1 ? 'DESC':
                'ASC';
                $q1 = mysql_query("SELECT * FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $id . ($dostadm == 1 ? "'" : "' AND `close` != '1'") . "  ORDER BY `time` " . $order . " LIMIT " . $start . ", " . $kmess . " ;");
                $q3 = mysql_query("SELECT `id`, `refid`, `text` FROM `forum` WHERE `id` = '" . $type1['refid'] . "' LIMIT 1");
                $razd = mysql_fetch_array($q3);
                $q4 = mysql_query("SELECT `id`, `text` FROM `forum` WHERE `id` = '" . $razd['refid'] . "' LIMIT 1");
                $frm = mysql_fetch_array($q4);

                // Панель навигации
                echo '<div class="phdr">';
                echo '<a href="index.php">Форум</a> &gt;&gt; <a href="index.php?id=' . $frm['id'] . '">' . $frm['text'] . '</a> &gt;&gt; <a href="index.php?id=' . $razd['id'] . '">' . $razd['text'] . '</a>';
                echo '</div>';

                // Выводим название топика
                echo "<br /><b>$type1[text]</b><br/>Сообщений: $colmes<br/>";
                if ($type1['edit'] == 1)
                {
                    echo '<b><font color="#FF0000">Тема закрыта</font></b><br/>';
                } elseif ($type1['close'] == 1)
                {
                    echo '<b><font color="#FF0000">Тема удалена</font></b><br/>';
                }
                if ($type1['edit'] != 1 && $_SESSION['uid'] != "" && $upfp == 1)
                {
                    if ($datauser['farea'] == 1 && $datauser['postforum'] >= 1)
                    {
                        echo "<div class='e'>Написать<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
                        echo "<input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
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
                echo '<a name="up" id="up"></a><a href="#down">Вниз</a><hr />';
                if ($dostsmod == 1)
                    echo '<form action="index.php?act=massdel" method="post">';
                while ($mass = mysql_fetch_array($q1))
                {
                    if ($i >= 0 && $i < $colmes)
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
                        $uz = mysql_query("SELECT `id`, 'from', `sex`, `rights`, `lastdate`, `dayb`, `status`, `datereg` FROM `users` WHERE `name`='" . $mass['from'] . "';");
                        $mass1 = mysql_fetch_array($uz);
                        echo $div;
                        echo $mass1['datereg'] > $realtime - 86400 ? '<img src="../theme/' . $skin . '/images/add.gif" alt=""/>&nbsp;' : '';
                        switch ($mass1['sex'])
                        {
                            case 'm':
                                echo '<img src="../theme/' . $skin . '/images/m.gif" alt=""/>';
                                break;
                            case 'zh':
                                echo '<img src="../theme/' . $skin . '/images/f.gif" alt=""/>';
                                break;
                        }
                        if ((!empty($_SESSION['uid'])) && ($_SESSION['uid'] != $mass1['id']))
                        {
                            echo '<a href="../str/anketa.php?user=' . $mass1['id'] . '&amp;fid=' . $mass['id'] . '"><b>' . $mass['from'] . '</b></a> ';
                            echo '<a href="index.php?act=say&amp;id=' . $mass['id'] . '"> [о]</a> <a href="index.php?act=say&amp;id=' . $mass['id'] . '&amp;cyt"> [ц]</a>';
                        } else
                        {
                            echo "<b>$mass[from]</b>";
                        }
                        $vrp = $mass['time'] + $sdvig * 3600;
                        $vr = date("d.m.Y / H:i", $vrp);
                        switch ($mass1['rights'])
                        {
                            case 7:
                                echo " Adm ";
                                break;
                            case 6:
                                echo " Smd ";
                                break;
                            case 3:
                                echo " Mod ";
                                break;
                            case 1:
                                echo " Kil ";
                                break;
                        }
                        $ontime = $mass1['lastdate'];
                        $ontime2 = $ontime + 300;
                        if ($realtime > $ontime2)
                        {
                            echo '<font color="#FF0000"> [Off]</font>';
                        } else
                        {
                            echo '<font color="#00AA00"> [ON]</font>';
                        }
                        echo ' <font color="#999999">(' . $vr . ')</font><br/>';
                        if (!empty($mass1['status']))
                            echo '<div class="status"><img src="../theme/' . $skin . '/images/star.gif" alt=""/>&nbsp;' . $mass1['status'] . '</div>';
                        if ($mass1['dayb'] == $day && $mass1['monthb'] == $mon)
                        {
                            echo '<font color="#FF0000">Именины!!!</font><br/>';
                        }
                        if ($mass['close'] == 1)
                        {
                            echo '<font color="#FF0000">Пост удалён!</font><br/>';
                        }
                        if (!empty($mass['to']))
                        {
                            echo "$mass[to], ";
                        }
                        ////////////////////////////////////////////////////////////
                        // Вывод текста поста                                     //
                        ////////////////////////////////////////////////////////////
                        $text = $mass['text'];
                        if (mb_strlen($text) >= 500)
                        {
                            // Если текст длинный, обрезаем и даем ссылку на полный вариант
                            $text = mb_substr($text, 0, 400);
                            $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                            $text = str_replace("\r\n", "<br/>", $text);
                            $text = tags($text);
                            echo $text . '...<br /><a href="index.php?act=post&amp;s=' . $page . '&amp;id=' . $mass['id'] . '">Читать все &gt;&gt;</a>';
                        } else
                        {
                            // Или, обрабатываем тэги и выводим весь текст
                            $text = htmlentities($text, ENT_QUOTES, 'UTF-8');
                            if ($offsm != 1)
                            {
                                $text = smiles($text);
                                $text = smilescat($text);
                                if ($mass['from'] == nickadmina || $mass['from'] == nickadmina2 || $mass1['rights'] >= 1)
                                {
                                    $text = smilesadm($text);
                                }
                            }
                            $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                            $text = str_replace("\r\n", "<br/>", $text);
                            $text = tags($text);
                            echo $text;
                        }
                        if ($mass['kedit'] >= 1)
                        {
                            $diz = $mass['tedit'] + $sdvig * 3600;
                            $dizm = date("d.m /H:i", $diz);
                            echo "<br /><font color='#999999'>Посл. изм. <b>$mass[edit]</b>  ($dizm)<br />Всего изм.:<b> $mass[kedit]</b></font>";
                        }
                        if (!empty($mass['attach']))
                        {
                            $fls = filesize("./files/$mass[attach]");
                            $fls = round($fls / 1024, 2);
                            echo "<br /><font color='#999999'>Прикреплённый файл:<br /><a href='index.php?act=file&amp;id=" . $mass['id'] . "'>$mass[attach]</a> ($fls кб.)<br/>";
                            echo 'Скачано: ' . $mass['dlcount'] . ' раз.</font>';
                        }
                        $lp = mysql_query("select `from`, `id` from `forum` where type='m' and refid='" . $id . "'  order by time desc LIMIT 1;");
                        $arr1 = mysql_fetch_array($lp);
                        $tpp = $realtime - 300;
                        if (($dostfmod == 1) || (($arr1['from'] == $login) && ($arr1['id'] == $mass['id']) && ($mass['time'] > $tpp)))
                        {
                            echo '<br /><a href="index.php?act=editpost&amp;id=' . $mass['id'] . '&amp;start=' . $start . '">Изменить</a>';
                        }
                        if ($dostfmod == 1)
                        {
                            echo "<br /><input type='checkbox' name='delch[]' value='" . $mass['id'] . "'/>&nbsp;<a href='?act=delpost&amp;id=" . $mass['id'] . "'>Удалить</a><br/>";
                            echo "$mass[ip] - $mass[soft]<br/>";
                        }
                        echo '</div>';
                    }
                    ++$i;
                }
                echo '<hr /><div id="down"><a href="#up">Вверх</a></div>';
                if ($type1['edit'] != 1 && $user_id && $upfp != 1 && !$ban['1'] && !$ban['11'])
                {
                    if ($datauser['farea'] == 1 && $datauser['postforum'] >= 1)
                    {
                        echo "<div class='e'>Написать<br/><form action='index.php?act=say&amp;id=" . $id . "' method='post' enctype='multipart/form-data'><textarea cols='20' rows='2' title='Введите текст сообщения' name='msg'></textarea><br/>";
                        echo "<input type='checkbox' name='addfiles' value='1' /> Добавить файл<br/>";
                        if ($offtr != 1)
                        {
                            echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
                        }
                        echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/><br/></form></div>";
                    } else
                    {
                        echo '<a href="?act=say&amp;id=' . $id . '&amp;start=' . $start . '">Написать</a><br />';
                    }
                }
                if ($colmes > $kmess)
                {
                    echo '<p>' . pagenav('index.php?id=' . $id . '&amp;', $start, $colmes, $kmess) . '</p>';
                    echo '<p><form action="index.php" method="get"><input type="hidden" name="id" value="' . $id . '"/><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
                }
                if ($dostfmod == 1)
                {
                    echo '<p><div class="func">';
                    if ($type1['moder'] != 1)
                    {
                        echo "<a href='index.php?act=fmoder&amp;id=" . $id . "'>Принять тему</a><br/>";
                    }
                    echo "<a href='index.php?act=ren&amp;id=" . $id . "'>Переименовать тему</a><br/>";
                    if ($type1['edit'] == 1)
                    {
                        echo "<a href='index.php?act=close&amp;id=" . $id . "'>Открыть тему</a><br/>";
                    } else
                    {
                        echo "<a href='index.php?act=close&amp;id=" . $id . "&amp;closed'>Закрыть тему</a><br/>";
                    }
                    echo "<a href='index.php?act=deltema&amp;id=" . $id . "'>Удалить тему</a><br/>";
                    if ($type1['vip'] == 1)
                    {
                        echo "<a href='index.php?act=vip&amp;id=" . $id . "'>Открепить тему</a>";
                    } else
                    {
                        echo "<a href='index.php?act=vip&amp;id=" . $id . "&amp;vip'>Закрепить тему</a>";
                    }
                    echo "<br/><a href='index.php?act=per&amp;id=" . $id . "'>Переместить тему</a><br /><br />";
                    if ($dostsmod == 1)
                    {
                        echo "<input type='submit' value='Удалить отмеченные'/></div></p>";
                        echo '</form>';
                    } else
                    {
                        echo '</div></p>';
                    }
                }
                if (!empty($_SESSION['uid']))
                {
                    echo "<a href='index.php?act=who&amp;id=" . $id . "'>Кто здесь?(" . wfrm($id) . ")</a><br/>";
                }
                echo "<a href='index.php?act=tema&amp;id=" . $id . "'>Скачать тему</a><br/>";
                break;

            default:
                echo '<p><b>Ошибка!</b><br />Тема удалена или не существует!</p>';
                break;
        }
    } else
    {
        ////////////////////////////////////////////////////////////
        // Список Форумов                                         //
        ////////////////////////////////////////////////////////////

        // Ссылка на Новые темы
        echo '<p><a href="index.php?act=new">' . ($user_id ? 'Непрочитанное&nbsp;(' . forum_new() . ')' : 'Последние 10 тем') . '</a></p>';

        // Панель навигации
        echo '<div class="phdr">';
        echo '<b>Форум</b></div>';
        $req = mysql_query("SELECT `id`, `text` FROM `forum` WHERE `type`='f' ORDER BY `realid`");
        while ($mass = mysql_fetch_array($req))
        {
            echo ceil(ceil($i / 2) - ($i / 2)) == 0 ? '<div class="list1">' : '<div class="list2">';
            $colraz = mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type`='r' and `refid`='" . $mass['id'] . "';");
            $colraz1 = mysql_result($colraz, 0);
            echo '<a href="index.php?id=' . $mass['id'] . '">' . $mass['text'] . '</a> [' . $colraz1 . ']</div>';
            ++$i;
        }
        echo '<div class="phdr">' . ($user_id ? '<a href="index.php?act=who">Кто в форуме</a>' : 'Кто в форуме') . '(' . wfrm() . ')</div>';
    }

    // Навигация внизу страницы
    echo '<p>' . ($id ? '<a href="index.php">В Форум</a><br />' : '') . '<a href="search.php">Поиск по форуму</a>';
    if (!$id)
    {
        echo '<br /><a href="../str/usset.php?act=forum">Настройки форума</a><br/>';
        echo '<a href="index.php?act=read">Правила форума</a><br/>';
        echo '<a href="index.php?act=moders&amp;id=' . $id . '">Модераторы</a><br />';
        echo '<a href="index.php?act=faq">FAQ</a>';
    }
    echo '</p>';

    if (empty($_SESSION['uid']))
    {
        if ((empty($_SESSION['uppost'])) || ($_SESSION['uppost'] == 0))
        {
            echo "<a href='index.php?id=" . $id . "&amp;page=" . $page . "&amp;newup'>Новые вверху</a><br/>";
        } else
        {
            echo "<a href='index.php?id=" . $id . "&amp;page=" . $page . "&amp;newdown'>Новые внизу</a><br/>";
        }
    }
}
require_once ("../incfiles/end.php");

?>