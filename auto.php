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
$headmod = 'auto';
$rootpath = '';
require_once ("incfiles/core.php");

// Получение данных
$auto_login = isset($_GET['id']) ? intval(trim($_GET['id'])) : false;
$auto_pass = isset($_GET['p']) ? check(trim($_GET['p'])) : false;
$form_login = isset($_POST['n']) ? check(trim($_POST['n'])) : false;
$form_pass = isset($_POST['p']) ? check(trim($_POST['p'])) : false;

if ($form_login && $form_pass)
{
    $user_ps = md5(md5($form_pass));
	$req = mysql_query("SELECT * FROM `users` WHERE `name_lat`='" . rus_lat(mb_strtolower(($form_login))) . "' LIMIT 1");
} elseif ($auto_login && $auto_pass)
{
    $user_ps = md5(md5($auto_pass));
	$req = mysql_query("SELECT * FROM `users` WHERE `id`='" . $auto_login . "' LIMIT 1");
} else
{
    header("Location: in.php?msg=1");
    exit;
}

// Проверка Логина
if (mysql_num_rows($req) == 0)
{
    header("Location: in.php?msg=2");
    exit;
}

$res = mysql_fetch_array($req);

// Проверка пароля
if ($res['password'] != $user_ps)
{
    header("Location: in.php?msg=2");
    exit;
}

// Если регистрация еще не подтверждена
if ($res['preg'] == "0" && $res['regadm'] == "")
{
    header("Location: in.php?msg=3");
    exit;
}

// Если регистрация отклонена
if ($res['preg'] == "0" && $res['regadm'] !== "")
{
    $_SESSION['otkl'] = $res['regadm'];
    header("Location: in.php?msg=4");
    exit;
}

$user_id = $res['id'];

// Установка данных COOKIE
if ($_POST['mem'] == 1)
{
    $cuid = base64_encode($user_id);
    $cups = base64_encode($form_pass);
    setcookie("cuid", $cuid, time() + 3600 * 24 * 365);
    setcookie("cups", $cups, time() + 3600 * 24 * 365);
}

// Установка данных сессии
$_SESSION['uid'] = $user_id;
$_SESSION['ups'] = $user_ps;
mysql_query("update `users` set `sestime`='" . $realtime . "' where `id`='" . $user_id . "';");
header("Location: index.php?mod=digest");

?>