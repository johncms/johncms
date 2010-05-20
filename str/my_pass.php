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

$headmod = 'pass';
$textl = 'Смена пароля';
require_once ('../incfiles/core.php');
require_once ('../incfiles/head.php');

if (!$user_id) {
    display_error('Только для зарегистрированных посетителей');
    require_once ('../incfiles/end.php');
    exit;
}

if ($id && $id != $user_id && $rights >= 7) {
    // Если был запрос на юзера, то получаем его данные
    $req = mysql_query("SELECT * FROM `users` WHERE `id` = '$id' LIMIT 1");
    if (mysql_num_rows($req)) {
        $user = mysql_fetch_assoc($req);
        if ($user['rights'] > $datauser['rights']) {
            // Если не хватает прав, выводим ошибку
            echo display_error('Вы не можете менять пароль старшего Вас по должности');
            require_once ('../incfiles/end.php');
            exit;
        }
    }
    else {
        echo display_error('Такого пользователя не существует');
        require_once ('../incfiles/end.php');
        exit;
    }
}
else {
    $id = false;
    $user = $datauser;
}

switch ($act) {
    case 'change' :
        $error = array();
        $oldpass = isset ($_POST['oldpass']) ? trim($_POST['oldpass']) : '';
        $newpass = isset ($_POST['newpass']) ? trim($_POST['newpass']) : '';
        $newconf = isset ($_POST['newconf']) ? trim($_POST['newconf']) : '';
        $autologin = isset ($_POST['autologin']) ? 1 : 0;
        if ($rights >= 7) {
            if (!$newpass || !$newconf)
                $error[] = 'Нужно заполнить все поля формы';
        }
        else {
            if (!$oldpass || !$newpass || !$newconf)
                $error[] = 'Нужно заполнить все поля формы';
        }
        if (!$error && $rights < 7 && md5(md5($oldpass)) !== $user['password'])
            $error[] = 'Старый пароль введен неверно';
        if ($newpass != $newconf)
            $error[] = 'Подтверждение нового пароля введено неверно';
        if (preg_match("/[^\da-zA-Z_]+/", $newpass) && !$error)
            $error[] = 'Недопустимые символы в новом пароле';
        if (!$error && (strlen($newpass) < 3 || strlen($newpass) > 10))
            $error[] = 'Длина пароля должна быть минимум 3 и максимум 10 символов';
        if (!$error) {
            // Записываем в базу
            mysql_query("UPDATE `users` SET `password` = '" . mysql_real_escape_string(md5(md5($newpass))) . "' WHERE `id` = '" . $user['id'] . "' LIMIT 1");
            // Проверяем и записываем COOKIES
            if (isset ($_COOKIE['cuid']) && isset ($_COOKIE['cups']))
                setcookie('cups', md5($newpass), time() + 3600 * 24 * 365);
            echo '<div class="gmenu"><p><b>Пароль успешно изменен</b><br />';
            if ($autologin) {
                // Показываем ссылку на Автологин
                echo '</p><p>Ссылка на Автологин:<br /><input type="text" value="' . $home . '/login.php?id=' . $user['id'] . '&amp;p=' . $newpass . '" /><br />';
                echo
                '</p><p><b>Внимание!</b><br />В целях безопасности, никогда не используйте Автологин в ненадежных местах (интернет-кафе, чужие компьютеры и др.)';
            }
            echo '</p></div>';
        }
        else {
            $error[] = '<div><a href="my_pass.php">Повторить</a></div>';
            echo display_error($error);
        }
        break;

    default :
        echo '<div class="phdr"><b>Меняем пароль:</b> ' . $user['name'] . '</div>';
        echo '<form action="my_pass.php?act=change' . ($id ? '&amp;id=' . $id : '') . '" method="post">';
        if (!$id || $rights < 7)
            echo '<div class="menu"><p>Введите старый пароль:<br /><input type="password" name="oldpass" /></p></div>';
        echo '<div class="gmenu"><p>Введите новый пароль:<br /><input type="password" name="newpass" /><br />Повторите пароль:<br /><input type="password" name="newconf" />';
        echo '</p><p><input type="checkbox" value="1" name="autologin" />&nbsp;Показать ссылку на Автологин';
        echo '</p><p><input type="submit" value="Сохранить" name="submit" />';
        echo '</p></div></form>';
        echo '<div class="phdr"><small>Длина пароля мин. 3, макс. 10 символов, разрешены буквы Латинского алфавита и цифры.</small></div>';
        echo '<p><a href="anketa.php?id=' . $user['id'] . '">В анкету</a></p>';
}

require_once ('../incfiles/end.php');

?>