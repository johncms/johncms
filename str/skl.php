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

$textl = 'Пароль';
require_once('../incfiles/core.php');
require_once('../incfiles/char.php');
require_once('../incfiles/head.php');

function passgen($length) {
    $vals = "abcdefghijklmnopqrstuvwxyz0123456789";
    for ($i = 1; $i <= $length; $i++) {
        $result .= $vals{rand(0, strlen($vals))};
    }
    return $result;
}

switch ($act) {
    case 'sent':
        // Принимаем и проверяем данные
        $nick = isset($_POST['nick']) ? rus_lat(mb_strtolower(check($_POST['nick']))) : '';
        $email = isset($_POST['email']) ? htmlspecialchars(trim($_POST['email'])) : '';
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $error = false;
        if (!$nick || !$email || !$code)
            $error = 'Необходимо заполнить все поля формы';
        elseif (!isset($_SESSION['code']) || mb_strlen($code) < 4 || $code != $_SESSION['code'])
            $error = 'Проверочный код введен неверно';
        unset($_SESSION['code']);
        if (!$error) {
            // Проверяем данные по базе
            $req = mysql_query("SELECT * FROM `users` WHERE `name_lat` = '$nick' LIMIT 1");
            if (mysql_num_rows($req) == 1) {
                $res = mysql_fetch_array($req);
                if (empty($res['mail']) || $res['mail'] != $email)
                    $error = 'E-mail адрес указан неверно';
                if ($res['rest_time'] > $realtime - 86400)
                    $error = 'Пароль можно восстанавливать не чаще 1 раза в сутки';
            } else {
                $error = 'Такой пользователь не зарегистрирован';
            }
        }
        if (!$error) {
            // Высылаем инструкции на E-mail
            $subject = 'Востановление пароля';
            $mail = "Здравствуйте, " . $res['name'] . "\r\nВы начали процедуру восстановлению пароля на сайте " . $home . "\r\n";
            $mail .= "Для того чтобы восстановить пароль, вам необходимо перейти по ссылке: \n\n$home/str/skl.php?act=set&id=" . $res['id'] . "&code=" . session_id() . "\n\n";
            $mail .= "Ссылка действительна в течение 1 часа\r\n";
            $mail .= "Если это письмо попало к вам по ошибке или вы не собираетесь восстанавливать пароль, то просто проигнорируйте его";
            $adds = "From: <" . $emailadmina . ">\r\n";
            $adds .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
            if (mail($res['mail'], $subject, $mail, $adds)) {
                mysql_query("UPDATE `users` SET `rest_code` = '" . session_id() . "', `rest_time` = '$realtime' WHERE `id` = '" . $res['id'] . "'");
                echo '<div class="gmenu"><p>Инструкции по восстановлению пароля высланы на указанный Вами адрес E-mail</p></div>';
            } else {
                echo '<div class="rmenu"><p>Ошибка отправки E-mail</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo '<div class="rmenu"><p>ОШИБКА!<br />' . $error . '<br /><a href="skl.php">Назад</a></p></div>';
        }
        break;

    case 'set':
        $code = isset($_GET['code']) ? trim($_GET['code']) : '';
        $error = false;
        if (!$id || !$code)
            $error = 'Отсутствуют необходимые данные';
        $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
        if (mysql_num_rows($req) == 1) {
            $res = mysql_fetch_array($req);
            if (empty($res['rest_code']) || empty($res['rest_time']) || $code != $res['rest_code']) {
                $error = 'Восстановление пароля невозможно';
            }
            if (!$error && $res['rest_time'] < $realtime - 3600) {
                $error = 'Время, отведенное на восстановления пароля прошло';
                mysql_query("UPDATE `users` SET `rest_code` = '', `rest_time` = '' WHERE `id` = '$id'");
            }
        } else {
            $error = 'Такого пользователя нет';
        }
        if (!$error) {
            // Высылаем пароль на E-mail
            $pass = passgen(4);
            $subject = 'Ваш новый пароль';
            $mail = "Здравствуйте, " . $res['name'] . "\r\nВы изменили пароль на сайте " . $home . "\r\n";
            $mail .= "Ваш новый пароль: $pass\r\n";
            $mail .= "После входа на сайт, Вы сможете сменить пароль на другой, какой пожелаете.";
            $adds = "From: <" . $emailadmina . ">\n";
            $adds .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
            if (mail($res['mail'], $subject, $mail, $adds)) {
                mysql_query("UPDATE `users` SET `rest_code` = '', `password` = '" . md5(md5($pass)) . "' WHERE `id` = '$id'");
                echo '<div class="phdr">Меняем пароль</div>';
                echo '<div class="gmenu"><p>Пароль успешно изменен.<br />Новый пароль выслан на ваш адрес E-mail</p></div>';
            } else {
                echo '<div class="rmenu"><p>Ошибка отправки E-mail</p></div>';
            }
        } else {
            // Выводим сообщение об ошибке
            echo '<div class="rmenu"><p>ОШИБКА!<br />' . $error . '</p></div>';
        }
        break;

    default:
        echo '<div class="phdr"><b>Восстановление пароля</b></div>';
        echo '<div class="menu"><form action="skl.php?act=sent" method="post">';
        echo '<p>Ваш логин:<br/><input type="text" name="nick" /><br/>';
        echo 'Ваш e-mail:<br/><input type="text" name="email" /></p>';
        echo '<p><img src="../captcha.php?r=' . rand(1000, 9999) . '" alt="Проверочный код"/><br />';
        echo '<input type="text" size="4" maxlength="4"  name="code"/>&nbsp;Введите код</p>';
        echo '<p><input type="submit" value="Отправить"/></p></form></div>';
        echo '<div class="phdr"><small>Пароль будет выслан на E-mail Адрес, указанный в Вашей анкете.<br />ВНИМЕНИЕ! Если в анкете не был указан E-mail адрес, то Вы не сможете восстановить пароль</small></div>';
        break;
}

require_once('../incfiles/end.php');
?>