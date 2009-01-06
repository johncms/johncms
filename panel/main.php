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

$textl = 'Админка';
require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

if ($dostmod == 1)
{
    $do = isset($_GET['do']) ? $_GET['do'] : '';
    switch ($do)
    {
        case 'modules':
            ////////////////////////////////////////////////////////////
            // Включение / выключение модулей системы                 //
            ////////////////////////////////////////////////////////////
            if ($dostadm == 1)
            {
                echo '<div class="phdr">Включить / выключить</div>';
                if (isset($_POST['submit']))
                {
                    // Записываем настройки
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['reg']) ? intval($_POST['reg']) : 0) . "' WHERE `key`='mod_reg';");
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['forum']) ? intval($_POST['forum']) : 0) . "' WHERE `key`='mod_forum';");
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['chat']) ? intval($_POST['chat']) : 0) . "' WHERE `key`='mod_chat';");
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['guest']) ? intval($_POST['guest']) : 0) . "' WHERE `key`='mod_guest';");
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['lib']) ? intval($_POST['lib']) : 0) . "' WHERE `key`='mod_lib';");
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['gal']) ? intval($_POST['gal']) : 0) . "' WHERE `key`='mod_gal';");
                    mysql_query("UPDATE `cms_settings` SET `val`='" . (isset($_POST['down']) ? intval($_POST['down']) : 0) . "' WHERE `key`='mod_down';");
                    $req = mysql_query("SELECT * FROM `cms_settings`;");
                    $set = array();
                    while ($res = mysql_fetch_row($req))
                        $set[$res[0]] = $res[1];
                    mysql_free_result($req);
                    echo '<div class="rmenu">Сайт настроен</div>';
                }
                // Выводим форму
                echo '<form id="form1" method="post" action="main.php?do=modules">';
                echo '<p><input name="reg" type="checkbox" value="1" ' . ($set['mod_reg'] ? 'checked="checked"' : '') . ' />&nbsp;регистрация<br />';
                echo '<input name="forum" type="checkbox" value="1" ' . ($set['mod_forum'] ? 'checked="checked"' : '') . ' />&nbsp;форум<br />';
                echo '<input name="chat" type="checkbox" value="1" ' . ($set['mod_chat'] ? 'checked="checked"' : '') . ' />&nbsp;чат<br />';
                echo '<input name="guest" type="checkbox" value="1" ' . ($set['mod_guest'] ? 'checked="checked"' : '') . ' />&nbsp;гостевая<br />';
                echo '<input name="lib" type="checkbox" value="1" ' . ($set['mod_lib'] ? 'checked="checked"' : '') . ' />&nbsp;библиотека<br />';
                echo '<input name="gal" type="checkbox" value="1" ' . ($set['mod_gal'] ? 'checked="checked"' : '') . ' />&nbsp;галерея<br />';
                echo '<input name="down" type="checkbox" value="1" ' . ($set['mod_down'] ? 'checked="checked"' : '') . ' />&nbsp;загрузки<br />';
                echo '<br /><input type="submit" name="submit" id="button" value="Запомнить" /></p>';
                echo '<p><a href="main.php">В админку</a></p>';
                echo '</form>';
            }
            break;

        case 'users':
            if (empty($_POST['user']))
            {
                echo '<br /><b>Вы не заполнили поле!</b><br/><br/><a href="main.php?do=search">Назад</a><br/><br/>';
                require_once ("../incfiles/end.php");
                exit;
            }
            // Поиск по ID
            if ($_POST['term'] == '1')
            {
                $search = intval($_POST['user']);
                $req = mysql_query("select * from `users` where `id`='" . $search . "';");
            }
            // Поиск по Нику
            else
            {
                $search = rus_lat(mb_strtolower($_POST['user']));
                $req = mysql_query("select * from `users` where `name_lat`='" . mysql_real_escape_string($search) . "';");
            }
            if (mysql_num_rows($req) == 0)
            {
                echo "Нет такого юзера!<br/><a href='main.php'>Назад</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            $res = mysql_fetch_array($req);
            header("location: ../str/anketa.php?user=$res[id]");
            break;

        case 'search':
            echo '<div class="phdr">Поиск юзера</div>';
            echo '<br />Кого ищем?:<br/>';
            echo '<form action="main.php?do=users" method="post">';
            echo '<input type="text" name="user"/><br/>';
            echo '<input name="term" type="radio" value="0" checked="checked" />Поиск по Нику<br />';
            echo '<input name="term" type="radio" value="1" />Поиск по ID<br /><br />';
            echo '<input type="submit" value="Поиск"/>';
            echo '</form>';
            echo '<p><a href="main.php">В админку</a></p>';
            break;

        default:
            ////////////////////////////////////////////////////////////
            // Главное меню админки                                   //
            ////////////////////////////////////////////////////////////
            echo '<div class="phdr">Админ Панель</div>';
            echo '<div class="rmenu">Пользователи</div>';
            $total = @mysql_num_rows(mysql_query("SELECT * FROM `users`;"));
            echo '<div class="menu">Всего в базе: <a href="../str/users.php">' . $total . '</a><br />';
            $total = @mysql_num_rows(mysql_query("SELECT * FROM `users` WHERE `preg`='0';"));
            echo 'На регистрации: ' . ($total > 0 ? '<a href="preg.php">' . $total . '</a>' : '0') . '<br />';
            $total = @mysql_num_rows(mysql_query("SELECT * FROM `cms_ban_users` WHERE `ban_time`>'" . $realtime . "';"));
            echo 'Забаненных: ' . ($total > 0 ? '<a href="zaban.php">' . $total . '</a>' : '0') . '</div>';
            echo '<div class="menu"><a href="main.php?do=search">Поиск</a></div>';
            echo '<div class="menu"><a href="zaban.php">Бан-панель</a></div>';
            if ($dostadm == 1)
            {
                echo '<div class="rmenu">Модули</div>';
                echo '<div class="menu"><a href="news.php">Новости</a></div>';
                echo '<div class="menu"><a href="forum.php">Форум</a></div>';
                echo '<div class="menu"><a href="chat.php">Чат</a></div>';
                echo '<div class="rmenu">Система</div>';
                echo '<div class="menu"><a href="ipban.php">Бан по IP</a></div>';
                echo '<div class="menu"><a href="main.php?do=modules">Модули (вкл/выкл)</a></div>';
                echo '<div class="menu"><a href="set.php">Настройки</a></div>';
            }
    }
} else
{
    header("Location: ../index.php?err");
}
require_once ("../incfiles/end.php");

?>