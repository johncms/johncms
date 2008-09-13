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

defined('_IN_JOHNCMS') or die('Error:restricted access');

Error_Reporting(E_ALL & ~ E_NOTICE);
Error_Reporting(ERROR | WARNING);
mb_internal_encoding('UTF-8');

if (!isset($rootpath))
    $rootpath = '../';

////////////////////////////////////////////////////////////
// Удаляем слэши, если открыт magic_quotes_gpc            //
////////////////////////////////////////////////////////////
if (get_magic_quotes_gpc())
{
    $in = array(&$_GET, &$_POST, &$_COOKIE);
    while (list($k, $v) = each($in))
    {
        foreach ($v as $key => $val)
        {
            if (!is_array($val))
            {
                $in[$k][$key] = stripslashes($val);
                continue;
            }
            $in[] = &$in[$k][$key];
        }
    }
    unset($in);
    if (!empty($_FILES))
    {
        foreach ($_FILES as $k => $v)
        {
            $_FILES[$k]['name'] = stripslashes((string )$v['name']);
        }
    }
}

////////////////////////////////////////////////////////////
// 1) Получаем реальный IP                                //
// 2) Проверяем на попытку HTTP флуда                     //
////////////////////////////////////////////////////////////
require_once ($rootpath . 'incfiles/class_ipinit.php');
$ipinit = new ipinit();
$ipl = $ipinit->ip;
$ipp = long2ip($ipl);
unset($ipinit);

// Стартуем сессию
session_name("SESID");
session_start();

////////////////////////////////////////////////////////////
// Подключаемся к базе данных                             //
////////////////////////////////////////////////////////////
require_once ($rootpath . 'incfiles/db.php');
$connect = @mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server');
@mysql_select_db($db_name) or die('cannot connect to db');
@mysql_query("SET NAMES 'utf8'", $connect);

////////////////////////////////////////////////////////////
// Проверяем адрес IP на Бан                              //
////////////////////////////////////////////////////////////
$req = mysql_query("SELECT `ban_type`, `link` FROM `cms_ban_ip` WHERE `ip`='" . $ipl . "';") or die('Error: table "cms_ban_ip"');
if (mysql_num_rows($req) != 0)
{
    $res = mysql_fetch_array($req);
    switch ($res['ban_type'])
    {
        case 2:
            // Редирект по ссылке
            if (!empty($res['link']))
            {
                header("Location: " . $res['link']);
                exit;
            } else
            {
                header("Location: http://gazenwagen.com");
                exit;
            }
            break;

        case 3:
            // Закрытие регистрации
            $regban = true;
            break;

        default:
            // Полный запрет доступа к сайту
            header("HTTP/1.0 404 Not Found");
            exit;
    }
}

////////////////////////////////////////////////////////////
// Основные настройки системы                             //
////////////////////////////////////////////////////////////
$kmess = 10; // Число сообщений на страницу, для гостей
$sdvig = 0; // Временной сдвиг для гостей
$user_id = false;
$user_ps = false;
$dostsadm = 0;
$dostadm = 0;
$dostsmod = 0;
$dostlmod = 0;
$dostdmod = 0;
$dostfmod = 0;
$dostcmod = 0;
$dostkmod = 0;
$dostmod = 0;
$rights = 0;
$req = mysql_query("SELECT * FROM `cms_settings`;");
$set = array();
while ($res = mysql_fetch_row($req))
    $set[$res[0]] = $res[1];
mysql_free_result($req);
$nickadmina = $set['nickadmina']; // Ник 1-го админа
$nickadmina2 = $set['nickadmina2']; // Ник 2-го (скрытого) админа
$emailadmina = $set['emailadmina']; // E-mail администратора
$sdvigclock = $set['sdvigclock']; // Временной сдвиг по умолчанию для системы
$copyright = $set['copyright']; // Коприайт сайта
$home = $set['homeurl']; // Домашняя страница
$ras_pages = $set['rashstr']; // Расширение текстовых страниц
$admp = $set['admp']; // Папка с Админкой
$flsz = $set['flsz']; // Максимальный размер файлов
// Дата и время
$realtime = time() + $sdvigclock * 3600;
$mon = date("m", $realtime);
if (substr($mon, 0, 1) == 0)
{
    $mon = str_replace("0", "", $mon);
}
$day = date("d", $realtime);
if (substr($day, 0, 1) == 0)
{
    $day = str_replace("0", "", $day);
}
$mesyac = array(1 => "января", "февраля", "марта", "апреля", "мая", "июня", "июля", "августа", "сентября", "октября", "ноября", "декабря");

////////////////////////////////////////////////////////////
// Автоочистка системы                                    //
////////////////////////////////////////////////////////////
if ($set['clean_time'] <= ($realtime - 43200))
{
    // Очищаем таблицу "count"
    mysql_query("delete from `count` where `time`<='" . ($realtime - 600) . "';");
    mysql_query("OPTIMIZE TABLE `count`;");
    mysql_query("UPDATE `cms_settings` SET  `val`='" . $realtime . "' WHERE `key`='clean_time';");
}

// Получаем переменные окружения
$agn = htmlentities(substr($_SERVER['HTTP_USER_AGENT'], 0, 100), ENT_QUOTES); // User Agent

