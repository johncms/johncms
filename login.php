<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

define('_IN_JOHNCMS', 1);

$headmod = 'login';
require('incfiles/core.php');
require('incfiles/head.php');

if(core::$user_id){
    header('Location: index.php'); exit;
} else {
    echo '<div class="phdr"><b>' . $lng['login'] . '</b></div>';
    $error = array();
    $captcha = FALSE;
    $display_form = 1;
    $user_login = isset($_POST['n']) ? trim($_POST['n']) : NULL;
    $user_pass = isset($_POST['p']) ? trim($_POST['p']) : NULL;
    $user_mem = isset($_POST['mem']) ? 1 : 0;
    $user_code = isset($_POST['code']) ? trim($_POST['code']) : NULL;
    if ($user_pass && !$user_login) {
        $error[] = $lng['error_login_empty'];
    }
    if ($user_login && !$user_pass) {
        $error[] = $lng['error_empty_password'];
    }
    if ($user_login && (mb_strlen($user_login) < 2 || mb_strlen($user_login) > 20)) {
        $error[] = $lng['nick'] . ': ' . $lng['error_wrong_lenght'];
    }
    if ($user_pass && (mb_strlen($user_pass) < 3 || mb_strlen($user_pass) > 15)) {
        $error[] = $lng['password'] . ': ' . $lng['error_wrong_lenght'];
    }
    if (!$error && $user_pass && $user_login) {
        // Запрос в базу на юзера
        $stmt = $db->prepare('SELECT * FROM `users` WHERE `name_lat`= ? LIMIT 1');
        $stmt->execute([
            functions::rus_lat(mb_strtolower($user_login))
        ]);
        if ($stmt->rowCount()) {
            $user = $stmt->fetch();
            if ($user['failed_login'] > 2) {
                if ($user_code) {
                    if (mb_strlen($user_code) > 3 && $user_code == $_SESSION['code']) {
                        // Если введен правильный проверочный код
                        unset($_SESSION['code']);
                        $captcha = TRUE;
                    } else {
                        // Если проверочный код указан неверно
                        unset($_SESSION['code']);
                        $error[] = $lng['error_wrong_captcha'];
                    }
                } else {
                    // Показываем CAPTCHA
                    $display_form = 0;
                    echo '<form action="login.php' . ($id ? '?id=' . $id : '') . '" method="post">' .
                        '<div class="menu"><p><img src="captcha.php?r=' . rand(1000, 9999) . '" alt="' . $lng['verifying_code'] . '"/><br />' .
                        $lng['enter_code'] . ':<br/><input type="text" size="5" maxlength="5"  name="code"/>' .
                        '<input type="hidden" name="n" value="' . _e($user_login) . '"/>' .
                        '<input type="hidden" name="p" value="' . _e($user_pass) . '"/>' .
                        '<input type="hidden" name="mem" value="' . $user_mem . '"/>' .
                        '<input type="submit" name="submit" value="' . $lng['continue'] . '"/></p></div></form>';
                }
            }
            if ($user['failed_login'] < 3 || $captcha) {
                if (md5(md5($user_pass)) == $user['password']) {
                    // Если логин удачный
                    $display_form = 0;
                    $db->exec("UPDATE `users` SET `failed_login` = '0' WHERE `id` = '" . $user['id'] . "'");
                    if (!$user['preg']) {
                        // Если регистрация не подтверждена
                        echo '<div class="rmenu"><p>' . $lng['registration_not_approved'] . '</p></div>';
                    } else {
                        // Если все проверки прошли удачно, подготавливаем вход на сайт
                        if (isset($_POST['mem'])) {
                            // Установка данных COOKIE
                            $cuid = base64_encode($user['id']);
                            $cups = md5($user_pass);
                            setcookie("cuid", $cuid, time() + 3600 * 24 * 365);
                            setcookie("cups", $cups, time() + 3600 * 24 * 365);
                        }
                        // Установка данных сессии
                        $_SESSION['uid'] = $user['id'];
                        $_SESSION['ups'] = md5(md5($user_pass));
                        header('Location: ' . $set['homeurl'] . '/index.php'); exit;
                    }
                } else {
                    // Если логин неудачный
                    if ($user['failed_login'] < 3) {
                        // Прибавляем к счетчику неудачных логинов
                        $db->exec("UPDATE `users` SET `failed_login` = '" . ($user['failed_login'] + 1) . "' WHERE `id` = '" . $user['id'] . "'");
                    }
                    $error[] = $lng['authorisation_not_passed'];
                }
            }
        } else {
            $error[] = $lng['authorisation_not_passed'];
        }
    }
    if ($display_form) {
        if ($error)
            echo functions::display_error($error);

        $info = '';
        if (core::$system_set['site_access'] == 0 || core::$system_set['site_access'] == 1) {
            if (core::$system_set['site_access'] == 0) {
                $info = '<div class="rmenu">' . $lng['info_only_sv'] . '</div>';
            } elseif (core::$system_set['site_access'] == 1) {
                $info = '<div class="rmenu">' . $lng['info_only_adm'] . '</div>';
            }
        }

        echo $info;

        echo '<div class="gmenu"><form action="login.php" method="post"><p>' . $lng['login_name'] . ':<br/>' .
            '<input type="text" name="n" value="' . _e($user_login) . '" maxlength="20"/>' .
            '<br/>' . $lng['password'] . ':<br/>' .
            '<input type="password" name="p" maxlength="20"/></p>' .
            '<p><input type="checkbox" name="mem" value="1" checked="checked"/>' . $lng['remember'] . '</p>' .
            '<p><input type="submit" value="' . $lng['login'] . '"/></p>' .
            '</form></div>' .
            '<div class="menu"><p>' . functions::image('user.png') . '<a href="registration.php">' . $lng['registration'] . '</a></p></div>' .
            '<div class="bmenu"><p>' . functions::image('lock.png') . '<a href="users/skl.php?continue">' . $lng['forgotten_password'] . '?</a></p></div>';
    }
}

require('incfiles/end.php');
