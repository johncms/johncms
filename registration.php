<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS                Mobile Content Management System                    //
// Project site:          http://johncms.com                                  //
// Support site:          http://gazenwagen.com                               //
////////////////////////////////////////////////////////////////////////////////
// Lead Developer:        Oleg Kasyanov   (AlkatraZ)  alkatraz@gazenwagen.com //
// Development Team:      Eugene Ryabinin (john77)    john77@gazenwagen.com   //
//                        Dmitry Liseenko (FlySelf)   flyself@johncms.com     //
////////////////////////////////////////////////////////////////////////////////
*/

define('_IN_JOHNCMS', 1);
$textl = $lng['registration'];
$rootpath = '';
require('incfiles/core.php');
require('incfiles/head.php');
// Загружаем язык Регистрации
$lng_reg = $core->load_lng('reg');
// Если регистрация закрыта, выводим предупреждение
if ($core->regban || !$set['mod_reg']) {
    echo '<p>' . $lng_reg['registration_closed'] . '</p>';
    require('incfiles/end.php');
    exit;
}
echo '<div class="phdr"><b>' . $lng['registration'] . '</b></div>';
function regform() {
    // Форма регистрации
    global $lng_reg;
    echo '<form action="registration.php" method="post"><div class="gmenu">' .
        '<p><h3>' . $lng_reg['login'] . '</h3>' .
        '<input type="text" name="nick" maxlength="15" value="' . htmlspecialchars($_POST['nick']) . '" /><br />' .
        '<small>' . $lng_reg['login_help'] . '</small></p>' .
        '<p><h3>' . $lng_reg['password'] . '</h3>' .
        '<input type="text" name="password" maxlength="20" value="' . htmlspecialchars($_POST['password']) . '"/><br/>' .
        '<small>' . $lng_reg['password_help'] . '</small></p>' .
        '<p><h3>' . $lng_reg['sex'] . '</h3>' .
        '<select name="sex">' .
        '<option value="?">-?-</option>' .
        '<option value="m">' . $lng_reg['sex_m'] . '</option>' .
        '<option value="zh">' . $lng_reg['sex_w'] . '</option>' .
        '</select></p></div>' .
        '<div class="menu">' .
        '<p><h3>' . $lng_reg['name'] . '</h3>' .
        '<input type="text" name="imname" maxlength="30" value="' . htmlspecialchars($_POST['imname']) . '" /><br />' .
        '<small>' . $lng_reg['name_help'] . '</small></p>' .
        '<p><h3>' . $lng_reg['about'] . '</h3>' .
        '<textarea rows="3" name="about">' . htmlspecialchars($_POST['about']) . '</textarea><br />' .
        '<small>' . $lng_reg['about_help'] . '</small></p></div>' .
        '<div class="gmenu"><p>' .
        '<img src="captcha.php?r=' . rand(1000, 9999) . '" alt="' . $lng_reg['captcha'] . '" border="1"/><br />' . $lng_reg['captcha'] . ':<br/><input type="text" size="5" maxlength="5"  name="kod"/><br />' .
        '<small>' . $lng_reg['captcha_help'] . '</small></p>' .
        '<p><input type="submit" name="submit" value="' . $lng_reg['registration'] . '"/></p></div></form>' .
        '<div class="phdr"><small>' . $lng_reg['registration_terms'] . '</small></div>';
}
if (isset($_POST['submit'])) {
    // Принимаем переменные
    $reg_kod = isset($_POST['kod']) ? trim($_POST['kod']) : '';
    $reg_nick = isset($_POST['nick']) ? trim($_POST['nick']) : '';
    $lat_nick = functions::rus_lat(mb_strtolower($reg_nick));
    $reg_pass = isset($_POST['password']) ? trim($_POST['password']) : '';
    $reg_name = isset($_POST['imname']) ? trim($_POST['imname']) : '';
    $reg_about = isset($_POST['about']) ? trim($_POST['about']) : '';
    $reg_sex = isset($_POST['sex']) ? trim($_POST['sex']) : '';
    $error = array ();
    // Проверка Логина
    if (empty($reg_nick))
        $error[] = $lng_reg['error_nick_empty'];
    elseif (mb_strlen($reg_nick) < 2 || mb_strlen($reg_nick) > 15)
        $error[] = $lng_reg['error_nick_lenght'];
    if (preg_match("/[^\da-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick))
        $error[] = $lng['nick'] . ': ' . $lng['error_wrong_symbols'];
    // Проверка пароля
    if (empty($reg_pass))
        $error[] = $lng['error_empty_password'];
    elseif (mb_strlen($reg_pass) < 3 || mb_strlen($reg_pass) > 10)
        $error[] = $lng['password'] . ': ' . $lng['error_wrong_lenght'];
    if (preg_match("/[^\dA-Za-z]+/", $reg_pass))
        $error[] = $lng['password'] . ': ' . $lng['error_wrong_symbols'];
    // Проверка имени
    if ($reg_sex == 'm' || $reg_sex == 'zh') { }
    else
        $error[] = $lng_reg['error_sex'];
    // Проверка кода CAPTCHA
    if (empty($reg_kod) || mb_strlen($reg_kod) < 4 || $reg_kod != $_SESSION['code'])
        $error[] = $lng['error_wrong_captcha'];
    unset($_SESSION['code']);
    // Проверка переменных
    if (empty($error)) {
        $pass = md5(md5($reg_pass));
        $reg_name = functions::check(mb_substr($reg_name, 0, 20));
        $reg_about = functions::check(mb_substr($reg_about, 0, 500));
        $reg_sex = functions::check(mb_substr($reg_sex, 0, 2));
        // Проверка, занят ли ник
        $req = mysql_query("SELECT * FROM `users` WHERE `name_lat`='" . mysql_real_escape_string($lat_nick) . "'");
        if (mysql_num_rows($req) != 0) {
            $error[] = $lng_reg['error_nick_occupied'];
        }
    }
    if (empty($error)) {
        $preg = $set['mod_reg'] > 1 ? 1 : 0;
        mysql_query("INSERT INTO `users` SET
            `name` = '" . mysql_real_escape_string($reg_nick) . "',
            `name_lat` = '" . mysql_real_escape_string($lat_nick) . "',
            `password` = '" . mysql_real_escape_string($pass) . "',
            `imname` = '$reg_name',
            `about` = '$reg_about',
            `sex` = '$reg_sex',
            `rights` = '0',
            `ip` = '$ip',
            `browser` = '" . mysql_real_escape_string($agn) . "',
            `datereg` = '$realtime',
            `lastdate` = '$realtime',
            `preg` = '$preg'
        ");
        $usid = mysql_insert_id();
        echo '<div class="menu"><p><h3>' . $lng_reg['you_registered'] . '</h3>' . $lng_reg['your_id'] . ': <b>' . $usid . '</b><br/>' . $lng_reg['your_login'] . ': <b>' . $reg_nick . '</b><br/>' . $lng_reg['your_password'] . ': <b>' . $reg_pass . '</b></p>' .
            '<p><h3>' . $lng_reg['your_link'] . '</h3><input type="text" value="' . $set['homeurl'] . '/login.php?id=' . $usid . '&amp;p=' . $reg_pass . '" /><br/>';
        if ($set['mod_reg'] == 1) {
            echo '<p><span class="red"><b>' . $lng_reg['moderation_note'] . '</b></span></p>';
        } else {
            echo '<br /><a href="login.php?id=' . $usid . '&amp;p=' . $reg_pass . '">' . $lng_reg['enter'] . '</a><br/><br/>';
        }
        echo '</p></div>';
    } else {
        echo functions::display_error($error);
        regform();
    }
} else {
    // Форма регистрации
    if ($set['mod_reg'] == 1) {
        echo '<div class="rmenu"><p>' . $lng_reg['moderation_warning'] . '</p></div>';
    }
    regform();
}

require('incfiles/end.php');
?>