////////////////////////////////////////////////////////////
// Авторизация по сессии                                  //
////////////////////////////////////////////////////////////
if (isset($_SESSION['uid']) && isset($_SESSION['ups']))
{
    $user_id = intval($_SESSION['uid']);
    $user_ps = $_SESSION['ups'];
}

////////////////////////////////////////////////////////////
// Авторизация по COOKIE                                  //
////////////////////////////////////////////////////////////
elseif (isset($_COOKIE['cuid']) && isset($_COOKIE['cups']))
{
    $user_id = intval(base64_decode($_COOKIE['cuid']));
    $_SESSION['uid'] = $user_id;
    $user_ps = md5(md5(base64_decode($_COOKIE['cups'])));
    $_SESSION['ups'] = $user_ps;
    $cookauth = true;
}

////////////////////////////////////////////////////////////
// Запрос в базе данных по юзеру                          //
////////////////////////////////////////////////////////////
if ($user_id && $user_ps)
{
    $req = mysql_query("SELECT * FROM `users` WHERE `id`='" . $user_id . "' LIMIT 1;");
    if (mysql_num_rows($req) != 0)
    {
        $datauser = mysql_fetch_array($req);
        if ($user_ps === $datauser['password'])
        {
            // Получение параметров пользователя
            $idus = $user_id;
            $login = $datauser['name']; // Логин (Ник) пользователя
            $sdvig = $datauser['sdvig']; // Сдвиг времени
            $kmess = $datauser['kolanywhwere']; // Число сообщений на страницу
            $offpg = $datauser['offpg'];
            $offtr = $datauser['offtr']; // Выключить транслит
            $offgr = $datauser['offgr']; // Выключить графику
            $offsm = $datauser['offsm']; // Выключить смайлы
            $upfp = $datauser['upfp'];
            $nmenu = $datauser['nmenu'];
            $chmes = $datauser['chmes'];
            $rights = $datauser['rights'];
            $lastdate = $datauser['lastdate'];
            mysql_free_result($req);

            ////////////////////////////////////////////////////////////
            // Проверка юзера на бан                                  //
            ////////////////////////////////////////////////////////////
            $req = mysql_query("SELECT * FROM `cms_ban_users` WHERE `user_id`='" . $user_id . "' AND `ban_time`>'" . $realtime . "';") or die('Error: table "cms_ban_users"');
            if (mysql_num_rows($req) != 0)
            {
                $rights = 0;
                $ban = array();
                while ($res = mysql_fetch_row($req))
                    $ban[$res[4]] = 1;
                mysql_free_result($req);
                if (isset($ban['9']))
                {
                    header("HTTP/1.0 404 Not Found");
                    exit;
                }
            }

            // Установка административного доступа
            if ($login == $nickadmina || $login == $nickadmina2)
            {
                $dostsadm = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 7)
            {
                $dostadm = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 7 || $rights == 6)
            {
                $dostsmod = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 7 || $rights == 6 || $rights == 5)
            {
                $dostlmod = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 7 || $rights == 6 || $rights == 4)
            {
                $dostdmod = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 7 || $rights == 6 || $rights == 3)
            {
                $dostfmod = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 7 || $rights == 6 || $rights == 2)
            {
                $dostcmod = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights == 1 || $rights == 7 || $rights == 6)
            {
                $dostkmod = 1;
            }
            if ($login == $nickadmina || $login == $nickadmina2 || $rights >= 1)
            {
                $dostmod = 1;
            }

            // Устанавливаем время начала сессии
            if ($lastdate <= ($realtime - 300))
                mysql_query("UPDATE `users` SET `sestime`='" . $realtime . "' WHERE `id`='" . $user_id . "';");

            // Обновляем данные
            $totalonsite = $datauser['total_on_site'];
            if ($lastdate >= ($realtime - 300))
                $totalonsite = $totalonsite + $realtime - $lastdate;
            mysql_query("UPDATE `users` SET
			`total_on_site`='" . $totalonsite . "',
			`lastdate`='" . $realtime . "',
			`ip`='" . $ipl . "',
			`browser`='" . mysql_real_escape_string($agn) . "'
			WHERE `id`='" . $user_id . "';");

            // Если юзера не было на сайте более 1-го часа , показываем дайджест
            if ($lastdate < ($realtime - 3600))
                header("Location: " . $home . "/index.php?mod=digest&last=" . $lastdate);
        } else
        {
            // Если пароль не совпадает, уничтожаем переменные сессии и чистим куки
            unset($_SESSION['uid']);
            unset($_SESSION['ups']);
            setcookie('cuid', '');
            setcookie('cups', '');
            $user_id = false;
            $user_ps = false;
        }
    } else
    {
        // Если юзер не найден, уничтожаем переменные сессии и чистим куки
        unset($_SESSION['uid']);
        unset($_SESSION['ups']);
        setcookie('cuid', '');
        setcookie('cups', '');
        $user_id = false;
        $user_ps = false;
    }
}

// Подключаем дополнительные файлы
require_once ($rootpath . 'incfiles/func.php'); // Вспомогательные функции
require_once ($rootpath . 'incfiles/stat.php'); // Статистика

// Буфферизация вывода
if ($set['gzip'] == 1)
{
    ob_start('ob_gzhandler');
} else
{
    ob_start();
}

?>