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

defined('_IN_JOHNADM') or die('Error: restricted access');

if ($rights < 7) {
    echo display_error('Доступ закрыт');
    require_once ('../incfiles/end.php');
    exit;
}

// Задаем пользовательские настройки форума
$set_forum = unserialize($datauser['set_forum']);
if (!isset ($set_forum) || empty ($set_forum))
    $set_forum = array('farea' => 0, 'upfp' => 0, 'farea_w' => 20, 'farea_h' => 4, 'postclip' => 1, 'postcut' => 2);

switch ($mod) {
    case 'del' :
        if (!$id) {
            echo display_error('Неверные данные');
            require_once ('../incfiles/end.php');
            exit;
        }
        $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND (`type` = 'f' OR `type` = 'r') LIMIT 1");
        if (mysql_num_rows($req)) {
            $res = mysql_fetch_assoc($req);
            echo '<div class="phdr"><b>Удаляем ' . ($res['type'] == 'r' ? 'раздел' : 'категорию') . ':</b> ' . $res['text'] . '</div>';
            // Проверяем, есть ли подчиненная информация
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '$id' AND (`type` = 'f' OR `type` = 'r' OR `type` = 't')"), 0);
            if ($total) {
                if ($res['type'] == 'f') {
                    ////////////////////////////////////////////////////////////
                    // Удаление категории с подчиненными данными              //
                    ////////////////////////////////////////////////////////////
                    if (isset ($_POST['submit'])) {
                        $category = isset ($_POST['category']) ? intval($_POST['category']) : 0;
                        if (!$category || $category == $id) {
                            echo display_error('Неверные данные');
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        $check = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$category' AND `type` = 'f' LIMIT 1"), 0);
                        if (!$check) {
                            echo display_error('Неверный выбор категории');
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Вычисляем правила сортировки и перемещаем разделы
                        $sort = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `refid` = '$category' AND `type` ='r' ORDER BY `realid` DESC"));
                        $sortnum = !empty ($sort['realid']) && $sort['realid'] > 0 ? $sort['realid'] + 1 : 1;
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'r'");
                        while ($res_c = mysql_fetch_assoc($req_c)) {
                            mysql_query("UPDATE `forum` SET `refid` = '" . $category . "', `realid` = '$sortnum' WHERE `id` = '" . $res_c['id'] . "' LIMIT 1");
                            ++$sortnum;
                        }
                        // Перемещаем файлы в выбранную категорию
                        mysql_query("UPDATE `cms_forum_files` SET `cat` = '" . $category . "' WHERE `cat` = '" . $res['refid'] . "'");
                        mysql_query("DELETE FROM `forum` WHERE `id` = '$id' LIMIT 1");
                        echo '<div class="rmenu"><p><h3>Категория удалена</h3>';
                        echo 'Подчиненные разделы и файлы перемещены в <a href="../forum/index.php?id=' . $category . '">выбранную категорию</a>.';
                        echo '</p></div>';
                    }
                    else {
                        echo '<form action="index.php?act=mod_forum&amp;mod=del&amp;id=' . $id . '" method="POST"><div class="rmenu"><p>' .
                        '<h3>ВНИМАНИЕ!</h3>Есть подчиненные разделы.<br />Их необходимо переместить в другую категорию</p>' .
                        '<p><h3>Выберите категорию</h3><select name="category" size="1">';
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$id' ORDER BY `realid` ASC");
                        while ($res_c = mysql_fetch_assoc($req_c)) echo '<option value="' . $res_c['id'] . '">' . $res_c['text'] . '</option>';
                        echo '</select><br /><small>Все разделы, темы и файлы будут перемещены в выбранную категорию.<br />' .
                        'Старая категория будет удалена</small></p><p><input type="submit" name="submit" value="Переместить" /></p></div>';
                        if ($rights == 9) {
                            // Для супервайзоров запрос на полное удаление
                            echo '<div class="rmenu"><p><h3>Полное удаление</h3>' .
                            'Если хотите удалить всю информацию, вначале удалите <a href="index.php?act=mod_forum&amp;mod=cat&amp;id=' . $id . '">подчиненные разделы</a>.</p>';
                            echo '</div></form>';
                        }
                    }
                }
                else {
                    ////////////////////////////////////////////////////////////
                    // Удаление раздела с подчиненными данными                //
                    ////////////////////////////////////////////////////////////
                    if (isset ($_POST['submit'])) {
                        // Предварительные проверки
                        $subcat = isset ($_POST['subcat']) ? intval($_POST['subcat']) : 0;
                        if (!$subcat || $subcat == $id) {
                            echo display_error('Неверные данные');
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        $check = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$subcat' AND `type` = 'r' LIMIT 1"), 0);
                        if (!$check) {
                            echo display_error('Неверный выбор раздела');
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        mysql_query("UPDATE `forum` SET `refid` = '$subcat' WHERE `refid` = '$id'");
                        mysql_query("UPDATE `cms_forum_files` SET `subcat` = '$subcat' WHERE `subcat` = '$id'");
                        mysql_query("DELETE FROM `forum` WHERE `id` = '$id' LIMIT 1");
                        echo '<div class="rmenu"><p><h3>Раздел удален</h3>';
                        echo 'Подчиненные темы перемещены в <a href="../forum/index.php?id=' . $subcat . '">выбранный раздел</a>.';
                        echo '</p></div>';
                    }
                    elseif (isset ($_POST['delete'])) {
                        if ($rights != 9) {
                            echo display_error('Доступ закрыт');
                            require_once ('../incfiles/end.php');
                            exit;
                        }
                        // Удаляем файлы
                        $req_f = mysql_query("SELECT * FROM `cms_forum_files` WHERE `subcat` = '$id'");
                        while ($res_f = mysql_fetch_assoc($req_f)) {
                            unlink('../forum/files/' . $res_f['filename']);
                        }
                        mysql_query("DELETE FROM `cms_forum_files` WHERE `subcat` = '$id'");
                        // Удаляем посты, голосования и метки прочтений
                        $req_t = mysql_query("SELECT `id` FROM `forum` WHERE `refid` = '$id' AND `type` = 't'");
                        while ($res_t = mysql_fetch_assoc($req_t)) {
                            mysql_query("DELETE FROM `forum` WHERE `refid` = '" . $res_t['id'] . "'");
                            mysql_query("DELETE FROM `forum_vote` WHERE `topic` = '" . $res_t['id'] . "'");
                            mysql_query("DELETE FROM `forum_vote_us` WHERE `topic` = '" . $res_t['id'] . "'");
                            mysql_query("DELETE FROM `cms_forum_rdm` WHERE `topic_id` = '" . $res_t['id'] . "'");
                        }
                        // Удаляем темы
                        mysql_query("DELETE FROM `forum` WHERE `refid` = '$id'");
                        // Удаляем раздел
                        mysql_query("DELETE FROM `forum` WHERE `id` = '$id' LIMIT 1");
                        // Оптимизируем таблицы
                        mysql_query("OPTIMIZE TABLE `cms_forum_files` , `cms_forum_rdm` , `forum` , `forum_vote` , `forum_vote_us`");
                        echo '<div class="rmenu"><p>Раздел вместе с темами и файлами, удален<br /><a href="index.php?act=mod_forum&amp;mod=cat&amp;id=' . $res['refid'] .
                        '">В категорию</a></p></div>';
                    }
                    else {
                        echo '<form action="index.php?act=mod_forum&amp;mod=del&amp;id=' . $id . '" method="POST"><div class="rmenu"><p>' .
                        '<h3>ВНИМАНИЕ!</h3>В разделе есть темы.<br />Их необходимо переместить в другой раздел</p>' . '<p><h3>Выберите раздел</h3>';
                        $cat = isset ($_GET['cat']) ? abs(intval($_GET['cat'])) : 0;
                        $ref = $cat ? $cat : $res['refid'];
                        $req_r = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$ref' AND `id` != '$id' AND `type` = 'r' ORDER BY `realid` ASC");
                        while ($res_r = mysql_fetch_assoc($req_r)) {
                            echo '<input type="radio" name="subcat" value="' . $res_r['id'] . '" />&nbsp;' . $res_r['text'] . '<br />';
                        }
                        echo '</p><p><h3>Другая категория</h3><ul>';
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$ref' ORDER BY `realid` ASC");
                        while ($res_c = mysql_fetch_assoc($req_c)) {
                            echo '<li><a href="index.php?act=mod_forum&amp;mod=del&amp;id=' . $id . '&amp;cat=' . $res_c['id'] . '">' . $res_c['text'] . '</a></li>';
                        }
                        echo '</ul><small>Все темы и файлы будут перемещены в выбранный раздел.<br />' .
                        'Старый раздел будет удален</small></p><p><input type="submit" name="submit" value="Переместить" /></p></div>';
                        if ($rights == 9) {
                            // Для супервайзоров запрос на полное удаление
                            echo '<div class="rmenu"><p><h3>Полное удаление</h3>ВНИМАНИЕ! Будет удалена вся информация раздела.';
                            echo '</p><p><input type="submit" name="delete" value="Удалить" /></p></div></form>';
                        }
                    }
                }
            }
            else {
                ////////////////////////////////////////////////////////////
                // Удаление пустого раздела, или категории                //
                ////////////////////////////////////////////////////////////
                if (isset ($_POST['submit'])) {
                    mysql_query("DELETE FROM `forum` WHERE `id` = '$id' LIMIT 1");
                    echo '<div class="rmenu"><p>' . ($res['type'] == 'r' ? 'Раздел удален' : 'Категория удалена') . '</p></div>';
                }
                else {
                    echo '<div class="rmenu"><p>Вы действительно хотите удалить ' . ($res['type'] == 'r' ? 'раздел' : 'категорию') . '?';
                    echo '</p><p><form action="index.php?act=mod_forum&amp;mod=del&amp;id=' . $id . '" method="POST"><input type="submit" name="submit" value="Удалить" /></form>';
                    echo '</p></div>';
                }
            }
            echo '<div class="phdr"><a href="index.php?act=mod_forum&amp;mod=cat">Назад</a></div>';
        }
        else {
            header('Location: index.php?act=mod_forum&mod=cat');
        }
        break;

    case 'add' :
        ////////////////////////////////////////////////////////////
        // Добавление категории                                   //
        ////////////////////////////////////////////////////////////
        if ($id) {
            // Проверяем наличие категории
            $req = mysql_query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 'f' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_array($req);
                $cat_name = $res['text'];
            }
            else {
                header('Location: index.php?act=mod_forum&mod=cat');
                exit;
            }
        }
        if (isset ($_POST['submit'])) {
            // Принимаем данные
            $name = isset ($_POST['name']) ? check($_POST['name']) : '';
            $desc = isset ($_POST['desc']) ? check($_POST['desc']) : '';
            // Проверяем на ошибки
            $error = array();
            if (!$name)
                $error[] = 'Вы не ввели название';
            if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30))
                $error[] = 'Длина названия должна быть не менее 2-х и не более 30 символов';
            if ($desc && mb_strlen($desc) < 2)
                $error[] = 'Длина описания должна быть не менее 2-х символов';
            if (!$error) {
                // Добавляем в базу категорию
                $req = mysql_query("SELECT `realid` FROM `forum` WHERE " . ($id ? "`refid` = '$id' AND `type` = 'r'" : "`type` = 'f'") . " ORDER BY `realid` DESC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $sort = $res['realid'] + 1;
                }
                else {
                    $sort = 1;
                }
                mysql_query("INSERT INTO `forum` SET
                `refid` = '" . ($id ? $id : '') . "',
                `type` = '" . ($id ? 'r' : 'f') .
                "',
                `text` = '$name',
                `soft` = '$desc',
                `realid` = '$sort'");
                header('Location: index.php?act=mod_forum&mod=cat' . ($id ? '&id=' . $id : ''));
            }
            else {
                // Выводим сообщение об ошибках
                echo display_error($error);
            }
        }
        else {
            // Форма ввода
            echo '<div class="phdr"><b>Добавить ' . ($id ? 'раздел' : 'категорию') . '</b></div>';
            echo '<div class="bmenu">В категорию: ' . $cat_name . '</div>';
            echo '<form action="index.php?act=mod_forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post"><div class="gmenu"><p>';
            echo '<b>Название:</b><br /><input type="text" name="name" /><br /><small>Мин. 2, макс. 30 символов</small><br />';
            echo '<b>Описание:</b><br /><textarea name="desc" cols="24" rows="4"></textarea><br /><small>Мин. 2, макс. 500 симолов<br />Описание не обязательно</small><br />';
            echo '</p><p><input type="submit" value="Добавить" name="submit" />';
            echo '</p></div></form>';
            echo '<div class="phdr"><a href="index.php?act=mod_forum&amp;mod=cat' . ($id ? '&amp;id=' . $id : '') . '">Назад</a></div>';
        }
        break;

    case 'edit' :
        ////////////////////////////////////////////////////////////
        // Редактирование выбранной категории, или раздела        //
        ////////////////////////////////////////////////////////////
        if (!$id) {
            header('Location: index.php?act=mod_forum&mod=cat');
            exit;
        }
        $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' LIMIT 1");
        if (mysql_num_rows($req)) {
            $res = mysql_fetch_assoc($req);
            if ($res['type'] == 'f' || $res['type'] == 'r') {
                if (isset ($_POST['submit'])) {
                    // Принимаем данные
                    $name = isset ($_POST['name']) ? check($_POST['name']) : '';
                    $desc = isset ($_POST['desc']) ? check($_POST['desc']) : '';
                    $category = isset ($_POST['category']) ? intval($_POST['category']) : 0;
                    // проверяем на ошибки
                    $error = array();
                    if ($res['type'] == 'r' && !$category)
                        $error[] = 'Не выбрана категория';
                    elseif ($res['type'] == 'r' && !mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$category' AND `type` = 'f' LIMIT 1"), 0))
                        $error[] = 'Неправильный выбор категории';
                    if (!$name)
                        $error[] = 'Вы не ввели название';
                    if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30))
                        $error[] = 'Длина названия должна быть не менее 2-х и не более 30 символов';
                    if ($desc && mb_strlen($desc) < 2)
                        $error[] = 'Длина описания должна быть не менее 2-х символов';
                    if (!$error) {
                        // Записываем в базу
                        mysql_query("UPDATE `forum` SET
                            `text` = '$name',
                            `soft` = '$desc'
                            WHERE `id` = '$id' LIMIT 1");
                        if ($res['type'] == 'r' && $category != $res['refid']) {
                            // Вычисляем сортировку
                            $req_s = mysql_query("SELECT `realid` FROM `forum` WHERE `refid` = '$category' AND `type` = 'r' ORDER BY `realid` DESC LIMIT 1");
                            $res_s = mysql_fetch_assoc($req_s);
                            $sort = $res_s['realid'] + 1;
                            // Меняем категорию
                            mysql_query("UPDATE `forum` SET `refid` = '$category', `realid` = '$sort' WHERE `id` = '$id' LIMIT 1");
                            // Меняем категорию для прикрепленных файлов
                            mysql_query("UPDATE `cms_forum_files` SET `cat` = '$category' WHERE `cat` = '" . $res['refid'] . "'");
                        }
                        header('Location: index.php?act=mod_forum&mod=cat' . ($res['type'] == 'r' ? '&id=' . $res['refid'] : ''));
                    }
                    else {
                        // Выводим сообщение об ошибках
                        echo display_error($error);
                    }
                }
                else {
                    // Форма ввода
                    echo '<div class="phdr"><b>Редактируем ' . ($res['type'] == 'r' ? 'раздел' : 'категорию') . '</b></div>';
                    echo '<form action="index.php?act=mod_forum&amp;mod=edit&amp;id=' . $id . '" method="post"><div class="gmenu"><p>';
                    echo '<b>Название:</b><br /><input type="text" name="name" value="' . $res['text'] . '"/><br /><small>Мин. 2, макс. 30 символов</small><br />';
                    echo '<b>Описание:</b><br /><textarea name="desc" cols="24" rows="4">' . str_replace('<br />', "\r\n", $res['soft']) .
                    '</textarea><br /><small>Мин. 2, макс. 500 симолов<br />Описание не обязательно</small><br />';
                    if ($res['type'] == 'r') {
                        echo '</p><p><b>Категория:</b><br /><select name="category" size="1">';
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");
                        while ($res_c = mysql_fetch_assoc($req_c)) {
                            echo '<option value="' . $res_c['id'] . '"' . ($res_c['id'] == $res['refid'] ? ' selected="selected"' : '') . '>' . $res_c['text'] . '</option>';
                        }
                        echo '</select>';
                    }
                    echo '</p><p><input type="submit" value="Сохранить" name="submit" />';
                    echo '</p></div></form>';
                    echo '<div class="phdr"><a href="index.php?act=mod_forum&amp;mod=cat' . ($res['type'] == 'r' ? '&amp;id=' . $res['refid'] : '') . '">Назад</a></div>';
                }
            }
            else {
                header('Location: index.php?act=mod_forum&mod=cat');
            }
        }
        else {
            header('Location: index.php?act=mod_forum&mod=cat');
        }
        break;

    case 'up' :
        ////////////////////////////////////////////////////////////
        // Перемещение на одну позицию вверх                      //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res1 = mysql_fetch_assoc($req);
                $sort = $res1['realid'];
                $req = mysql_query("SELECT * FROM `forum` WHERE `type` = '" . ($res1['type'] == 'f' ? 'f' : 'r') . "' AND `realid` < '$sort' ORDER BY `realid` DESC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['realid'];
                    mysql_query("UPDATE `forum` SET `realid` = '$sort2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `forum` SET `realid` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=mod_forum&mod=cat' . ($res1['type'] == 'r' ? '&id=' . $res1['refid'] : ''));
        break;

    case 'down' :
        ////////////////////////////////////////////////////////////
        // Перемещение на одну позицию вниз                       //
        ////////////////////////////////////////////////////////////
        if ($id) {
            $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' LIMIT 1");
            if (mysql_num_rows($req)) {
                $res1 = mysql_fetch_assoc($req);
                $sort = $res1['realid'];
                $req = mysql_query("SELECT * FROM `forum` WHERE `type` = '" . ($res1['type'] == 'f' ? 'f' : 'r') . "' AND `realid` > '$sort' ORDER BY `realid` ASC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $id2 = $res['id'];
                    $sort2 = $res['realid'];
                    mysql_query("UPDATE `forum` SET `realid` = '$sort2' WHERE `id` = '$id'");
                    mysql_query("UPDATE `forum` SET `realid` = '$sort' WHERE `id` = '$id2'");
                }
            }
        }
        header('Location: index.php?act=mod_forum&mod=cat' . ($res1['type'] == 'r' ? '&id=' . $res1['refid'] : ''));
        break;

    case 'cat' :
        echo '<div class="phdr"><a href="index.php?act=mod_forum"><b>Управление Форумом</b></a> | Структура форума</div>';
        if ($id) {
            ////////////////////////////////////////////////////////////
            // Управление разделами                                   //
            ////////////////////////////////////////////////////////////
            $req = mysql_query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 'f' LIMIT 1");
            $res = mysql_fetch_assoc($req);
            echo '<div class="bmenu"><b>' . $res['text'] . '</b> | Список разделов</div>';
            $req = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'r' ORDER BY `realid` ASC");
            if (mysql_num_rows($req)) {
                while ($res = mysql_fetch_assoc($req)) {
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    echo '<b>' . $res['text'] . '</b>';
                    echo '&nbsp;<a href="../forum/index.php?id=' . $res['id'] . '">&gt;&gt;</a>';
                    if (!empty ($res['soft']))
                        echo '<br /><span class="gray"><small>' . $res['soft'] . '</small></span><br />';
                    echo '<div class="sub"><a href="index.php?act=mod_forum&amp;mod=up&amp;id=' . $res['id'] . '">Вверх</a> | <a href="index.php?act=mod_forum&amp;mod=down&amp;id=' . $res['id'] .
                    '">Вниз</a> | <a href="index.php?act=mod_forum&amp;mod=edit&amp;id=' . $res['id'] . '">Изм.</a> | <a href="index.php?act=mod_forum&amp;mod=del&amp;id=' . $res['id'] . '">Удалить</a></div></div>';
                    ++$i;
                }
            }
            else {
                echo '<div class="menu"><p>Список разделов пуст</p></div>';
            }
        }
        else {
            ////////////////////////////////////////////////////////////
            // Управление категориями                                 //
            ////////////////////////////////////////////////////////////
            echo '<div class="bmenu">Список категорий</div>';
            $req = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");
            while ($res = mysql_fetch_assoc($req)) {
                echo ($i % 2) ? '<div class="list2">' : '<div class="list1">';
                echo '<a href="index.php?act=mod_forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['text'] . '</b></a> ';
                echo '(' . mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'r' AND `refid` = '" . $res['id'] . "'"), 0) . ')';
                echo '&nbsp;<a href="../forum/index.php?id=' . $res['id'] . '">&gt;&gt;</a>';
                if (!empty ($res['soft']))
                    echo '<br /><span class="gray"><small>' . $res['soft'] . '</small></span><br />';
                echo '<div class="sub"><a href="index.php?act=mod_forum&amp;mod=up&amp;id=' . $res['id'] . '">Вверх</a> | <a href="index.php?act=mod_forum&amp;mod=down&amp;id=' . $res['id'] .
                '">Вниз</a> | <a href="index.php?act=mod_forum&amp;mod=edit&amp;id=' . $res['id'] . '">Изм.</a> | <a href="index.php?act=mod_forum&amp;mod=del&amp;id=' . $res['id'] . '">Удалить</a></div></div>';
                ++$i;
            }
        }
        echo '<div class="gmenu"><form action="index.php?act=mod_forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post"><input type="submit" value="Добавить" /></form></div>';
        echo '<div class="phdr">' . ($mod == 'cat' && $id ? '<a href="index.php?act=mod_forum&amp;mod=cat">К списку категорий</a>' : '<a href="index.php?act=mod_forum">Управление Форумом</a>') . '</div>';
        break;

    case 'htopics' :
        ////////////////////////////////////////////////////////////
        // Скрытые темы форума                                    //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><a href="index.php?act=mod_forum"><b>Управление Форумом</b></a> | Скрытые темы</div>';
        $sort = '';
        $link = '';
        if (isset ($_GET['usort'])) {
            $sort = " AND `forum`.`user_id` = '" . abs(intval($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs(intval($_GET['usort']));
            echo '<div class="bmenu">Фильтр по автору [<a href="index.php?act=mod_forum&amp;mod=htopics">отменить</a>]</div>';
        }
        if (isset ($_GET['rsort'])) {
            $sort = " AND `forum`.`refid` = '" . abs(intval($_GET['rsort'])) . "'";
            $link = '&amp;rsort=' . abs(intval($_GET['rsort']));
            echo '<div class="bmenu">Фильтр по разделу [<a href="index.php?act=mod_forum&amp;mod=htopics">отменить</a>]</div>';
        }
        if (isset ($_POST['deltopic'])) {
            if ($rights != 9) {
                echo display_error('Доступ закрыт');
                require_once ('../incfiles/end.php');
                exit;
            }
            $req = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort");
            while ($res = mysql_fetch_assoc($req)) {
                $req_f = mysql_query("SELECT * FROM `cms_forum_files` WHERE `topic` = '" . $res['id'] . "'");
                if (mysql_num_rows($req_f)) {
                    // Удаляем файлы
                    while ($res_f = mysql_fetch_assoc($req_f)) {
                        unlink('../forum/files/' . $res_f['filename']);
                    }
                    mysql_query("DELETE FROM `cms_forum_files` WHERE `topic` = '" . $res['id'] . "'");
                }
                // Удаляем посты
                mysql_query("DELETE FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['id'] . "'");
            }
            // Удаляем темы
            $req = mysql_query("DELETE FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort");
            header('Location: index.php?act=mod_forum&mod=htopics');
        }
        else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort"), 0);
            $req = mysql_query(
            "SELECT `forum`.*, `forum`.`id` AS `fid`, `forum`.`user_id` AS `id`, `forum`.`from` AS `name`, `forum`.`soft` AS `browser`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`
            FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
            WHERE `forum`.`type` = 't' AND `forum`.`close` = '1' $sort ORDER BY `forum`.`id` DESC LIMIT $start, $kmess"
            );
            if (mysql_num_rows($req)) {
                while ($res = mysql_fetch_assoc($req)) {
                    $subcat = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "' LIMIT 1"));
                    $cat = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $subcat['refid'] . "' LIMIT 1"));
                    $ttime = '<span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>';
                    $text = '<a href="../forum/index.php?id=' . $res['fid'] . '"><b>' . $res['text'] . '</b></a>';
                    $text .= '<br /><small><a href="../forum/index.php?id=' . $cat['id'] . '">' . $cat['text'] . '</a> / <a href="../forum/index.php?id=' . $subcat['id'] . '">' . $subcat['text'] . '</a></small>';
                    $subtext = '<span class="gray"><u>Фильтровать</u>:</span> ';
                    $subtext .= '<a href="index.php?act=mod_forum&amp;mod=htopics&amp;rsort=' . $res['refid'] . '">по разделу</a> | ';
                    $subtext .= '<a href="index.php?act=mod_forum&amp;mod=htopics&amp;usort=' . $res['user_id'] . '">по автору</a>';
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    echo show_user($res, 0, 0, $ttime, $text, $subtext);
                    echo '</div>';
                    ++$i;
                }
                if ($rights == 9)
                    echo '<form action="index.php?act=mod_forum&amp;mod=htopics' . $link . '" method="POST"><div class="rmenu"><input type="submit" name="deltopic" value="Удалить все" /></div></form>';
            }
            else {
                echo '<div class="menu"><p>Скрытых тем нет</p></div>';
            }
            echo '<div class="phdr">Всего: ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . pagenav('index.php?act=mod_forum&amp;mod=htopics&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="index.php?act=mod_forum&amp;mod=htopics" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
            }
        }
        break;

    case 'hposts' :
        ////////////////////////////////////////////////////////////
        // Скрытые посты форума                                   //
        ////////////////////////////////////////////////////////////
        echo '<div class="phdr"><a href="index.php?act=mod_forum"><b>Управление Форумом</b></a> | Скрытые посты</div>';
        $sort = '';
        $link = '';
        if (isset ($_GET['tsort'])) {
            $sort = " AND `forum`.`refid` = '" . abs(intval($_GET['tsort'])) . "'";
            $link = '&amp;tsort=' . abs(intval($_GET['tsort']));
            echo '<div class="bmenu">Фильтр по теме [<a href="index.php?act=mod_forum&amp;mod=hposts">отменить</a>]</div>';
        }
        elseif (isset ($_GET['usort'])) {
            $sort = " AND `forum`.`user_id` = '" . abs(intval($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs(intval($_GET['usort']));
            echo '<div class="bmenu">Фильтр по автору [<a href="index.php?act=mod_forum&amp;mod=hposts">отменить</a>]</div>';
        }
        if (isset ($_POST['delpost'])) {
            if ($rights != 9) {
                echo display_error('Доступ закрыт');
                require_once ('../incfiles/end.php');
                exit;
            }
            $req = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort");
            while ($res = mysql_fetch_assoc($req)) {
                $req_f = mysql_query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "' LIMIT 1");
                if (mysql_num_rows($req_f)) {
                    $res_f = mysql_fetch_assoc($req_f);
                    // Удаляем файлы
                    unlink('../forum/files/' . $res_f['filename']);
                    mysql_query("DELETE FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "' LIMIT 1");
                }
            }
            // Удаляем посты
            mysql_query("DELETE FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort");
            header('Location: index.php?act=mod_forum&mod=hposts');
        }
        else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort"), 0);
            $req = mysql_query(
            "SELECT `forum`.*, `forum`.`id` AS `fid`, `forum`.`user_id` AS `id`, `forum`.`from` AS `name`, `forum`.`soft` AS `browser`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`
            FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
            WHERE `forum`.`type` = 'm' AND `forum`.`close` = '1' $sort ORDER BY `forum`.`id` DESC LIMIT $start, $kmess"
            );
            if (mysql_num_rows($req)) {
                while ($res = mysql_fetch_assoc($req)) {
                    $res['ip'] = ip2long($res['ip']);
                    $posttime = ' <span class="gray">(' . date("d.m.Y / H:i", $res['time'] + $set_user['sdvig'] * 3600) . ')</span>';
                    $page = ceil(mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '" . $res['fid'] . "'"), 0) / $kmess);
                    $text = mb_substr($res['text'], 0, 500);
                    $text = checkout($text, 1, 0);
                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $theme = mysql_fetch_assoc(mysql_query("SELECT `id`, `text` FROM `forum` WHERE `id` = '" . $res['refid'] . "' LIMIT 1"));
                    $text = '<b>' . $theme['text'] . '</b> <a href="../forum/index.php?id=' . $theme['id'] . '&amp;page=' . $page . '">&gt;&gt;</a><br />' . $text;
                    $subtext = '<span class="gray"><u>Фильтровать</u>:</span> ';
                    $subtext .= '<a href="index.php?act=mod_forum&amp;mod=hposts&amp;tsort=' . $theme['id'] . '">по теме</a> | ';
                    $subtext .= '<a href="index.php?act=mod_forum&amp;mod=hposts&amp;usort=' . $res['user_id'] . '">по автору</a>';
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    echo show_user($res, 0, 2, $posttime, $text, $subtext);
                    echo '</div>';
                    ++$i;
                }
                if ($rights == 9)
                    echo '<form action="index.php?act=mod_forum&amp;mod=hposts' . $link . '" method="POST"><div class="rmenu"><input type="submit" name="delpost" value="Удалить все" /></div></form>';
            }
            else {
                echo '<div class="menu"><p>Скрытых постов нет</p></div>';
            }
            echo '<div class="phdr">Всего: ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<p>' . pagenav('index.php?act=mod_forum&amp;mod=hposts&amp;', $start, $total, $kmess) . '</p>';
                echo '<p><form action="index.php?act=mod_forum&amp;mod=hposts" method="post"><input type="text" name="page" size="2"/><input type="submit" value="К странице &gt;&gt;"/></form></p>';
            }
        }
        break;

    case 'moders' :
        if (isset ($_POST['submit'])) {
            if (!$id) {
                echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
                require_once ("../incfiles/end.php");
                exit;
            }
            if (isset ($_POST['moder'])) {
                $q = mysql_query("select * from `forum` where type='a' and refid='" . $id . "'");
                while ($q1 = mysql_fetch_array($q)) {
                    if (!in_array($q1['from'], $_POST['moder'])) {
                        mysql_query("delete from `forum` where `id`='" . $q1['id'] . "'");
                    }
                }
                foreach ($_POST['moder'] as $v) {
                    $q2 = mysql_query("select * from `forum` where type='a' and `from`='" . $v . "' and refid='" . $id . "'");
                    $q3 = mysql_num_rows($q2);
                    if ($q3 == 0) {
                        mysql_query("INSERT INTO `forum` SET
						`refid`='" . $id . "',
						`type`='a',
						`from`='" . check($v) . "'");
                    }
                }
            }
            else {
                $q = mysql_query("select * from `forum` where type='a' and refid='" . $id . "'");
                while ($q1 = mysql_fetch_array($q)) {
                    mysql_query("delete from `forum` where `id`='" . $q1['id'] . "'");
                }
            }
            header("Location: index.php?act=mod_forum&mod=moders&id=$id");
        }
        else {
            if (!empty ($_GET['id'])) {
                $typ = mysql_query("select * from `forum` where id='" . $id . "';");
                $ms = mysql_fetch_array($typ);
                if ($ms['type'] != "f") {
                    echo "Ошибка!<br/><a href='forum.php?'>В управление форумом</a><br/>";
                    require_once ("../incfiles/end.php");
                    exit;
                }
                echo '<div class="phdr"><b>Назначение модеров</b> в категорию '.$ms['text'].'</div>';
                echo '<form action="index.php?act=mod_forum&amp;mod=moders&amp;id=' . $id . '" method="post">';
                $q = mysql_query("SELECT * FROM `users` WHERE `rights` = '3'");
                while ($q1 = mysql_fetch_assoc($q)) {
                    $q2 = mysql_query("SELECT * FROM `forum` WHERE `type` = 'a' AND `from` = '" . $q1['name'] . "' and `refid` = '$id'");
                    $q3 = mysql_num_rows($q2);
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    if ($q3 == 0) {
                        echo "<input type='checkbox' name='moder[]' value='" . $q1['name'] . "'/>$q1[name]";
                    }
                    else {
                        echo "<input type='checkbox' name='moder[]' value='" . $q1['name'] . "' checked='checked'/>$q1[name]";
                    }
                    echo '</div>';
                    ++$i;
                }
                echo '<div class="gmenu"><input type="submit" name="submit" value="Запомнить"/></div></form>';
                echo '<div class="phdr"><a href="index.php?act=mod_forum&amp;mod=moders">Выбрать категорию</a></div>';
            }
            else {
                echo '<div class="phdr"><a href="index.php?act=mod_forum"><b>Управление Форумом</b></a> | Модераторvы</div>';
                echo '<div class="bmenu">Выберите категорию</div>';
                $q = mysql_query("select * from `forum` where type='f' order by realid;");
                while ($q1 = mysql_fetch_array($q)) {
                    echo is_integer($i / 2) ? '<div class="list1">' : '<div class="list2">';
                    echo '<a href="index.php?act=mod_forum&amp;mod=moders&amp;id=' . $q1['id'] . '">' . $q1['text'] . '</a></div>';
                    ++$i;
                }
            echo '<div class="phdr"><a href="index.php?act=mod_forum">Управление форумом</a></div>';
            }
        }
        break;

    default :
        ////////////////////////////////////////////////////////////
        // Панель управления форумом                              //
        ////////////////////////////////////////////////////////////
        $total_cat = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'f'"), 0);
        $total_sub = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'r'"), 0);
        $total_thm = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't'"), 0);
        $total_thm_del = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` = '1'"), 0);
        $total_msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm'"), 0);
        $total_msg_del = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` = '1'"), 0);
        $total_files = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_files`"), 0);
        $total_votes = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum_vote` WHERE `type` = '1'"), 0);
        echo '<div class="phdr"><a href="index.php"><b>Админ панель</b></a> | Управление форумом</div>';
        echo '<div class="gmenu"><p><h3><img src="../images/rate.gif" width="16" height="16" class="left" />&nbsp;Статистика</h3><ul>';
        echo '<li>Категории:&nbsp;' . $total_cat . '</li>';
        echo '<li>Разделы:&nbsp;' . $total_sub . '</li>';
        echo '<li>Темы:&nbsp;' . $total_thm . '&nbsp;/&nbsp;<span class="red">' . $total_thm_del . '</span></li>';
        echo '<li>Посты:&nbsp;' . $total_msg . '&nbsp;/&nbsp;<span class="red">' . $total_msg_del . '</span></li>';
        echo '<li>Файлы:&nbsp;' . $total_files . '</li>';
        echo '<li>Голосования:&nbsp;' . $total_votes . '</li>';
        echo '</ul></p></div>';
        echo '<div class="menu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&nbsp;Управление</h3><ul>';
        echo '<li><a href="index.php?act=mod_forum&amp;mod=cat"><b>Структура форума</b></a></li>';
        echo '<li><a href="index.php?act=mod_forum&amp;mod=hposts">Скрытые посты</a> (' . $total_msg_del . ')</li>';
        echo '<li><a href="index.php?act=mod_forum&amp;mod=htopics">Скрытые темы</a> (' . $total_thm_del . ')</li>';
        //echo '<li><a href="index.php?act=mod_forum&amp;mod=delhid">Чистка форума</a></li>';
        echo '<li><a href="index.php?act=mod_forum&amp;mod=moders">Модераторы</a></li>';
        echo '</ul></p></div>';
        echo '<div class="phdr"><a href="../forum/index.php">В форум</a></div>';
}

echo '<p><a href="index.php">Админ панель</a></p>';

?>