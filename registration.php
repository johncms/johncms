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

require('incfiles/core.php');
$textl = $lng['registration'];
$headmod = 'registration';
require('incfiles/head.php');
$lng_reg = core::load_lng('registration');

if (core::$user_id){
    header('Location: index.php'); exit;
}
// Если регистрация закрыта, выводим предупреждение
if (core::$deny_registration || !$set['mod_reg'] || core::$user_id) {
    echo '<p>' . $lng_reg['registration_closed'] . '</p>';
    require('incfiles/end.php');
    exit;
}

$captcha = isset($_POST['captcha']) ? trim($_POST['captcha']) : NULL;
$reg_nick = isset($_POST['nick']) ? trim($_POST['nick']) : '';
$lat_nick = functions::rus_lat(mb_strtolower($reg_nick));
$reg_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
$reg_name = isset($_POST['imname']) ? trim(mb_substr($_POST['imname'], 0, 30)) : '';
$reg_about = isset($_POST['about']) ? trim(mb_substr($_POST['about'], 0, 500)) : '';
$reg_sex = isset($_POST['sex']) ? trim(mb_substr($_POST['sex'], 0, 2)) : '';

echo '<div class="phdr"><b>' . $lng['registration'] . '</b></div>';
if (isset($_POST['submit'])) {
    // Принимаем переменные
    $error = array();

    // Проверка Логина
    if (empty($reg_nick)) {
        $error['login'][] = $lng_reg['error_nick_empty'];
    } elseif (mb_strlen($reg_nick) < 5 || mb_strlen($reg_nick) > 20) {
        $error['login'][] = $lng_reg['error_nick_lenght'];
    }

    if (preg_match('/[^[:alnum:]_.]/', $lat_nick)) {
        $error['login'][] = $lng['error_wrong_symbols'];
    }

    // Проверка пароля
    if (empty($reg_pass)) {
        $error['password'][] = $lng['error_empty_password'];
    } elseif (mb_strlen($reg_pass) < 6) {
        $error['password'][] = $lng['error_wrong_lenght'];
    }

    // Проверка пола
    if ($reg_sex != 'm' && $reg_sex != 'zh') {
        $error['sex'] = $lng_reg['error_sex'];
    }

    // Проверка кода CAPTCHA
    if (!$captcha
        || !isset($_SESSION['code'])
        || mb_strlen($captcha) < 4
        || $captcha != $_SESSION['code']
    ) {
        $error['captcha'] = $lng['error_wrong_captcha'];
    }
    unset($_SESSION['code']);

    // Проверка переменных
    if (empty($error)) {
        $pass = md5(md5($reg_pass));
        // Проверка, занят ли ник
        $stmt = $db->prepare('SELECT `id` FROM `users` WHERE `name_lat` = ? LIMIT 1');
        $stmt->execute([
            $lat_nick
        ]);
        if ($stmt->rowCount()) {
            $error['login'][] = $lng_reg['error_nick_occupied'];
        }
    }
    if (empty($error)) {
        $preg = $set['mod_reg'] > 1 ? 1 : 0;
        $stmt = $db->prepare("INSERT INTO `users` SET
            `name` = ?,
            `name_lat` = ?,
            `password` = ?,
            `imname` = ?,
            `about` = ?,
            `sex` = '$reg_sex',
            `rights` = '0',
            `ip` = '" . core::$ip . "',
            `ip_via_proxy` = '" . core::$ip_via_proxy . "',
            `browser` = ?,
            `datereg` = '" . time() . "',
            `lastdate` = '" . time() . "',
            `sestime` = '" . time() . "',
            `preg` = '$preg',
            `set_user` = '',
            `set_forum` = '',
            `set_mail` = '',
            `smileys` = ''
        ");
        $stmt->execute([
            $reg_nick,
            $lat_nick,
            $pass,
            $reg_name,
            $reg_about,
            $agn
        ]);
        $usid = $db->lastInsertId();

        // Отправка системного сообщения
        $set_mail = unserialize($set['setting_mail']);

        if (!isset($set_mail['message_include'])) {
            $set_mail['message_include'] = 0;
        }

        if ($set_mail['message_include']) {
            $array = array('{LOGIN}', '{TIME}');
            $array_replace = array($reg_nick, '{TIME=' . time() . '}');

            if (empty($set['them_message'])) {
                $set['them_message'] = $lng_mail['them_message'];
            }

            if (empty($set['reg_message'])) {
                $set['reg_message'] = $lng['hi'] . ", {LOGIN}\r\n" . $lng_mail['pleased_see_you'] . "\r\n" . $lng_mail['come_my_site'] . "\r\n" . $lng_mail['respectfully_yours'];
            }

            $theme = str_replace($array, $array_replace, $set['them_message']);
            $system = str_replace($array, $array_replace, $set['reg_message']);
            $stmt = $db->prepare("INSERT INTO `cms_mail` SET
			    `user_id` = '0',
			    `from_id` = '" . $usid . "',
			    `text` = ?,
			    `time` = '" . time() . "',
			    `sys` = '1',
			    `them` = ?
			");
            $stmt->execute([
                $system,
                $theme
            ]);
        }

        echo '<div class="menu"><p><h3>' . $lng_reg['you_registered'] . '</h3>' . $lng_reg['your_id'] . ': <b>' . $usid . '</b><br/>' . $lng_reg['your_login'] . ': <b>' . $reg_nick . '</b><br/>' . $lng_reg['your_password'] . ': <b>' . _e($reg_pass) . '</b></p>';

        if ($set['mod_reg'] == 1) {
            echo '<p><span class="red"><b>' . $lng_reg['moderation_note'] . '</b></span></p>';
        } else {
            $_SESSION['uid'] = $usid;
            $_SESSION['ups'] = md5(md5($reg_pass));
            echo '<p><a href="' . $home . '">' . $lng_reg['enter'] . '</a></p>';
        }

        echo '</div>';
        require('incfiles/end.php');
        exit;
    }
}

/*
-----------------------------------------------------------------
Форма регистрации
-----------------------------------------------------------------
*/
if ($set['mod_reg'] == 1) echo '<div class="rmenu"><p>' . $lng_reg['moderation_warning'] . '</p></div>';
echo '<form action="registration.php" method="post"><div class="gmenu">' .
    '<p><h3>' . $lng_reg['login'] . '</h3>' .
    (isset($error['login']) ? '<span class="red"><small>' . implode('<br />', $error['login']) . '</small></span><br />' : '') .
    '<input type="text" name="nick" maxlength="15" value="' . _e($reg_nick) . '"' . (isset($error['login']) ? ' style="background-color: #FFCCCC"' : '') . '/><br />' .
    '<small>' . $lng_reg['login_help'] . '</small></p>' .
    '<p><h3>' . $lng_reg['password'] . '</h3>' .
    (isset($error['password']) ? '<span class="red"><small>' . implode('<br />', $error['password']) . '</small></span><br />' : '') .
    '<input type="text" name="password" maxlength="20" value="' . _e($reg_pass) . '"' . (isset($error['password']) ? ' style="background-color: #FFCCCC"' : '') . '/><br/>' .
    '<small>' . $lng_reg['password_help'] . '</small></p>' .
    '<p><h3>' . $lng_reg['sex'] . '</h3>' .
    (isset($error['sex']) ? '<span class="red"><small>' . $error['sex'] . '</small></span><br />' : '') .
    '<select name="sex"' . (isset($error['sex']) ? ' style="background-color: #FFCCCC"' : '') . '>' .
    '<option value="?">-?-</option>' .
    '<option value="m"' . ($reg_sex == 'm' ? ' selected="selected"' : '') . '>' . $lng_reg['sex_m'] . '</option>' .
    '<option value="zh"' . ($reg_sex == 'zh' ? ' selected="selected"' : '') . '>' . $lng_reg['sex_w'] . '</option>' .
    '</select></p></div>' .
    '<div class="menu">' .
    '<p><h3>' . $lng_reg['name'] . '</h3>' .
    '<input type="text" name="imname" maxlength="30" value="' . _e($reg_name) . '" /><br />' .
    '<small>' . $lng_reg['name_help'] . '</small></p>' .
    '<p><h3>' . $lng_reg['about'] . '</h3>' .
    '<textarea rows="3" name="about">' . _e($reg_about) . '</textarea><br />' .
    '<small>' . $lng_reg['about_help'] . '</small></p></div>' .
    '<div class="gmenu"><p>' .
    '<h3>' . $lng_reg['captcha'] . '</h3>' .
    '<img src="captcha.php?r=' . rand(1000, 9999) . '" alt="' . $lng_reg['captcha'] . '" border="1"/><br />' .
    (isset($error['captcha']) ? '<span class="red"><small>' . $error['captcha'] . '</small></span><br />' : '') .
    '<input type="text" size="5" maxlength="5"  name="captcha" ' . (isset($error['captcha']) ? ' style="background-color: #FFCCCC"' : '') . '/><br />' .
    '<small>' . $lng_reg['captcha_help'] . '</small></p>' .
    '<p><input type="submit" name="submit" value="' . $lng_reg['registration'] . '"/></p></div></form>' .
    '<div class="phdr"><small>' . $lng_reg['registration_terms'] . '</small></div>';

require('incfiles/end.php');
