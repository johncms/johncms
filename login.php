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

$rootpath = '';
require_once('incfiles/core.php');
require_once('incfiles/head.php');
echo '<div class="phdr"><b>Вход в систему</b></div>';

$error = array ();
$captcha = false;
$display_form = 1;
$user_login = isset($_POST['n']) ? check($_POST['n']) : NULL;
$user_pass = isset($_REQUEST['p']) ? check($_REQUEST['p']) : NULL;
$user_mem = isset($_POST['mem']) ? 1 : 0;
$user_code = isset($_POST['code']) ? trim($_POST['code']) : NULL;
if ($user_pass && !$user_login && !$id)
    $error[] = 'Вы не ввели имя';
if (($user_login || $id) && !$user_pass)
    $error[] = 'Вы не ввели пароль';
if ($user_login && (mb_strlen($user_login) < 2 || mb_strlen($user_login) > 20))
    $error[] = 'Допустимая длина имени не менее 2 и не более 20 символов';
if ($user_pass && (mb_strlen($user_pass) < 3 || mb_strlen($user_pass) > 15))
    $error[] = 'Допустимая длина пароля не менее 3 и не более 15 символов';
if (!$error && $user_pass && ($user_login || $id)) {
    // Запрос в базу на юзера
    $sql = $id ? "`id` = '$id'" : "`name_lat`='" . rus_lat(mb_strtolower($user_login)) . "'";
    $req = mysql_query("SELECT * FROM `users` WHERE $sql LIMIT 1");
    if (mysql_num_rows($req)) {
        $user = mysql_fetch_assoc($req);
        if ($user['failed_login'] > 2) {
            if ($user_code) {
                if (mb_strlen($user_code) > 3 && $user_code == $_SESSION['code']) {
                    // Если введен правильный проверочный код
                    unset($_SESSION['code']);
                    $captcha = true;
                } else {
                    // Если проверочный код указан неверно
                    unset($_SESSION['code']);
                    $error[] = 'Проверочный код указан неверно';
                }
            } else {
                // Показываем CAPTCHA
                $display_form = 0;
                echo '<form action="login.php" method="post">' .
                    '<div class="menu"><p><img src="captcha.php?r=' . rand(1000, 9999) . '" alt="Проверочный код"/><br />' .
                    'Введите код с картинки:<br/><input type="text" size="5" maxlength="5"  name="code"/>' .
                    '<input type="hidden" name="n" value="' . $user_login . '"/>' .
                    '<input type="hidden" name="p" value="' . $user_pass . '"/>' .
                    '<input type="hidden" name="mem" value="' . $user_mem . '"/>' .
                    '<input type="submit" name="submit" value="Продолжить"/></p></div></form>';
            }
        }
        if ($user['failed_login'] < 3 || $captcha) {
            if (md5(md5($user_pass)) == $user['password']) {
                // Если логин удачный
                $display_form = 0;
                mysql_query("UPDATE `users` SET `failed_login` = '0' WHERE `id` = '" . $user['id'] . "' LIMIT 1");
                if (!$user['preg']) {
                    // Если регистрация не подтверждена
                    echo '<div class="rmenu"><p>';
                    if (!empty($user['regadm']))
                        echo 'Ваша заявка на регистроацию отклонена.<br />Причина:<br />' . $res['regadm'];
                    else
                        echo 'Приносим извинения, но Ваша заявка на регистрацию ещё не рассмотрена.<br />Пожалуйста ожидайте.';
                    echo '</p></div>';
                } else {
                    // Если все проверки прошли удачно, подготавливаем вход на сайт
                    if ($_POST['mem'] == 1) {
                        // Установка данных COOKIE
                        $cuid = base64_encode($user['id']);
                        $cups = md5($user_pass);
                        setcookie("cuid", $cuid, time() + 3600 * 24 * 365);
                        setcookie("cups", $cups, time() + 3600 * 24 * 365);
                    }
                    // Установка данных сессии
                    $_SESSION['uid'] = $user['id'];
                    $_SESSION['ups'] = md5(md5($user_pass));
                    mysql_query("UPDATE `users` SET `sestime` = '$realtime' WHERE `id` = '" . $user['id'] . "'");
                    $set_user = unserialize($user['set_user']);
                    if ($user['lastdate'] < ($realtime - 3600) && $set_user['digest'])
                        header('Location: ' . $home . '/index.php?act=digest&last=' . $user['lastdate']);
                    else
                        header('Location: ' . $home . '/index.php');
                    echo '<div class="gmenu"><p><b><a href="index.php?act=digest">Войти на сайт</a></b></p></div>';
                }
            } else {
                // Если логин неудачный
                if ($user['failed_login'] < 3) {
                    // Прибавляем к счетчику неудачных логинов
                    $failed_login = $user['failed_login'] + 1;
                    mysql_query("UPDATE `users` SET `failed_login` = '$failed_login' WHERE `id` = '" . $user['id'] . "' LIMIT 1");
                }
                $error[] = 'Авторизация не прошла';
            }
        }
    } else {
        $error[] = 'Авторизация не прошла';
    }
}

if ($display_form) {
    if ($error)
        echo display_error($error);
    echo '<div class="gmenu"><form action="login.php" method="post">' .
        'Имя:<br/><input type="text" name="n" value="' . htmlentities($user_login, ENT_QUOTES, 'UTF-8') . '" maxlength="20"/><br/>' .
        'Пароль:<br/><input type="password" name="p" maxlength="20"/><br/>' .
        '<input type="checkbox" name="mem" value="1" checked="checked"/>Запомнить меня<br/>' .
        '<input type="submit" value="Вход"/></form></div>' .
        '<div class="phdr"><a href="str/skl.php?continue">Забыли пароль?</a></div>';
}

require_once('incfiles/end.php');

?>