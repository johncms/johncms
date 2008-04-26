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

Error_Reporting(E_ALL & ~ E_NOTICE);
Error_Reporting(ERROR | WARNING);
mb_internal_encoding('UTF-8');

if (!isset($rootpath))
    $rootpath = '../';

////////////////////////////////////////////////////////////
// Предварительная проверка IP адреса                     //
// 1) Получаем реальный IP                                //
// 2) Проверяем на попытку HTTP флуда                     //
////////////////////////////////////////////////////////////
require_once ($rootpath . 'incfiles/class_ipinit.php');
$ipinit = new ipinit($rootpath);
$ipl = $ipinit->ip;
$ipp = long2ip($ipl);
unset($ipinit);

session_name("SESID");
session_start();

// Подключаемся к базе данных
require_once ($rootpath . 'incfiles/db.php');
$connect = @mysql_connect($db_host, $db_user, $db_pass) or die('cannot connect to server');
@mysql_select_db($db_name) or die('cannot connect to db');
@mysql_query("SET NAMES 'utf8'", $connect);

////////////////////////////////////////////////////////////
// Основные настройки системы                             //
////////////////////////////////////////////////////////////
$req = mysql_query("select * from `settings`;");
$set = mysql_fetch_array($req);
$nickadmina = $set['nickadmina']; // Ник 1-го админа
$nickadmina2 = $set['nickadmina2']; // Ник 2-го (скрытого) админа
$emailadmina = $set['emailadmina']; // E-mail администратора
$sdvigclock = $set['sdvigclock']; // Временной сдвиг по умолчанию для системы
$copyright = $set['copyright']; // Коприайт сайта
$home = $set['homeurl']; // Домашняя страница
$ras_pages = $set['rashstr']; // Расширение текстовых страниц
$gzip = $set['gzip']; // Включение GZIP сжатия
$admp = $set['admp']; // Папка с Админкой
$fmod = $set['fmod']; // Премодерация форума
$rmod = $set['rmod']; // Премодерация регистраций в системе
$flsz = $set['flsz']; // Максимальный размер файлов
$gb = $set['gb']; // Открыть гостевую для гостей
mysql_free_result($req);

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
    mysql_query("update `settings` set  `clean_time`='" . $realtime . "';");
}

// Настройки по умолчанию
$kmess = 10;
$sdvig = 0;
$dostsadm = 0;
$dostadm = 0;
$dostsmod = 0;
$dostlmod = 0;
$dostdmod = 0;
$dostfmod = 0;
$dostcmod = 0;
$dostkmod = 0;
$dostmod = 0;

////////////////////////////////////////////////////////////
// Авторизация пользователей                              //
////////////////////////////////////////////////////////////

// Получаем переменные окружения
$agn = htmlentities(substr($_SERVER['HTTP_USER_AGENT'], 0, 100), ENT_QUOTES); // User Agent

// Авторизация по сессии
if (isset($_SESSION['uid']) && isset($_SESSION['ups']))
{
    $user_id = intval($_SESSION['uid']);
    $user_ps = $_SESSION['ups'];
}

// Авторизация по COOKIE
elseif (isset($_COOKIE['cuid']) && isset($_COOKIE['cups']))
{
    $user_id = intval(base64_decode($_COOKIE['cuid']));
    $_SESSION['uid'] = $user_id;
    $user_ps = md5(md5(base64_decode($_COOKIE['cups'])));
    $_SESSION['ups'] = $user_ps;
    $cookauth = true;
}

// Если нет ни сессии, ни COOKIE
else
{
    $user_id = false;
    $user_ps = false;
}

// Запрос в базе данных по юзеру
if ($user_id && $user_ps)
{
    $req = mysql_query("select * from `users` where id='" . $user_id . "' LIMIT 1;");
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
        mysql_free_result($req);

        // Установка административного доступа
        if ($login == $nickadmina || $login == $nickadmina2)
        {
            $dostsadm = "1"; // Супер Админ
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "7")
        {
            $dostadm = "1"; // Админ
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "7" || $datauser['rights'] == "6")
        {
            $dostsmod = "1"; // Старший модер
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "7" || $datauser['rights'] == "6" || $datauser['rights'] == "5")
        {
            $dostlmod = "1";
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "7" || $datauser['rights'] == "6" || $datauser['rights'] == "4")
        {
            $dostdmod = "1";
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "7" || $datauser['rights'] == "6" || $datauser['rights'] == "3")
        {
            $dostfmod = "1";
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "7" || $datauser['rights'] == "6" || $datauser['rights'] == "2")
        {
            $dostcmod = "1";
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] == "1" || $datauser['rights'] == "7" || $datauser['rights'] == "6")
        {
            $dostkmod = "1";
        }
        if ($login == $nickadmina || $login == $nickadmina2 || $datauser['rights'] >= "1")
        {
            $dostmod = "1";
        }

        // Устанавливаем время начала сессии
        if ($datauser['lastdate'] <= ($realtime - 300))
            mysql_query("update `users` set `sestime`='" . $realtime . "' where `id`='" . $user_id . "';");

        // Обновляем данные
        $totalonsite = $datauser['total_on_site'];
        if ($datauser['lastdate'] >= ($realtime - 300))
            $totalonsite = $totalonsite + $realtime - $datauser['lastdate'];
        mysql_query("update `users` set `total_on_site`='" . $totalonsite . "', `lastdate`='" . $realtime . "', `ip`='" . $ipp . "', `browser`='" . mysql_real_escape_string($agn) . "' where `id`='" . $user_id . "';");
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
}

// Подключаем дополнительные файлы
require_once ($rootpath . 'incfiles/func.php'); // Вспомогательные функции
require_once ($rootpath . 'incfiles/stat.php'); // Статистика
if (!isset($headmod))
    $headmod = '';
if ($headmod == "mainpage")
{
    $textl = $copyright;
}

// Буфферизация вывода
if ($gzip == 1)
{
    ob_start('ob_gzhandler');
} else
{
    ob_start();
}

//if ($offgr == 1)
//{
//    ob_start(offimg);
//}

?>