<?php

/*
////////////////////////////////////////////////////////////////////////////////
// JohnCMS v.1.1.0                     30.05.2008                             //
// Официальный сайт сайт проекта:      http://johncms.com                     //
// Дополнительный сайт поддержки:      http://gazenwagen.com                  //
////////////////////////////////////////////////////////////////////////////////
// JohnCMS core team:                                                         //
// Евгений Рябинин aka john77          john77@gazenwagen.com                  //
// Олег Касьянов aka AlkatraZ          alkatraz@gazenwagen.com                //
//                                                                            //
// Плагиат и удаление копирайтов заруганы на ближайших родственников!!!       //
////////////////////////////////////////////////////////////////////////////////
*/

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 9)
    die('Error: restricted access');

switch ($mod) {
    case 'view' :
        ////////////////////////////////////////////////////////////
        // Предварительный просмотр счетчиков                     //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT * FROM `cms_counters` WHERE `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                if (isset ($_GET['go']) && $_GET['go'] == 'on') {
                    mysql_query("UPDATE `cms_counters` SET `switch` = '1' WHERE `id` = '$id'");
                    $req = mysql_query("SELECT * FROM `cms_counters` WHERE `id` = '$id' LIMIT 1");
                }
                elseif (isset ($_GET['go']) && $_GET['go'] == 'off') {
                    mysql_query("UPDATE `cms_counters` SET `switch` = '0' WHERE `id` = '$id'");
                    $req = mysql_query("SELECT * FROM `cms_counters` WHERE `id` = '$id' LIMIT 1");
                }
                $res = mysql_fetch_array($req);
                echo '<div class="phdr"><b>Просмотр счетчика</b></div>';
                echo '<div class="menu">' . ($res['switch'] == 1 ? '<font color="#00AA00">[ON]</font>' : '<font color="#FF0000">[OFF]</font>') . '&nbsp;<b>' . $res['name'] . '</b></div>';
                echo ($res['switch'] == 1 ? '<div class="gmenu">' : '<div class="rmenu">') . '<p><u>Вариант-1</u><br />' . $res['link1'] . '</p>';
                echo '<p><u>Вариант-2</u><br />' . $res['link2'] . '</p>';
                echo '<p><u>Режим отображения</u><br />';
                switch ($res['mode']) {
                    case 2 :
                        echo 'На всех страницах показывается Вариант-1';
                        break;
                    case 3 :
                        echo 'На всех страницах показывается Вариант-2';
                        break;
                    default :
                        echo 'На Главной показывается Вариант-1, на остальных страницах Вариант-2';
                }
                echo '</p></div>';
                echo '<div class="phdr">' . ($res['switch'] == 1 ? '<a href="index.php?act=mod_counters&amp;mod=view&amp;go=off&amp;id=' . $id . '">Выключить</a>' : '<a href="index.php?act=mod_counters&amp;mod=view&amp;go=on&amp;id=' . $id . '">Включить</a>') . ' | <a href="index.php?act=mod_counters&amp;mod=edit&amp;id=' . $id . '">Изм.</a> | <a href="index.php?act=mod_counters&amp;mod=del&amp;id=' . $id . '">Удалить</a></div>';
            }
            else {
                echo display_error('Неверные данные');
            }
        }
        break;

    case 'up' :
        ////////////////////////////////////////////////////////////
        // Перемещение счетчика на одну позицию вверх             //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT `sort` FROM `cms_counters` WHERE `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                $sort = $res['sort'];
                $req = mysql_query("SELECT * FROM `cms_counters` WHERE `sort` < '$sort' ORDER BY `sort` DESC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    mysql_query("UPDATE `cms_counters` SET `sort` = '$sort2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `cms_counters` SET `sort` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=mod_counters');
        break;

    case 'down' :
        ////////////////////////////////////////////////////////////
        // Перемещение счетчика на одну позицию вниз              //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT `sort` FROM `cms_counters` WHERE `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                $sort = $res['sort'];
                $req = mysql_query("SELECT * FROM `cms_counters` WHERE `sort` > '$sort' ORDER BY `sort` ASC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['sort'];
                    mysql_query("UPDATE `cms_counters` SET `sort` = '$sort2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `cms_counters` SET `sort` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=mod_counters');
        break;

    case 'del' :
        ////////////////////////////////////////////////////////////
        // Удаление счетчика                                      //
        ////////////////////////////////////////////////////////////
        if (!$id) {
            echo '<p><b>Ошибка!</b><br/><a href="index.php?act=mod_counters">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;
        }
        $req = mysql_query("SELECT * FROM `cms_counters` WHERE `id` = '$id'");
        if (mysql_num_rows($req) == 1) {
            if (isset ($_POST['submit'])) {
                mysql_query("DELETE FROM `cms_counters` WHERE `id` = '$id' LIMIT 1");
                echo '<p>Счетчик удален!<br/><a href="index.php?act=mod_counters">Продолжить</a></p>';
                require_once ("../incfiles/end.php");
                exit;
            }
            else {
                echo '<form action="index.php?act=mod_counters&amp;mod=del&amp;id=' . $id . '" method="post">';
                echo '<div class="phdr"><b>Удаление счетчика</b></div>';
                $res = mysql_fetch_array($req);
                echo '<div class="menu">Счетчик:<br /><b>' . $res['name'] . '</b></div>';
                echo '<div class="rmenu"><p>Вы действительно хотите его удалить?</p><p><input type="submit" value="Удалить" name="submit" /></p></div>';
                echo '<div class="phdr"><a href="index.php?act=mod_counters">Не удалять (отмена)</a></div></form>';
            }
        }
        else {
            echo '<p><b>Ошибка!</b><br/>Счетчика не существует<br /><a href="index.php?act=mod_counters">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;
        }
        break;

    case 'edit' :
        ////////////////////////////////////////////////////////////
        // Форма добавления счетчика                              //
        ////////////////////////////////////////////////////////////
        if (isset ($_POST['submit'])) {
            // Предварительный просмотр
            $name = isset ($_POST['name']) ? mb_substr(trim($_POST['name']), 0, 25) : '';
            $link1 = isset ($_POST['link1']) ? trim($_POST['link1']) : '';
            $link2 = isset ($_POST['link2']) ? trim($_POST['link2']) : '';
            $mode = isset ($_POST['mode']) ? intval($_POST['mode']) : 1;
            if (empty ($name) || empty ($link1)) {
                echo display_error('Не заполнены обязательные поля<br /><a href="index.php?act=mod_counters&amp;mod=edit' . ($id ? '&amp;id=' . $id : '') . '">Назад</a>');
                require_once ("../incfiles/end.php");
                exit;
            }
            echo '<div class="phdr"><b>Предварительный просмотр</b></div>';
            echo '<div class="menu"><p><u>Название</u><br /><b>' . check($name) . '</b></p>';
            echo '<p><u>Вариант-1</u><br />' . $link1 . '</p>';
            echo '<p><u>Вариант-2</u><br />' . $link2 . '</p></div>';
            echo '<div class="rmenu">Если счетчики отображаются правильно и без ошибок, нажмите кнопку "Запомнить".<br />В противном случае, на своем браузере жмите кнопку "назад" и исправляйте ошибки.</div>';
            echo '<form action="index.php?act=mod_counters&amp;mod=add" method="post">';
            echo '<input type="hidden" value="' . $name . '" name="name" />';
            echo '<input type="hidden" value="' . htmlspecialchars($link1) . '" name="link1" />';
            echo '<input type="hidden" value="' . htmlspecialchars($link2) . '" name="link2" />';
            echo '<input type="hidden" value="' . $mode . '" name="mode" />';
            if ($id)
                echo '<input type="hidden" value="' . $id . '" name="id" />';
            echo '<div class="bmenu"><input type="submit" value="Запомнить" name="submit" /></div>';
            echo '</form>';
        }
        else {
            $name = '';
            $link1 = '';
            $link2 = '';
            $mode = 0;
            if ($id) {
                // запрос к базе, если счетчик редактируется
                $req = mysql_query("SELECT * FROM `cms_counters` WHERE `id` = '" . $id . "' LIMIT 1");
                if (mysql_num_rows($req) > 0) {
                    $res = mysql_fetch_array($req);
                    $name = $res['name'];
                    $link1 = htmlspecialchars($res['link1']);
                    $link2 = htmlspecialchars($res['link2']);
                    $mode = $res['mode'];
                    $switch = 1;
                }
                else {
                    echo '<p><b>Ошибка!</b><br/><a href="counters.php">Назад</a></p>';
                    require_once ("../incfiles/end.php");
                    exit;
                }
            }
            echo '<form action="index.php?act=mod_counters&amp;mod=edit" method="post">';
            echo '<div class="phdr"><b>Добавление счетчика</b></div>';
            echo '<div class="menu"><u>Название</u><br /><input type="text" name="name" value="' . $name . '" /></div>';
            echo '<div class="menu"><u>Вариант-1</u><br /><small>Код для Главной страницы:</small><br /><textarea rows="3" name="link1">' . $link1 . '</textarea></div>';
            echo '<div class="menu"><u>Вариант-2</u><br /><small>Код для остальных страниц:</small><br /><textarea rows="3" name="link2">' . $link2 . '</textarea></div>';
            echo '<div class="menu"><u>Режим отображения</u><p>' . '<input type="radio" value="1" ' . ($mode == 0 || $mode == 1 ? 'checked="checked" ' : '') . 'name="mode" />&nbsp;По умолчанию<br />' . '<div class="sub">На Главной показывается Вариант-1, на остальных страницах Вариант-2<br />Если поле "Вариант-2" не заполнено, то чсетчик будет отображаться только на Главной странице.</div></p><p>' . '<input type="radio" value="2" ' . ($mode == 2 ? 'checked="checked" ' : '') . 'name="mode" />&nbsp;Вариант-1<br />' . '<input type="radio" value="3" ' . ($mode == 3 ? 'checked="checked" ' : '') . 'name="mode" />&nbsp;Вариант-2</p></div>';
            echo '<div class="rmenu"><small>ВНИМАНИЕ!<br />Следите за правильностью введенного кода. Он должен соответствовать стандартам XML.<br />Если после нажатия кнопки "Просмотр" возникнут ошибки XHTML, то в своем браузере нажмите кнопку "Назад", вернитесь в данную форму и откорректируйте ошибки.</small></div>';
            if ($id)
                echo '<input type="hidden" value="' . $id . '" name="id" />';
            echo '<div class="bmenu"><input type="submit" value="Просмотр" name="submit" /></div>';
            echo '</form>';
        }
        break;

    case 'add' :
        ////////////////////////////////////////////////////////////
        // Запись счетчика в базу                                 //
        ////////////////////////////////////////////////////////////
        $name = isset ($_POST['name']) ? mb_substr($_POST['name'], 0, 25) : '';
        $link1 = isset ($_POST['link1']) ? $_POST['link1'] : '';
        $link2 = isset ($_POST['link2']) ? $_POST['link2'] : '';
        $mode = isset ($_POST['mode']) ? intval($_POST['mode']) : 1;
        if (empty ($name) || empty ($link1)) {
            echo '<p><b>Ошибка!</b><br/>Не заполнены обязательные поля<br /><a href="index.php?act=mod_counters&amp;mod=edit' . ($id ? '&amp;id=' . $id : '') . '">Назад</a></p>';
            require_once ("../incfiles/end.php");
            exit;
        }
        if ($id) {
            // Режим редактирования
            $req = mysql_query("SELECT * FROM `cms_counters` WHERE `id` = '$id'");
            if (mysql_num_rows($req) != 1) {
                echo display_error('Неверные данные');
                require_once ("../incfiles/end.php");
                exit;
            }
            mysql_query("UPDATE `cms_counters` SET
			`name` = '" . check($name) . "',
			`link1` = '" . mysql_real_escape_string($link1) . "',
			`link2` = '" . mysql_real_escape_string($link2) . "',
			`mode` = '" . $mode . "'
			WHERE `id` = '" . $id . "'");
        }
        else {
            // Получаем значение сортировки
            $req = mysql_query("SELECT `sort` FROM `cms_counters` ORDER BY `sort` DESC LIMIT 1");
            if (mysql_num_rows($req) > 0) {
                $res = mysql_fetch_array($req);
                $sort = $res['sort'] + 1;
            }
            else {
                $sort = 1;
            }
            // Режим добавления
            mysql_query("INSERT INTO `cms_counters` SET
			`name` = '" . check($name) . "',
			`sort` = '$sort',
			`link1` = '" . mysql_real_escape_string($link1) . "',
			`link2` = '" . mysql_real_escape_string($link2) . "',
			`mode` = '$mode'");
        }
        echo '<div class="gmenu"><p>Счетчик успешно ' . ($id ? 'изменен' : 'добавлен') . '</p></div>';
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Вывод списка счетчиков                                 //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Управление счетчиками</div>';
        $req = mysql_query("SELECT * FROM `cms_counters` ORDER BY `sort` ASC");
        if (mysql_num_rows($req)) {
            while ($res = mysql_fetch_assoc($req)) {
                echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                echo '<img src="../images/' . ($res['switch'] == 1 ? 'green' : 'red') . '.gif" width="16" height="16" class="left"/>&nbsp;';
                echo '<a href="index.php?act=mod_counters&amp;mod=view&amp;id=' . $res['id'] . '"><b>' . $res['name'] . '</b></a><br />';
                echo '<div class="sub"><a href="index.php?act=mod_counters&amp;mod=up&amp;id=' . $res['id'] . '">Вверх</a> | ';
                echo '<a href="index.php?act=mod_counters&amp;mod=down&amp;id=' . $res['id'] . '">Вниз</a> | ';
                echo '<a href="index.php?act=mod_counters&amp;mod=edit&amp;id=' . $res['id'] . '">Изм.</a> | ';
                echo '<a href="index.php?act=mod_counters&amp;mod=del&amp;id=' . $res['id'] . '">Удалить</a></div></div>';
                ++$i;
            }
        }
        echo '<div class="phdr"><a href="index.php?act=mod_counters&amp;mod=edit">Добавить</a></div>';
}

echo '<p>' . ($mod ? '<a href="index.php?act=mod_counters">К счетчикам</a><br />' : '') . '<a href="index.php">Админ панель</a></p>';

?>