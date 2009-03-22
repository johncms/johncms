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

$headmod = 'mainpage';

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
                echo '<div class="menu"><b>' . $ban_term[$res['ban_type']] . '</b><br />' . $ban_desc[$res['ban_type']] . '</div>';
                echo '<div class="menu"><u>Причина</u>: ';
                if (!empty($res['ban_ref']))
                    echo 'Нарушение <a href="' . $home . '/forum/index.php?act=post&amp;id=' . $res['ban_ref'] . '">на форуме</a><br />';
                echo $res['ban_reason'] . '</div>';
                echo '<div class="menu"><u>Срок:</u> ' . timecount($res['ban_time'] - $res['ban_while']) . '</div>';
                echo '<div class="bmenu">Осталось: ' . timecount($res['ban_time'] - $realtime) . '</div>';
            }
        }
        break;

    case 'cab':
        echo '<div class="phdr">Личный кабинет</div>';
        echo '<div class="gmenu"><a href="str/privat.php">Личная почта</a></div>';
        if ($dostmod == 1)
        {
            $guest = gbook(2);
            echo '<div class="gmenu"><a href="str/guest.php?act=ga&amp;do=set">Админ-Клуб</a>' . ($guest > 0 ? ' (<span class="red">+' . $guest . '</span>)' : '') . '</div>';
        }
        echo '<div class="menu"><a href="str/anketa.php">Ваша анкета</a></div>';
        echo '<div class="menu"><a href="str/anketa.php?act=statistic">Статистика</a></div>';
        echo '<div class="menu"><a href="str/usset.php">Настройки</a></div>';
        if ($dostmod == 1)
            echo '<div class="menu">Админка <a href="' . $admp . '/main.php">&gt;&gt;&gt;</a></div>';
        echo '<div class="bmenu"><a href="index.php?mod=digest">Новое на сайте</a></div>';
        break;

    case 'digest':
        ////////////////////////////////////////////////////////////
        // Дайджест                                               //
        ////////////////////////////////////////////////////////////
        if ($user_id)
        {
            echo '<div class="phdr">Дайджест</div>';
            echo '<div class="gmenu">Привет, <b>' . $login . '</b><br/>';
            echo 'Добро пожаловать на ' . $copyright . '!</div>';
            // Поздравление с днем рождения
            if ($datauser['dayb'] == $day && $datauser['monthb'] == $mon)
            {
                echo '<div class="rmenu">С ДНЁМ РОЖДЕНИЯ!!!</div>';
            }
            echo '<div class="bmenu">Новое на сайте</div>';
            // Новости
            $total_news = mysql_num_rows(mysql_query("SELECT * FROM `news` WHERE `time`>'" . ($realtime - 86400) . "';"));
            if ($total_news > 0)
                echo '<div class="menu">Новости: ' . $total_news . ' <a href="str/news.php">&gt;&gt;&gt;</a></div>';
            // Форум
            $total_forum = forum_new();
            if ($total_forum > 0)
                echo '<div class="menu">Форум: ' . $total_forum . '&nbsp;<a href="forum/index.php?act=new">&gt;&gt;&gt;</a></div>';
            // Гостевая
            $total_guest = gbook(1);
            if ($total_guest > 0)
                echo '<div class="menu">Гостевая: ' . $total_guest . '&nbsp;<a href="str/guest.php?act=ga">&gt;&gt;&gt;</a></div>';
            // Админ-Клуб
            $total_admin = 0;
            if ($dostmod == 1)
            {
                $total_admin = gbook(2);
                if ($total_admin > 0)
                    echo '<div class="menu">Админ-Клуб: ' . $total_admin . '&nbsp;<a href="str/guest.php?act=ga&amp;do=set">&gt;&gt;&gt;</a></div>';
            }
            // Галерея
            $total_gal = fgal(1);
            if ($total_gal > 0)
                echo '<div class="menu">Галерея: ' . $total_gal . '&nbsp;<a href="gallery/index.php?act=new">&gt;&gt;&gt;</a></div>';
            // Библиотека
            $old = $realtime - (3 * 24 * 3600);
            $req = mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type`='bk' AND `moder`='1' AND `time` > '" . $old . "';");
            $total_lib = mysql_result($req, 0);
            if ($total_lib > 0)
                echo '<div class="menu">Библиотека: ' . $total_lib . '&nbsp;<a href="library/index.php?act=new">&gt;&gt;&gt;</a></div>';
            // Если нового нет, выводим сообщение
            if ($total_news == 0 && $total_forum == 0 && $total_guest == 0 && $total_admin == 0 && $total_gal == 0 && $total_lib == 0)
                echo '<div class="menu">Новостей нет</div>';
            // Дата последнего посещения
            $last = isset($_GET['last']) ? intval($_GET['last']) : $lastdate;
            echo '<div class="bmenu"><small>Последнее посещение: ' . date("d.m.Y (H:i)", $last) . '</small></div>';
            //echo '<div class="gmenu" style="font-size:large; padding-top: 8px; padding-bottom: 8px;"><a href="index.php">Войти на сайт</a></div>';
        }
        break;

    default:
        ////////////////////////////////////////////////////////////
        // Главное меню сайта                                     //
        ////////////////////////////////////////////////////////////
        include_once 'pages/mainmenu.php';
}

require_once ("incfiles/end.php");

?>