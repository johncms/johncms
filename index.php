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

$headmod = "mainpage";

// Внимание! Если файл находится в корневой папке, нужно указать $rootpath = '';
$rootpath = '';

require_once ("incfiles/core.php");
require_once ("incfiles/head.php");

$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
if (isset($_GET['err']))
    $mod = 404;
switch ($mod)
{
    case '404':
        ////////////////////////////////////////////////////////////
        // Сообщение об ошибке 404                                //
        ////////////////////////////////////////////////////////////
        echo '<p>Ошибка 404: файл не найден!!!</p>';
        break;

    case 'ban':
        ////////////////////////////////////////////////////////////
        // Подробности бана                                       //
        ////////////////////////////////////////////////////////////
        require_once ('incfiles/ban.php');
        echo '<div class="phdr">У Вас есть следующие наказания:</div>';
        $req = mysql_query("SELECT * FROM `cms_ban_users` WHERE `user_id`='" . $user_id . "' AND `ban_time`>'" . $realtime . "';");
        if (mysql_num_rows($req) != 0)
        {
            while ($res = mysql_fetch_array($req))
            {
                echo '<div class="menu">';
                echo '<b>' . $ban_term[$res['ban_type']] . '</b><br />';
                echo $ban_desc[$res['ban_type']] . '<br />';
                echo '<u>Причина:</u> ' . $res['ban_reason'] . '<br />';
                echo '<u>Срок:</u> ' . timecount($res['ban_time'] - $res['ban_while']) . '<br />';
				echo '<u>Осталось:</u> ' . timecount($res['ban_time'] - $realtime) . '';
                echo '</div>';
            }
        }
        break;

    case 'cab':
        echo '<div class="phdr">Личный кабинет</div>';
        echo '<div class="gmenu"><a href="str/privat.php">Личная почта</a></div>';
        echo '<div class="menu"><a href="str/anketa.php">Ваша анкета</a></div>';
        echo '<div class="menu"><a href="str/usset.php">Настройки</a></div>';
        echo '<div class="menu"><a href="str/anketa.php?act=statistic">Статистика</a></div>';
        if ($dostmod == 1)
        {
            echo '<div class="gmenu"><a href="str/guest.php?act=ga&amp;do=set">Админ-Клуб</a></div>';
            echo '<div class="rmenu"><a href="' . $admp . '/main.php">Админка</a></div>';
        }
        break;

    case 'digest':
        ////////////////////////////////////////////////////////////
        // Дайджест                                               //
        ////////////////////////////////////////////////////////////
        echo '<p>Привет, ' . $login . ' !<br/>';
        echo 'Добро пожаловать на ' . $copyright . '!</p><p>';
        // Поздравление с днем рождения
        if ($datauser['dayb'] == $day && $datauser['monthb'] == $mon)
        {
            echo "<font color = 'red'><b>С ДНЁМ РОЖДЕНИЯ!!!</b></font></p><p>";
        }
        // Дата последнего посещения
        echo 'Последнее посещение: ' . date("d.m.Y (H:i)", $datauser['lastdate']) . '<br /><br />';
        echo '<b>Новое на сайте:</b><br />';
        // Новости
        echo '&nbsp;Новости: ';
        $total = mysql_num_rows(mysql_query("SELECT * FROM `news` WHERE `time`>'" . $datauser['lastdate'] . "';"));
        if ($total != 0)
        {
            echo $total . '&nbsp;<a href="str/news.php?kv=' . $total . '">&gt;&gt;&gt;</a><br/>';
        } else
        {
            echo " нет.<br/>";
        }
        // Форум
        $lp = mysql_query("select * from `forum` where type='t' and moder='1' and close!='1';");
        while ($arrt = mysql_fetch_array($lp))
        {
            $q3 = mysql_query("select * from `forum` where type='r' and id='" . $arrt[refid] . "';");
            $q4 = mysql_fetch_array($q3);
            $rz = mysql_query("select * from `forum` where type='n' and refid='" . $q4[refid] . "' and `from`='" . $login . "';");
            $np = mysql_query("select * from `forum` where type='l' and time>='" . $arrt[time] . "' and refid='" . $arrt[id] . "' and `from`='" . $login . "';");
            if ((mysql_num_rows($np)) != 1 && (mysql_num_rows($rz)) != 1)
            {
                $total = $total + 1;
            }
        }
        echo '&nbsp;Форум: ' . ($total != 0 ? $total . '&nbsp;<a href="forum/index.php?act=new">&gt;&gt;&gt;</a><br/>' : 'нет.<br/>');
        // Гостевая
        $total = gbook(1);
        echo '&nbsp;Гостевая: ' . ($total != 0 ? $total . '&nbsp;<a href="str/guest.php?act=ga">&gt;&gt;&gt;</a><br/>' : 'нет.<br/>');
        // Админ-Клуб
        $total = gbook(2);
        echo '&nbsp;Админ-Клуб: ' . ($total != 0 ? $total . '&nbsp;<a href="str/guest.php?act=ga&amp;do=set">&gt;&gt;&gt;</a><br/>' : 'нет.<br/>');
        // Библиотека
        $total = stlib(1);
        echo '&nbsp;Библиотека: ' . ($total != 0 ? $total . '&nbsp;<a href="library/index.php?act=new">&gt;&gt;&gt;</a><br/>' : 'нет.<br/>');
        // Галерея
        $total = fgal(1);
        echo '&nbsp;Галерея: ' . ($total != 0 ? $total . '&nbsp;<a href="gallery/index.php?act=new">&gt;&gt;&gt;</a><br/>' : 'нет.<br/>');
        // Ссылка на Главную
        echo '</p><p><a href="index.php">На главную</a>';
        echo '</p>';
        break;

    default:
        ////////////////////////////////////////////////////////////
        // Главное меню сайта                                     //
        ////////////////////////////////////////////////////////////
        include_once 'pages/mainmenu.php';
}

require_once ("incfiles/end.php");

?>