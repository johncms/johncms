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

$textl = 'Регистрация';
$rootpath = '';
require_once ("incfiles/core.php");
require_once ("incfiles/head.php");

if ($regban || !$set['mod_reg']) {
    echo '<p>Регистрация закрыта.</p>';
    require_once ("incfiles/end.php");
    exit;
}

echo '<div class="phdr">Регистрация на сайте</div>';
function regform() {
    $cod = rand(1000, 9999);
    $_SESSION['code'] = $cod;
    echo '<form action="registration.php" method="post"><div class="gmenu"><p>';
    echo '<b>Логин:</b><br/><input type="text" name="nick" maxlength="15" value="' . check($_POST['nick']) . '" /><br />';
    echo
    '<small>Мин. 2, макс. 15 символов.<br />Разрешены буквы Русского и Латинского алфавита,<br />цифры и знаки - = @ ! ? ~ _ ( ) [ ] . * (кроме нуля)</small></p>';
    echo '<p><b>Пароль:</b><br/><input type="text" name="password" maxlength="20" /><br/>';
    echo '<small>Мин. 3, макс. 10 символов.<br />Разрешены буквы Латинского алфавита и цифры.</small></p>';
    echo '<p><b>Пол:</b><br/><select name="sex"><option value="?">-?-</option><option value="m">Муж.</option><option value="zh">Жен.</option></select></p></div>';
    echo '<div class="menu"><p>Имя:<br/><input type="text" name="imname" maxlength="30" value="' . check($_POST['imname']) . '" /><br />';
    echo '<small>Макс. 30 символов.</small></p>';
    echo '<p>О себе: <small>(макс. 500 символов)</small><br/><textarea rows="3" name="about">' . check($_POST['about']) . '</textarea></p></div>';
    echo '<div class="gmenu"><p>Если Вы не видите рисунок с кодом,<br />включите поддержку графики в настройках браузера<br />и обновите страницу.<br />';
    echo '<img src="code.php" alt="Проверочный код"/><br />';
    echo 'Код с картинки:<br/><input type="text" size="4" maxlength="4"  name="kod"/></p></div>';
    echo '<div class="bmenu"><input type="submit" name="submit" value="Регистрация"/></div></form>';
}

if (isset ($_POST['submit'])) {
    // Принимаем переменные
    $reg_kod = intval($_POST['kod']);
    $reg_nick = trim($_POST['nick']);
    $lat_nick = rus_lat(mb_strtolower($reg_nick));
    $reg_pass = trim($_POST['password']);
    $reg_name = trim($_POST['imname']);
    $reg_about = trim($_POST['about']);
    $reg_sex = trim($_POST['sex']);

    $error = false;
    // Проверка Логина
    if (empty ($reg_nick))
        $error = $error . 'Не введён логин!<br/>';
    elseif (mb_strlen($reg_nick) < 2 || mb_strlen($reg_nick) > 15)
        $error = $error . 'Недопустимая длина Логина<br />';
    if (preg_match("/[^1-9a-z\-\@\*\(\)\?\!\~\_\=\[\]]+/", $lat_nick))
        $error = $error . 'Недопустимые символы в Логине!<br/>';
    // Проверка пароля
    if (empty ($reg_pass))
        $error = $error . 'Не введён пароль!<br/>';
    elseif (mb_strlen($reg_pass) < 3 || mb_strlen($reg_pass) > 10)
        $error = $error . 'Недопустимая длина пароля<br />';
    if (preg_match("/[^\da-zA-Z_]+/", $reg_pass))
        $error = $error . 'Недопустимые символы в пароле!<br/>';
    // Проверка имени
    if ($reg_sex == 'm' || $reg_sex == 'zh') {
    }
    else
        $error = $error . 'Не указан пол!<br/>';
    // Проверка кода
    if (empty ($reg_kod))
        $error = $error . 'Не введён проверочный код!<br/>';
    elseif ($reg_kod != $_SESSION['code'])
        $error = $error . 'Проверочный код неверен!<br/>';

    // Проверка переменных
    if (empty ($error)) {
        $pass = md5(md5($reg_pass));
        $reg_name = check(mb_substr($reg_name, 0, 20));
        $reg_about = check(mb_substr($reg_about, 0, 500));
        $reg_sex = check(mb_substr($reg_sex, 0, 2));
        // Проверка, занят ли ник
        $req = mysql_query("select * from `users` where `name_lat`='" . mysql_real_escape_string($lat_nick) . "';");
        if (mysql_num_rows($req) != 0) {
            $error = 'Этот ник уже зарегистрирован!<br/>Выберите другой.<br/>';
        }
    }
    $_SESSION['code'] = rand(1000, 9999);
    if (empty ($error)) {
        $preg = $set['mod_reg'] > 1 ? 1 : 0;
        mysql_query("INSERT INTO `users` SET
		`name`='" . mysql_real_escape_string($reg_nick) . "',
		`name_lat`='" . mysql_real_escape_string($lat_nick) . "',
		`password`='" . mysql_real_escape_string($pass) . "',
		`imname`='" . $reg_name
        . "',
		`about`='" . $reg_about . "',
		`sex`='" . $reg_sex . "',
		`rights`='0',
		`ip`='" . $ipl . "',
		`browser`='" . $agn . "',
		`datereg`='" . $realtime . "',
		`lastdate`='" . $realtime . "',
		`preg`='" . $preg . "';");

        $usid = mysql_insert_id();
        echo "Вы зарегистрированы!<br/>";
        echo "Ваш id: " . $usid . "<br/>";
        echo "Ваш логин: " . $reg_nick . "<br/>";
        echo "Ваш Пароль: " . $reg_pass . "<br/>";
        echo "Ссылка для автовхода:<br/><input type='text' value='" . $home . "/login.php?id=" . $usid . "&amp;p=" . $reg_pass . "' /><br/>";
        if ($set['mod_reg'] == 1) {
            echo "Пожалуйста,ожидайте подтверждения Вашей регистрации администратором<br/>";
        }
        else {
            echo "<br /><a href='login.php?id=" . $usid . "&amp;p=" . $reg_pass . "'>ВХОД</a><br/><br/>";
        }
    }
    else {
        echo '<div class="rmenu"><p><b>ОШИБКА!</b><br />' . $error . '</p></div>';
        regform();
    }
}

// Форма регистрации
else {
    if ($set['mod_reg'] == 1) {
        echo
        '<div class="rmenu"><p>Вы сможете получить авторизованный доступ к разделам сайта после подтверждения Вашей регистрации.<br />Подтверждение проводится 1-2 раза в сутки.</p>';
        echo
        '<p>Просьба не регистрировать ники типа 111, ггг, uuuu и им подобные, они будут сразу же удалены.<br />Также будут удалены ВСЕ профили, которые регистрировались через Прокси серверы.</p></div>';
    }
    regform();
}

require_once ("incfiles/end.php");

?>