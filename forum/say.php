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

// Проверка на спам
$old = ($rights > 0 || $dostsadm = 1) ? 10 : 30;
if ($lastpost > ($realtime - $old))
{
    require_once ("../incfiles/head.php");
    echo '<p><b>Антифлуд!</b><br />Вы не можете так часто писать<br/>Порог ' . $old . ' секунд<br/><br/><a href="?id=' . $id . '&amp;start=' . $start . '">Назад</a></p>';
    require_once ("../incfiles/end.php");
    exit;
}

$agn1 = strtok($agn, ' ');
$type = mysql_query("SELECT * FROM `forum` WHERE `id`= '" . $id . "';");
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
            if (empty($_POST['msg']))
            {
                require_once ("../incfiles/head.php");
                echo '<p>Вы не ввели сообщение!</p><p><a href="index.php?act=say&amp;id=' . $id . '">&lt;&lt; Повторить</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            $msg = trim($_POST['msg']);
            if ($_POST['msgtrans'] == 1)
            {
                $msg = trans($msg);
            }
            mysql_query("INSERT INTO `forum` SET
			`refid`='" . $id . "',
			`type`='m',
			`time`='" . $realtime . "',
			`from`='" . $login . "',
			`ip`='" . $ipp . "',
			`soft`='" . mysql_real_escape_string($agn1) . "',
			`text`='" . mysql_real_escape_string($msg) . "';");
            $fadd = mysql_insert_id();
            mysql_query("UPDATE `forum` SET  `time`='" . $realtime . "' WHERE `id`='" . $id . "';");
            $fpst = $datauser['postforum'] + 1;
            mysql_query("UPDATE `users` SET
			`postforum`='" . $fpst . "',
			`lastpost` = '" . $realtime . "'
			WHERE `id`='" . $user_id . "';");
            $pa = mysql_query("select `id` from `forum` where type='m' and refid= '" . $id . "';");
            $pa2 = mysql_num_rows($pa);
            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                $page = 1;
            } else
            {
                $page = ceil($pa2 / $kmess);
            }
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
            ////////////////////////////////////////////////////////////
            // Форма добавления сообщения                             //
            ////////////////////////////////////////////////////////////
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
            echo '<div class="phdr">Тема: <b>' . $type1['text'] . '</b></div>';
            echo '<form action="index.php?act=say&amp;id=' . $id . '" method="post" enctype="multipart/form-data">';
            echo '<div class="gmenu"><b>Сообщение:</b><br /><textarea cols="24" rows="4" title="Введите текст сообщения" name="msg"></textarea><br />';
            echo '<input type="checkbox" name="addfiles" value="1" /> Добавить файл<br/>';
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            echo "<input type='submit' title='Нажмите для отправки' name='submit' value='Отправить'/></div></form>";
        }
        echo '<div class="bmenu"><a href="index.php?act=trans">Транслит</a> | <a href="../str/smile.php">Смайлы</a></div>';
        echo '<p><a href="?id=' . $id . '&amp;start=' . $start . '">Назад</a></p>';
        break;

    case "m":
        $th = $type1['refid'];
        $th2 = mysql_query("select * from `forum` where `id`= '" . $th . "';");
        $th1 = mysql_fetch_array($th2);
        if (isset($_POST['submit']))
        {
            if (empty($_POST['msg']))
            {
                require_once ("../incfiles/head.php");
                echo "Вы не ввели сообщение!<br/><a href='index.php?act=say&amp;id=" . $id . "'>Повторить</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $msg = trim($_POST['msg']);
            if ($_POST['msgtrans'] == 1)
            {
                $msg = trans($msg);
            }
            $to = $type1['from'];
            if (!empty($_POST['citata']))
            {
                $citata = trim($_POST['citata']);
                $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
                $citata = mb_substr($citata, 0, 200);
                $tp = date("d.m.Y/H:i", $type1['time']);
                $msg = '[c]' . $to . ' (' . $tp . ")\r\n" . $citata . '[/c]' . $msg;
                $to = '';
            } elseif (!empty($_POST['txt']))
            {
                $txt = trim($_POST['txt']);
                $msg = $txt . ' ' . $msg;
                $to = '';
            }
            mysql_query("INSERT INTO `forum` SET
			`refid`='" . $th . "',
			`type`='m',
			`time`='" . $realtime . "',
			`from`='" . $login . "',
			`to`='" . $to . "',
			`ip`='" . $ipp . "',
			`soft`='" . mysql_real_escape_string($agn1) . "',
			`text`='" . mysql_real_escape_string($msg) . "';");
            $fadd = mysql_insert_id();
            mysql_query("update `forum` set  time='" . $realtime . "' where id='" . $th . "';");
            if (empty($datauser['postforum']))
            {
                $fpst = 1;
            } else
            {
                $fpst = $datauser['postforum'] + 1;
            }
            mysql_query("UPDATE `users` SET
			`postforum` = '" . $fpst . "',
			`lastpost` = '" . $realtime . "'
			WHERE `id` = '" . $user_id . "';");
            $pa = mysql_query("select * from `forum` where type='m' and refid= '" . $th . "';");
            $pa2 = mysql_num_rows($pa);

            if (((empty($_SESSION['uid'])) && (!empty($_SESSION['uppost'])) && ($_SESSION['uppost'] == 1)) || ((!empty($_SESSION['uid'])) && $upfp == 1))
            {
                $page = 1;
            } else
            {
                $page = ceil($pa2 / $kmess);
            }

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
            ////////////////////////////////////////////////////////////
            // Добавление сообщения с цитированием                    //
            ////////////////////////////////////////////////////////////
            require_once ("../incfiles/head.php");
            $qt = " $type1[text]";
            if ($th1['edit'] == 1)
            {
                echo "Вы не можете писать в закрытую тему<br/><a href='?id=" . $th . "'>В тему</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
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
            echo '<div class="phdr">Тема: <b>' . $th1['text'] . '</b></div>';
            $qt = str_replace("<br/>", "\r\n", $qt);
            $qt = trim(preg_replace('#\[c\](.*?)\[/c\]#si', '', $qt));
            $qt = htmlentities($qt, ENT_QUOTES, 'UTF-8');
            echo '<form action="?act=say&amp;id=' . $id . '&amp;cyt" method="post" enctype="multipart/form-data">';
            if (isset($_GET['cyt']))
            {
                echo '<div class="menu"><b>Автор:</b> ' . $type1['from'] . '</div>';
                echo '<div class="menu"><b>Цитата:</b><br/><textarea cols="24" rows="4" name="citata">' . $qt . '</textarea>';
                echo '<br /><small>Допустимо макс. 200 символов.<br />Весь лишний текст обрезается.</small></div>';
            } else
            {
                echo '<div class="menu"><b>Кому:</b> ' . $type1['from'] . '</div>';
                echo '<div class="menu">Выберите вариант обращения:';
                echo '<br /><input type="radio" value="' . $type1['from'] . ', " checked="checked" name="txt" />';
                echo '&nbsp;' . $type1['from'] . ',';
                $vrp = $type1['time'] + $sdvig * 3600;
                $vr = date("d.m.Y / H:i", $vrp);
                echo '<br /><input type="radio" value="' . $type1['from'] . ', с удовольствием тебе отвечу," name="txt" />';
                echo '&nbsp;' . $type1['from'] . ', с удовольствием тебе отвечу,';
                echo '<br /><input type="radio" value="' . $type1['from'] . ', на твой пост (' . $vr . ') отвечаю," name="txt" />';
                echo '&nbsp;' . $type1['from'] . ', на твой пост (' . $vr . ') отвечаю,';
                echo '<br /><input type="radio" value="' . $type1['from'] . ', канай отсюда редиска! Маргалы выкалю, рога поотшибаю!" name="txt" />';
                echo '&nbsp;' . $type1['from'] . ', канай отсюда редиска! Маргалы выкалю, рога поотшибаю!';
                echo '<br /><small>Выбранный текст будет вставлен перед Вашим текстом, который Вы напишите ниже.</small>';
                echo '</div>';
            }
            echo '<div class="gmenu"><b>Сообщение:</b><br/><textarea cols="24" rows="4" name="msg"></textarea><br/>';
            echo '<input type="checkbox" name="addfiles" value="1" /> Добавить файл<br/>';
            if ($offtr != 1)
            {
                echo "<input type='checkbox' name='msgtrans' value='1' /> Транслит сообщения<br/>";
            }
            echo '<input type="submit" name="submit" value="Отправить"/></div></form>';
        }
        echo '<div class="bmenu"><a href="index.php?act=trans">Транслит</a> | <a href="../str/smile.php">Смайлы</a></div>';
        echo '<p><a href="?id=' . $type1['refid'] . '">Назад</a></p>';
        break;

    default:
        require_once ("../incfiles/head.php");
        echo "Ошибка:тема удалена или не существует!<br/>&#187;<a href='?'>В форум</a><br/>";
        break;
}

?>