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

require_once ("../incfiles/core.php");
require_once ("../incfiles/head.php");

if ($dostadm == 1) {
    if (empty($_GET['user'])) {
        echo "Вы не ввели логин!<br/><a href='main.php'>В админку</a><br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    $qus = @mysql_query("select * from `users` where id='" . intval($_GET['user']) .
        "';");
    $userprof = @mysql_fetch_array($qus);
    $nam = trim($userprof['name']);
    if (($login !== $nickadmina) && ($nam == $nickadmina) || ($nam !== $login) && ($nickadmina
        !== $login) && ($rights == 7) && ($userprof['rights'] == "7")) {
        echo "У ВАС НЕДОСТАТОЧНО ПРАВ ДЛЯ ЭТОГО!<br/>";
        require_once ("../incfiles/end.php");
        exit;
    }
    if ($nam == $login) {
        echo 'ВНИМАНИЕ! ВЫ РЕДАКТИРУЕТЕ CОБСТВЕННЫЙ АККАУНТ<br/>';
    }

    if (!empty($_GET['act'])) {
        $act = check($_GET['act']);
    }
    switch ($act) {
        case "del":
            $user = intval($_GET['user']);
            $q1 = mysql_query("select * from `users` where id='" . $user . "';");
            $arr1 = mysql_fetch_array($q1);
            if ($arr1['name'] != $nickadmina && $arr1['name'] != $nickadmina2 && $arr1['immunity'] ==
                0) {
                if (isset($_GET['yes'])) {
                    // Удаляем профиль юзера
                    mysql_query("DELETE FROM `users` WHERE `id` = '" . $user . "' LIMIT 1;");
                    // Чистим историю нарушений
                    mysql_query("DELETE FROM `cms_ban_users` WHERE `user_id` = '" . $user . "'");
                    // Чистим метки прочтений форума
                    mysql_query("DELETE FROM `cms_forum_rdm` WHERE `user_id` = '" . $user . "'");
                    // Чистим приват
                    mysql_query("DELETE FROM `privat` WHERE `type` = 'in' AND `user` = '" . $arr1['name'] . "'");
                    echo "Пользователь $arr1[name] удалён!<br/>";
                    echo "<a href='main.php'>В админку</a><br/>";
                } else {
                    echo "Вы уверены в удалении юзера $arr1[name]?<br/><a href='editusers.php?act=del&amp;user=" .
                        $user . "&amp;yes'>Да</a> | <a href='../str/anketa.php?user=" . $user .
                        "'>Нет</a><br/>";
                }
            } else {
                echo '<p>Нельзя!!!<br/>';
                if ($arr1['immunity'] == 1) {
                    echo 'Этот пользователь имеет иммунитет.</p>';
                } else {
                    echo 'Нельзя удалять Суперадмина.</p>';
                }
            }
            break;

        case "edit":
            $user = intval($_GET['user']);
            $q1 = mysql_query("select * from `users` where `id` = '" . $user . "';");
            $arr1 = mysql_fetch_array($q1);
            echo '<div class="phdr">Профиль юзера <b>' . $arr1['name'] . '</b></div>';
            $usdata = array("name" => "Логин:", "par" => "ПАРОЛЬ:", "imname" => "Имя:",
                "status" => "Статус:", "live" => "Город:", "mibile" => "Мобила:", "mail" =>
                "E-mail:", "icq" => "ICQ:", "skype" => "Skype:", "jabber" => "Jabber:", "www" =>
                "Сайт:", "about" => "О себе:");
            if (isset($_GET['ok'])) {
                echo '<div class="rmenu">Профиль изменён!</div>';
            }
            echo "<form action='editusers.php?act=yes&amp;user=" . intval($_GET['user']) .
                "' method='post' >";
            foreach ($usdata as $key => $value) {
                echo '<div class="menu">' . $usdata[$key] . '<br/><input type="text" name="' . $key .
                    '" value="' . $userprof[$key] . '"/></div>';
            }
            echo '<div class="menu">';
            if ($userprof['sex'] == "m") {
                echo 'Пол:&nbsp;<select name=\'sex\' title=\'Пол\' value=\'' . $userprof['sex'] .
                    '\'>' . '<option value=\'m\'>М</option>' . '<option value=\'zh\'>Ж</option></select><br/>';
            } else {
                echo 'Пол:&nbsp;<select name=\'sex\' title=\'Пол\' value=\'' . $userprof['sex'] .
                    '\'>' . '<option value=\'zh\'>Ж</option>' . '<option value=\'m\'>М</option></select><br/>';
            }
            echo '</div>';
                echo '<div class="menu">Изменить скин<br/>';
                echo '<select name="skin">';
                $dr = opendir('../theme');
				while ($skindef = readdir($dr))
                {
                    if (($skindef != ".") && ($skindef != ".."))
                    {
                        $skindef = str_replace(".css", "", $skindef);
                        echo '<option' . ($arr1['skin'] == $skindef ? ' selected="selected">' : '>') . $skindef . '</option>';
                    }
                }
                echo '</select></div>';
            if ($dostsadm)
                echo '<div class="gmenu"><input name="immunity" type="checkbox" value="1" ' . ($arr1['immunity'] ?
                    'checked="checked"' : '') . ' />&nbsp;Иммунитет</div>';
            echo '<div class="rmenu"><b>Должность:</b><br/>';
            if ($userprof['rights'] == "1") {
                echo "<input name='admst' type='radio' value='1' checked='checked' />";
            } else {
                echo "<input name='admst' type='radio' value='1'/>";
            }
            echo '&nbsp;Киллер<br/>';
            if ($userprof['rights'] == "2") {
                echo "<input name='admst' type='radio' value='2' checked='checked' />";
            } else {
                echo "<input name='admst' type='radio' value='2'/>";
            }
            echo '&nbsp;Модер чата<br/>';
            if ($userprof['rights'] == "3") {
                echo "<input name='admst' type='radio' value='3' checked='checked' />";
            } else {
                echo "<input name='admst' type='radio' value='3'/>";
            }
            echo '&nbsp;Модер форума<br/>';
            if ($userprof['rights'] == "4") {
                echo "<input name='admst' type='radio' value='4' checked='checked' />";
            } else {
                echo "<input name='admst' type='radio' value='4'/>";
            }
            echo '&nbsp;Модер по загрузкам<br/>';

            if ($userprof['rights'] == "5") {
                echo "<input name='admst' type='radio' value='5' checked='checked' />";
            } else {
                echo "<input name='admst' type='radio' value='5'/>";
            }
            echo '&nbsp;Модер библиотеки<br/>';

            if ($userprof['rights'] == "6") {
                echo "<input name='admst' type='radio' value='6' checked='checked' />";
            } else {
                echo "<input name='admst' type='radio' value='6'/>";
            }
            echo '&nbsp;Супермодератор<br/>';
            if ($dostsadm == 1) {
                if ($userprof['rights'] == "7") {
                    echo "<input name='admst' type='radio' value='7' checked='checked' />";
                } else {
                    echo "<input name='admst' type='radio' value='7'/>";
                }
                echo '&nbsp;Администратор<br/>';
            } else {
                if ($dostadm == 1 && $nam == $login) {
                    echo "<input name='admst' type='hidden' value='7'/>";
                }
            }
            if ($userprof['rights'] == "0") {
                echo "<br /><input name='admst' type='radio' value='0' checked='checked' />";
            } else {
                echo "<br /><input name='admst' type='radio' value='0'/>";
            }
            echo '&nbsp;Обычный юзер</div>';
            echo '<div class="phdr"><input type="submit" value="Сохранить" /></div></form>';
            echo '<p><a href="main.php">В админку</a></p>';
            break;

        case "yes":
            if (!empty($_POST['par'])) {
                $par = check($_POST['par']);
                $par1 = md5(md5($par));
            } else {
                $par1 = $userprof['password'];
            }
            if ($dostsadm) {
                mysql_query("UPDATE `users` SET
				`name` = '" . check($_POST['name']) . "',
				`password` = '" . $par1 . "',
				`immunity` = '" . intval($_POST['immunity']) . "',
				`imname` = '" . check($_POST['imname']) . "',
				`sex` = '" . check($_POST['sex']) . "',
				`mibile` = '" . check($_POST['mibile']) . "',
				`mail` = '" . mysql_real_escape_string(htmlspecialchars($_POST['mail'])) .
                    "',
				`rights` = '" . intval($_POST['admst']) . "',
				`icq` = '" . intval($_POST['icq']) . "',
				`skype` = '" . check($_POST['skype']) . "',
				`jabber` = '" . check($_POST['jabber']) . "',
				`www` = '" . check($_POST['www']) . "',
				`about` = '" . check($_POST['about']) . "',
				`live` = '" . check($_POST['live']) . "',
				`status` = '" . check($_POST['status']) . "',
				`skin`='" . check(trim($_POST['skin'])) . "'
				WHERE `id` = '" . intval($_GET['user']) . "';");
            } else {
                mysql_query("UPDATE `users` SET
				`name` = '" . check($_POST['name']) . "',
				`password` = '" . $par1 . "',
				`imname` = '" . check($_POST['imname']) . "',
				`sex` = '" . check($_POST['sex']) . "',
				`mibile` = '" . check($_POST['mibile']) . "',
				`mail` = '" . mysql_real_escape_string(htmlspecialchars($_POST['mail'])) .
                    "',
				`rights` = '" . intval($_POST['admst']) . "',
				`icq` = '" . intval($_POST['icq']) . "',
				`skype` = '" . check($_POST['skype']) . "',
				`jabber` = '" . check($_POST['jabber']) . "',
				`www` = '" . check($_POST['www']) . "',
				`about` = '" . check($_POST['about']) . "',
				`live` = '" . check($_POST['live']) . "',
				`status` = '" . check($_POST['status']) . "',
				`skin`='" . check(trim($_POST['skin'])) . "'
				WHERE `id` = '" . intval($_GET['user']) . "';");
            }
            if (!empty($_POST['par'])) {
                echo '<p>Вы изменили пароль юзера!<br/>Новый пароль: ' . $par . '<br/>';
                echo '<a href="editusers.php?act=edit&amp;ok=1&amp;user=' . intval($_GET['user']) .
                    '">Анкета</a></p><p>';
                echo '<a href="main.php">В админку</a></p>';
            } else {
                header("Location: editusers.php?act=edit&ok=1&user=" . intval($_GET['user']));
            }

            break;
    }
} else {
    header("Location: ../index.php?err");
}


require_once ("../incfiles/end.php");

?>