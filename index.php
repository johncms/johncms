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

require_once ('incfiles/core.php');
require_once ('incfiles/head.php');

if (isset ($_GET['err']))
    $act = 404;
switch ($act) {
    case '404' :
        ////////////////////////////////////////////////////////////
        // Сообщение об ошибке 404                                //
        ////////////////////////////////////////////////////////////
        echo display_error('Запрошенная Вами страница отсутствует');
        break;

    case 'users' :
        //TODO: Сделать переключатель доступа из Админки, показвать, или нет Актив гостям
        echo '<div class="phdr"><b>Актив Сайта</b></div>';
        echo '<div class="menu"><a href="str/users_search.php">Поиск юзера</a></div>';
        echo '<div class="menu"><a href="str/users.php">Список юзеров</a> (' . kuser() . ')</div>';
        $mon = date("m", $realtime);
        if (substr($mon, 0, 1) == 0) {
            $mon = str_replace("0", "", $mon);
        }
        $day = date("d", $realtime);
        if (substr($day, 0, 1) == 0) {
            $day = str_replace("0", "", $day);
        }
        $brth = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `dayb` = '" . $day . "' AND `monthb` = '" . $mon . "' AND `preg` = '1'"), 0);
        if ($brth)
            echo '<div class="menu"><a href="str/brd.php">Именинники</a> (' . $brth . ')</div>';
        echo '<div class="menu"><a href="str/moders.php">Администрация</a></div>';
        echo '<div class="menu"><a href="str/users_top.php">Топ активности</a></div>';
        echo '<div class="phdr">&nbsp;</div>';
        break;

    case 'info' :
        ////////////////////////////////////////////////////////////
        // Информационный блок                                    //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><b>Информация</b></div>';
        echo '<div class="menu"><a href="str/smile.php?">Смайлы</a></div>';
        echo '<div class="menu"><a href="read.php?">FAQ (ЧаВо)</a></div>';
        //TODO: Разобраться с сессией, по возможности удалить
        $_SESSION['refsm'] = '../index.php?act=info';
        break;

    case 'cab' :
        ////////////////////////////////////////////////////////////
        // Личный кабинет                                         //
        ////////////////////////////////////////////////////////////
        if (!$user_id) {
            echo display_error('Только для зарегистрированных');
            exit;
        }
        echo '<div class="phdr"><b>Личный кабинет</b></div>';
        // Блок статистики
        echo '<div class="gmenu"><p><h3><img src="images/rate.gif" width="16" height="16" class="left" />&nbsp;Мои активы</h3><ul>';
        echo '<li><a href="str/my_stat.php?act=forum">Последние записи</a></li>';
        echo '<li><a href="str/my_stat.php">Моя Статистика</a></li>';
        if ($rights >= 6) {
            $guest = gbook(2);
            echo '<li><a href="str/guest.php?act=ga&amp;do=set">Админ-Клуб</a> (<span class="red">' . $guest . '</span>)</li>';
            echo '<li><span class="red"><a href="' . $admp . '/index.php"><b>Админ панель</b></a></span></li>';
        }
        echo '</ul></p></div>';
        echo '<div class="menu"><p><h3><img src="images/mail.png" width="16" height="16" class="left" />&nbsp;Моя почта</h3><ul>';
        // Блок почты
        $count_mail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in'"), 0);
        $count_newmail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '" . $login . "' AND `type` = 'in' AND `chit` = 'no'"), 0);
        echo '<li><a href="str/pradd.php?act=in">Входящие</a>&nbsp;(' . $count_mail . ($count_newmail ? '&nbsp;/&nbsp;<span class="red"><a href="str/pradd.php?act=in&amp;new">+' . $count_newmail . '</a></span>' : '') . ')</li>';
        $count_sentmail = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `author` = '$login' AND `type` = 'out'"), 0);
        $count_sentunread = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `author` = '$login' AND `type` = 'out' AND `chit` = 'no'"), 0);
        echo '<li><a href="str/pradd.php?act=out">Отправленные</a>&nbsp;(' . $count_sentmail . ($count_sentunread ? '&nbsp;/&nbsp;<span class="red">' . $count_sentunread . '</span>' : '') . ')</li>';
        $count_files = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `user` = '$login' AND `type` = 'in' AND `attach` != ''"), 0);
        //echo '<li><a href="str/pradd.php?act=files">Файлы</a>&nbsp;(' . $count_files . ')</li>';
        if (!$ban['1'] && !$ban['3'])
            echo '<p><form action="str/pradd.php?act=write" method="post"><input type="submit" value=" Написать " /></form></p>';
        // Блок контактов
        echo '</ul><h3><img src="images/contacts.png" width="16" height="16" class="left" />&nbsp;Мои контакты</h3><ul>';
        $count_contacts = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `me` = '$login' AND `cont` != ''"), 0);
        echo '<li><a href="str/cont.php">Контакты</a>&nbsp;(' . $count_contacts . ')</li>';
        $count_ignor = mysql_result(mysql_query("SELECT COUNT(*) FROM `privat` WHERE `me` = '$login' AND `ignor` != ''"), 0);
        echo '<li><a href="str/ignor.php">Игнор</a>&nbsp;(' . $count_ignor . ')</li>';
        echo '</ul></p></div>';
        // Блок настроек
        echo '<div class="bmenu"><p><h3><img src="images/settings.png" width="16" height="16" class="left" />&nbsp;Мои настройки</h3><ul>';
        echo '<li><a href="str/anketa.php">Моя анкета</a></li>';
        echo '<li><a href="str/my_pass.php">Сменить пароль</a></li>';
        echo '<li><a href="str/my_set.php">Общие настройки</a></li>';
        echo '<li><a href="str/my_set.php?act=forum">Форум</a></li>';
        echo '<li><a href="str/my_set.php?act=chat">Чат</a></li>';
        echo '</ul></p></div>';
        break;

    case 'digest' :
        ////////////////////////////////////////////////////////////
        // Дайджест                                               //
        ////////////////////////////////////////////////////////////
        if (!$user_id) {
            echo display_error('Только для зарегистрированных');
            exit;
        }
        echo '<div class="phdr">Дайджест</div>';
        echo '<div class="gmenu"><p>Привет, <b>' . $login . '</b><br/>Добро пожаловать на ' . $copyright . '!<br /><a href="index.php">Войти на сайт</a></p></div>';
        // Поздравление с днем рождения
        if ($datauser['dayb'] == $day && $datauser['monthb'] == $mon) {
            echo '<div class="rmenu"><p>С ДНЁМ РОЖДЕНИЯ!!!</p></div>';
        }
        // Дайджест Администратора
        if ($rights >= 1) {
            $newusers_total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `datereg` > '" . ($realtime - 86400) . "' AND `preg` = '1'"), 0);
            $reg_total = mysql_result(mysql_query("SELECT COUNT(*) FROM `users` WHERE `preg` = 0"), 0);
            $ban_total = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_ban_users` WHERE `ban_time`>'" . $realtime . "'"), 0);
            echo '<div class="bmenu">События в админке</div>';
            echo '<div class="menu"><ul>';
            if ($newusers_total > 0)
                echo '<li><a href="str/users.php">Новые посетители</a> (' . $newusers_total . ')</li>';
            if ($reg_total > 0)
                echo '<li><a href="' . $admp . '/index.php?act=usr_reg">На регистрации</a> (' . $reg_total . ')</li>';
            if ($ban_total > 0)
                echo '<li><a href="' . $admp . '/index.php?act=usr_ban">Имеют Бан</a> (' . $ban_total . ')</li>';
            $total_libmod = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = 0"), 0);
            if ($total_libmod > 0)
                echo '<li><a href="library/index.php?act=moder">Мод. Библиотеки</a> (' . $total_libmod . ')</li>';
            $total_admin = gbook(2);
            if ($total_admin > 0)
                echo '<li><a href="str/guest.php?act=ga&amp;do=set">Админ-Клуб</a> (' . $total_admin . ')</li>';
            if (!$newusers_total && !$reg_total && !$ban_total && !$total_libmod && !$total_admin)
                echo 'Новых событий нет';
            echo '</ul></div>';
        }
        // Дайджест юзеров
        echo '<div class="bmenu">Новое на сайте</div><div class="menu"><ul>';
        $total_news = mysql_result(mysql_query("SELECT COUNT(*) FROM `news` WHERE `time` > " . ($realtime - 86400)), 0);
        if ($total_news > 0)
            echo '<li><a href="str/news.php">Новости</a> (' . $total_news . ')</li>';
        $total_forum = forum_new();
        if ($total_forum > 0)
            echo '<li><a href="forum/index.php?act=new">Форум</a> (' . $total_forum . ')</li>';
        $total_guest = gbook(1);
        if ($total_guest > 0)
            echo '<li><a href="str/guest.php?act=ga">Гостевая</a> (' . $total_guest . ')</li>';
        $total_gal = fgal(1);
        if ($total_gal > 0)
            echo '<li><a href="gallery/index.php?act=new">Галерея</a> (' . $total_gal . ')</li>';
        $old = $realtime - (3 * 24 * 3600);
        $total_lib = mysql_result(mysql_query("SELECT COUNT(*) FROM `lib` WHERE `type` = 'bk' AND `moder` = 1 AND `time` > " . $old), 0);
        if ($total_lib > 0)
            echo '<li><a href="library/index.php?act=new">Библиотека</a> (' . $total_lib . ')</li>';
        // Если нового нет, выводим сообщение
        if (!$total_news && !$total_forum && !$total_guest && !$total_gal && !$total_lib)
            echo 'Новостей нет';
        // Дата последнего посещения
        $last = isset ($_GET['last']) ? intval($_GET['last']) : $datauser['lastdate'];
        echo '</ul></div><div class="phdr">Последнее посещение: ' . date("d.m.Y (H:i)", $last) . '</div>';
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Главное меню сайта                                     //
        ////////////////////////////////////////////////////////////
        include_once 'pages/mainmenu.php';
}

require_once ("incfiles/end.php");

?>