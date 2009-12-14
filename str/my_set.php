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

$headmod = 'userset';
$textl = 'Мои настройки';
require_once ('../incfiles/core.php');
require_once ('../incfiles/head.php');

if (!$user_id) {
    echo display_error('Только для авторизованных');
    require_once ('../incfiles/end.php');
    exit;
}

switch ($act) {
    case 'forum' :
        ////////////////////////////////////////////////////////////
        // Настройки форума                                       //
        ////////////////////////////////////////////////////////////
        $set_forum = array();
        $set_forum = unserialize($datauser['set_forum']);
        echo '<div class="phdr"><b>Настройки Форума</b></div>';
        echo '<div class="bmenu"><small>Данные настройки влияют на отображение информации Форума</small></div>';
        if (isset ($_POST['submit'])) {
            $set_forum['farea'] = isset ($_POST['farea']) ? 1 : 0;
            $set_forum['upfp'] = isset ($_POST['upfp']) ? 1 : 0;
            $set_forum['farea_w'] = isset ($_POST['farea_w']) ? intval($_POST['farea_w']) : 20;
            $set_forum['farea_h'] = isset ($_POST['farea_h']) ? intval($_POST['farea_h']) : 2;
            $set_forum['postclip'] = isset ($_POST['postclip']) ? intval($_POST['postclip']) : 1;
            $set_forum['postcut'] = isset ($_POST['postcut']) ? intval($_POST['postcut']) : 1;
            if ($set_forum['postclip'] < 0 || $set_forum['postclip'] > 2)
                $set_forum['postclip'] = 1;
            if ($set_forum['postcut'] < 0 || $set_forum['postcut'] > 3)
                $set_forum['postcut'] = 1;
            if ($set_forum['farea_w'] < 10)
                $set_forum['farea_w'] = 10;
            elseif ($set_forum['farea_w'] > 80)
                $set_forum['farea_w'] = 80;
            if ($set_forum['farea_h'] < 1)
                $set_forum['farea_h'] = 1;
            elseif ($set_forum['farea_h'] > 9)
                $set_forum['farea_h'] = 9;
            mysql_query("UPDATE `users` SET `set_forum` = '" . mysql_real_escape_string(serialize($set_forum)) . "' WHERE `id` = '$user_id' LIMIT 1");
            echo '<div class="rmenu">Настройки сохранены</div>';
        }
        if (isset ($_GET['reset']) || empty ($set_forum)) {
            $set_forum = array();
            $set_forum['farea'] = 0;
            $set_forum['upfp'] = 0;
            $set_forum['farea_w'] = 20;
            $set_forum['farea_h'] = 4;
            $set_forum['postclip'] = 1;
            $set_forum['postcut'] = 2;
            mysql_query("UPDATE `users` SET `set_forum` = '" . mysql_real_escape_string(serialize($set_forum)) . "' WHERE `id` = '$user_id' LIMIT 1");
            echo '<div class="rmenu">Установлены настройки по умолчанию</div>';
        }
        echo '<form action="my_set.php?act=forum" method="post"><div class="menu"><p><h3>Основные настройки</h3>';
        echo '<input name="upfp" type="checkbox" value="1" ' . ($set_forum['upfp'] ? 'checked="checked"' : '') . ' />&nbsp;Обратная сортировка<br/>';
        echo '</p><p><h3>Ввод сообщения</h3>';
        echo '<input type="text" name="farea_w" size="2" maxlength="2" value="' . $set_forum['farea_w'] . '"/> Ширина формы (10-80)<br />';
        echo '<input type="text" name="farea_h" size="2" maxlength="1" value="' . $set_forum['farea_h'] . '"/> Высота формы (1-9)<br />';
        echo '<input name="farea" type="checkbox" value="1" ' . ($set_forum['farea'] ? 'checked="checked"' : '') . ' />&nbsp;Включить поле ввода<br/>';
        echo '</p><p><h3>Закреплять 1-й пост</h3>';
        echo '<input type="radio" value="2" name="postclip" ' . ($set_forum['postclip'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;всегда<br />';
        echo '<input type="radio" value="1" name="postclip" ' . ($set_forum['postclip'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;в непрочитанных<br />';
        echo '<input type="radio" value="0" name="postclip" ' . (!$set_forum['postclip'] ? 'checked="checked"' : '') . '/>&nbsp;никогда';
        echo '</p><p><h3>Обрезка постов</h3>';
        echo '<input type="radio" value="1" name="postcut" ' . ($set_forum['postcut'] == 1 ? 'checked="checked"' : '') . '/>&nbsp;500 символов<br />';
        echo '<input type="radio" value="2" name="postcut" ' . ($set_forum['postcut'] == 2 ? 'checked="checked"' : '') . '/>&nbsp;1000 символов<br />';
        echo '<input type="radio" value="3" name="postcut" ' . ($set_forum['postcut'] == 3 ? 'checked="checked"' : '') . '/>&nbsp;3000 символов<br />';
        echo '<input type="radio" value="0" name="postcut" ' . (!$set_forum['postcut'] ? 'checked="checked"' : '') . '/>&nbsp;не обрезать<br />';
        echo '</p><p><input type="submit" name="submit" value="Сохранить"/></p></div></form>';
        echo '<div class="phdr"><a href="my_set.php?act=forum&amp;reset">Сброс настроек</a></div>';
        echo '<p><a href="../forum">В форум</a><br /><a href="../index.php?act=cab">В кабинет</a></p>';
        break;

    case 'chat' :
        ////////////////////////////////////////////////////////////
        // Настройки Чата                                         //
        ////////////////////////////////////////////////////////////
        $mood = array("нейтральное", "бодрое", "прекрасное", "весёлое", "унылое", "ангельское", "агрессивное", "удивленное", "злое", "сердитое",
        "сонное", "озлобленное", "скучающее", "оживлённое", "угрюмое", "размышляющее", "занятое", "нахальное", "холодное", "смущённое", "крутое",
        "дьявольское", "сварливое", "счастливое", "горячее", "влюблённое", "невинное", "вдохновлённое", "одинокое", "скрытое", "задумчивое",
        "психоделическое", "расслабленое", "грустное", "испуганное", "шокированное", "потрясенное", "хитрое", "усталое", "утомленное");
        $set_chat = array();
        $set_chat = unserialize($datauser['set_chat']);
        echo '<div class="phdr"><b>Настройки Чата</b></div>';
        echo '<div class="bmenu"><small>Индивидуальная настройка Чата</small></div>';
        if (isset ($_POST['submit'])) {
            $set_chat['refresh'] = isset ($_POST['refresh']) ? intval($_POST['refresh']) : 20;
            $set_chat['chmes'] = isset ($_POST['chmes']) ? intval($_POST['chmes']) : 10;
            $set_chat['carea'] = isset ($_POST['carea']) ? 1 : 0;
            $set_chat['carea_w'] = isset ($_POST['carea_w']) ? intval($_POST['carea_w']) : 20;
            $set_chat['carea_h'] = isset ($_POST['carea_h']) ? intval($_POST['carea_h']) : 2;
            $set_chat['mood'] = (isset ($_POST['mood']) && in_array(trim($_POST['mood']), $mood)) ? trim($_POST['mood']) : 'нейтральное';
            $mood_adm = isset ($_POST['mood_adm']) ? check(mb_substr(trim($_POST['mood_adm']), 0, 30)) : '';
            if ($set_chat['refresh'] < 10)
                $set_chat['refresh'] = 10;
            elseif ($set_chat['refresh'] > 99)
                $set_chat['refresh'] = 99;
            if ($set_chat['chmes'] < 5)
                $set_chat['chmes'] = 5;
            elseif ($set_chat['chmes'] > 40)
                $set_chat['chmes'] = 40;
            if ($set_chat['carea_w'] < 10)
                $set_chat['carea_w'] = 10;
            elseif ($set_chat['carea_w'] > 80)
                $set_chat['carea_w'] = 80;
            if ($set_chat['carea_h'] < 1)
                $set_chat['carea_h'] = 1;
            elseif ($set_chat['carea_h'] > 9)
                $set_chat['carea_h'] = 9;
            if ($rights >= 7 && !empty ($mood_adm))
                $set_chat['mood'] = $mood_adm;
            mysql_query("UPDATE `users` SET `set_chat` = '" . mysql_real_escape_string(serialize($set_chat)) . "' WHERE `id` = '$user_id' LIMIT 1");
            echo '<div class="rmenu">Настройки сохранены</div>';
        }
        if (isset ($_GET['reset']) || empty ($set_chat)) {
            $set_chat = array();
            $set_chat['refresh'] = 20;
            $set_chat['chmes'] = 10;
            $set_chat['carea'] = 0;
            $set_chat['carea_w'] = 20;
            $set_chat['carea_h'] = 2;
            $set_chat['mood'] = 'нейтральное';
            mysql_query("UPDATE `users` SET `set_chat` = '" . mysql_real_escape_string(serialize($set_chat)) . "' WHERE `id` = '$user_id' LIMIT 1");
            echo '<div class="rmenu">Установлены настройки по умолчанию</div>';
        }
        echo '<form action="my_set.php?act=chat" method="post"><div class="menu"><p><h3>Основные настройки</h3>';
        echo '<input type="text" name="refresh" size="2" maxlength="2" value="' . $set_chat['refresh'] . '"/> Обновление (10-99 сек.)<br />';
        echo '<input type="text" name="chmess" size="2" maxlength="2" value="' . $set_chat['chmes'] . '"/> Постов на странице (5-40)<br />';
        echo '</p><p><h3>Ввод сообщения</h3>';
        echo '<input type="text" name="carea_w" size="2" maxlength="2" value="' . $set_chat['carea_w'] . '"/> Ширина формы (10-80)<br />';
        echo '<input type="text" name="carea_h" size="2" maxlength="1" value="' . $set_chat['carea_h'] . '"/> Высота формы (1-9)<br />';
        echo '<input name="carea" type="checkbox" value="1" ' . ($set_chat['carea'] ? 'checked="checked"' : '') . ' />&nbsp;Включить поле ввода<br />';
        echo '</p><p><h3>Ваше настроение</h3>';
        echo '';
        echo 'Выберите настроение:<br/><select name="mood">';
        foreach ($mood as $val) {
            echo '<option' . ($set_chat['mood'] == $val ? ' selected="selected">' : '>') . $val . '</option>';
        }
        echo '</select><br/>';
        if ($rights >= 7)
            echo 'Или укажите свое:<br/><input type="text" name="mood_adm" value="' . (in_array($set_chat['mood'], $mood) ? '' : $set_chat['mood']) . '"/><br/>';
        echo '</p><p><input type="submit" name="submit" value="Сохранить"/></p></div></form>';
        echo '<div class="phdr"><a href="my_set.php?act=chat&amp;reset">Сброс настроек</a></div>';
        echo '<p><a href="../chat">В чат</a><br /><a href="../index.php?act=cab">В кабинет</a></p>';
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Общие настройки                                        //
        ////////////////////////////////////////////////////////////
        $set_user = array();
        $set_user = unserialize($datauser['set_user']);
        echo '<div class="phdr"><b>Общие настройки</b></div>';
        echo '<div class="bmenu"><small>Данные настройки влияют на весь сайт и все его модули</small></div>';
        if (isset ($_POST['submit'])) {
            $set_user['sdvig'] = isset ($_POST['sdvig']) ? intval($_POST['sdvig']) : 0;
            $set_user['avatar'] = isset ($_POST['avatar']) ? 1 : 0;
            $set_user['smileys'] = isset ($_POST['smileys']) ? 1 : 0;
            $set_user['translit'] = isset ($_POST['translit']) ? 1 : 0;
            $set_user['digest'] = isset ($_POST['digest']) ? 1 : 0;
            $set_user['kmess'] = isset ($_POST['kmess']) ? intval($_POST['kmess']) : 10;
            $set_user['quick_go'] = isset ($_POST['quick_go']) ? 1 : 0;
            $set_user['gzip'] = isset ($_POST['gzip']) ? 1 : 0;
            $set_user['online'] = isset ($_POST['online']) ? 1 : 0;
            $set_user['movings'] = isset ($_POST['movings']) ? 1 : 0;
            if ($set_user['sdvig'] < - 12)
                $set_user['sdvig'] = - 12;
            elseif ($set_user['sdvig'] > 12)
                $set_user['sdvig'] = 12;
            if ($set_user['kmess'] < 5)
                $set_user['kmess'] = 5;
            elseif ($set_user['kmess'] > 99)
                $set_user['kmess'] = 99;
            $set_user['skin'] = isset ($_POST['skin']) ? check(trim($_POST['skin'])) : 'default';
            $arr = array();
            $dir = opendir('../theme');
            while ($skindef = readdir($dir)) {
                if (($skindef != '.') && ($skindef != '..') && ($skindef != '.svn'))
                    $arr[] = str_replace('.css', '', $skindef);
            }
            closedir($dir);
            if (!in_array($set_user['skin'], $arr))
                $set_user['skin'] = 'default';
            mysql_query("UPDATE `users` SET `set_user` = '" . mysql_real_escape_string(serialize($set_user)) . "' WHERE `id` = '$user_id' LIMIT 1");
            echo '<div class="rmenu">Настройки сохранены</div>';
        }
        if (isset ($_GET['reset']) || empty ($set_user)) {
            $set_user = array();
            $set_user['avatar'] = 1;
            $set_user['smileys'] = 1;
            $set_user['translit'] = 1;
            $set_user['quick_go'] = 1;
            $set_user['gzip'] = 1;
            $set_user['online'] = 1;
            $set_user['movings'] = 1;
            $set_user['digest'] = 1;
            $set_user['sdvig'] = 0;
            $set_user['kmess'] = 10;
            $set_user['skin'] = 'default';
            mysql_query("UPDATE `users` SET `set_user` = '" . mysql_real_escape_string(serialize($set_user)) . "' WHERE `id` = '$user_id' LIMIT 1");
            echo '<div class="rmenu">Установлены настройки по умолчанию</div>';
        }
        echo '<form action="my_set.php" method="post" ><div class="menu"><p><h3>Настройка часов</h3>';
        echo '<input type="text" name="sdvig" size="2" maxlength="2" value="' . $set_user['sdvig'] . '"/> Сдвиг времени (+-12)<br />';
        echo '<span style="font-weight:bold; background-color:#CCC">' . date("H:i", $realtime + $set_user['sdvig'] * 3600) . '</span> Системное время';
        echo '</p><p><h3>Функции системы</h3>';
        echo '<input name="avatar" type="checkbox" value="1" ' . ($set_user['avatar'] ? 'checked="checked"' : '') . ' />&nbsp;Аватары<br/>';
        echo '<input name="smileys" type="checkbox" value="1" ' . ($set_user['smileys'] ? 'checked="checked"' : '') . ' />&nbsp;Смайлы<br/>';
        echo '<input name="translit" type="checkbox" value="1" ' . ($set_user['translit'] ? 'checked="checked"' : '') . ' />&nbsp;Транслит<br/>';
        echo '<input name="digest" type="checkbox" value="1" ' . ($set_user['digest'] ? 'checked="checked"' : '') . ' />&nbsp;Дайджест';
        echo '</p><p><h3>Внешний вид</h3>';
        echo '<input type="text" name="kmess" size="2" maxlength="2" value="' . $set_user['kmess'] . '"/> Строк на страницу (5-99)<br />';
        echo '<input name="quick_go" type="checkbox" value="1" ' . ($set_user['quick_go'] ? 'checked="checked"' : '') . ' />&nbsp;Меню быстрого перехода<br />';
        echo '<input name="gzip" type="checkbox" value="1" ' . ($set_user['gzip'] ? 'checked="checked"' : '') . ' />&nbsp;Коэффициент сжатия<br />';
        echo '<input name="online" type="checkbox" value="1" ' . ($set_user['online'] ? 'checked="checked"' : '') . ' />&nbsp;Время в Online<br />';
        echo '<input name="movings" type="checkbox" value="1" ' . ($set_user['movings'] ? 'checked="checked"' : '') . ' />&nbsp;Счетчик переходов';
        echo '</p><p><h3>Тема оформления</h3><select name="skin">';
        $dr = opendir('../theme');
        while ($skindef = readdir($dr)) {
            if (($skindef != '.') && ($skindef != '..') && ($skindef != '.svn')) {
                $skindef = str_replace('.css', '', $skindef);
                echo '<option' . ($set_user['skin'] == $skindef ? ' selected="selected">' : '>') . $skindef . '</option>';
            }
        }
        closedir($dir);
        echo '</select>';
        echo '</p><p><input type="submit" name="submit" value="Сохранить"/></p></div></form>';
        echo '<div class="phdr"><a href="my_set.php?reset">Сброс настроек</a></div>';
        echo '<p><a href="../index.php?act=cab">В кабинет</a></p>';
}

require_once ('../incfiles/end.php');

?>