<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNADM') or die('Error: restricted access');

// Проверяем права доступа
if ($rights < 7) {
    header('Location: http://johncms.com/?err');
    exit;
}

// Подключаем языковый файл форума
$lng_forum = core::load_lng('forum');

// Задаем пользовательские настройки форума
$set_forum = unserialize($datauser['set_forum']);
if (!isset($set_forum) || empty($set_forum))
    $set_forum = array(
        'farea'    => 0,
        'upfp'     => 0,
        'farea_w'  => 20,
        'farea_h'  => 4,
        'postclip' => 1,
        'postcut'  => 2
    );
switch ($mod) {
    case 'del':
        /*
        -----------------------------------------------------------------
        Удаление категории, или раздела
        -----------------------------------------------------------------
        */
        if (!$id) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="index.php?act=forum">' . $lng_forum['forum_management'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id' AND (`type` = 'f' OR `type` = 'r')");
        if (mysql_num_rows($req)) {
            $res = mysql_fetch_assoc($req);
            echo '<div class="phdr"><b>' . ($res['type'] == 'r' ? $lng_forum['delete_section'] : $lng_forum['delete_catrgory']) . ':</b> ' . $res['text'] . '</div>';
            // Проверяем, есть ли подчиненная информация
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '$id' AND (`type` = 'f' OR `type` = 'r' OR `type` = 't')"), 0);
            if ($total) {
                if ($res['type'] == 'f') {
                    ////////////////////////////////////////////////////////////
                    // Удаление категории с подчиненными данными              //
                    ////////////////////////////////////////////////////////////
                    if (isset($_POST['submit'])) {
                        $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
                        if (!$category || $category == $id) {
                            echo functions::display_error($lng['error_wrong_data']);
                            require('../incfiles/end.php');
                            exit;
                        }
                        $check = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$category' AND `type` = 'f'"), 0);
                        if (!$check) {
                            echo functions::display_error($lng['error_wrong_data']);
                            require('../incfiles/end.php');
                            exit;
                        }
                        // Вычисляем правила сортировки и перемещаем разделы
                        $sort = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `refid` = '$category' AND `type` ='r' ORDER BY `realid` DESC"));
                        $sortnum = !empty($sort['realid']) && $sort['realid'] > 0 ? $sort['realid'] + 1 : 1;
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'r'");
                        while ($res_c = mysql_fetch_assoc($req_c)) {
                            mysql_query("UPDATE `forum` SET `refid` = '" . $category . "', `realid` = '$sortnum' WHERE `id` = '" . $res_c['id'] . "'");
                            ++$sortnum;
                        }
                        // Перемещаем файлы в выбранную категорию
                        mysql_query("UPDATE `cms_forum_files` SET `cat` = '" . $category . "' WHERE `cat` = '" . $res['refid'] . "'");
                        mysql_query("DELETE FROM `forum` WHERE `id` = '$id'");
                        echo '<div class="rmenu"><p><h3>' . $lng_forum['category_deleted'] . '</h3>' . $lng_forum['contents_moved_to'] . ' <a href="../forum/index.php?id=' . $category . '">' . $lng_forum['selected_category'] . '</a></p></div>';
                    } else {
                        echo '<form action="index.php?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                            '<div class="rmenu"><p>' . $lng['contents_move_warning'] . '</p>' .
                            '<p><h3>' . $lng_forum['select_category'] . '</h3><select name="category" size="1">';
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$id' ORDER BY `realid` ASC");
                        while ($res_c = mysql_fetch_assoc($req_c)) echo '<option value="' . $res_c['id'] . '">' . $res_c['text'] . '</option>';
                        echo '</select><br /><small>' . $lng_forum['contents_move_description'] . '</small></p>' .
                            '<p><input type="submit" name="submit" value="' . $lng['move'] . '" /></p></div>';
                        if ($rights == 9) {
                            // Для супервайзоров запрос на полное удаление
                            echo '<div class="rmenu"><p><h3>' . $lng_forum['delete_full'] . '</h3>' . $lng_forum['delete_full_note'] . ' <a href="index.php?act=forum&amp;mod=cat&amp;id=' . $id . '">' . $lng_forum['child_section'] . '</a></p>' .
                                '</div>';
                        }
                        echo '</form>';
                    }
                } else {
                    ////////////////////////////////////////////////////////////
                    // Удаление раздела с подчиненными данными                //
                    ////////////////////////////////////////////////////////////
                    if (isset($_POST['submit'])) {
                        // Предварительные проверки
                        $subcat = isset($_POST['subcat']) ? intval($_POST['subcat']) : 0;
                        if (!$subcat || $subcat == $id) {
                            echo functions::display_error($lng['error_wrong_data'], '<a href="index.php?act=forum">' . $lng_forum['forum_management'] . '</a>');
                            require('../incfiles/end.php');
                            exit;
                        }
                        $check = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$subcat' AND `type` = 'r'"), 0);
                        if (!$check) {
                            echo functions::display_error($lng['error_wrong_data'], '<a href="index.php?act=forum">' . $lng_forum['forum_management'] . '</a>');
                            require('../incfiles/end.php');
                            exit;
                        }
                        mysql_query("UPDATE `forum` SET `refid` = '$subcat' WHERE `refid` = '$id'");
                        mysql_query("UPDATE `cms_forum_files` SET `subcat` = '$subcat' WHERE `subcat` = '$id'");
                        mysql_query("DELETE FROM `forum` WHERE `id` = '$id'");
                        echo '<div class="rmenu"><p><h3>' . $lng_forum['section_deleted'] . '</h3>' . $lng_forum['themes_moved_to'] . ' <a href="../forum/index.php?id=' . $subcat . '">' . $lng_forum['selected_section'] . '</a>.' .
                            '</p></div>';
                    } elseif (isset($_POST['delete'])) {
                        if ($rights != 9) {
                            echo functions::display_error($lng['access_forbidden']);
                            require_once('../incfiles/end.php');
                            exit;
                        }
                        // Удаляем файлы
                        $req_f = mysql_query("SELECT * FROM `cms_forum_files` WHERE `subcat` = '$id'");
                        while ($res_f = mysql_fetch_assoc($req_f)) {
                            unlink('../files/forum/attach/' . $res_f['filename']);
                        }
                        mysql_query("DELETE FROM `cms_forum_files` WHERE `subcat` = '$id'");
                        // Удаляем посты, голосования и метки прочтений
                        $req_t = mysql_query("SELECT `id` FROM `forum` WHERE `refid` = '$id' AND `type` = 't'");
                        while ($res_t = mysql_fetch_assoc($req_t)) {
                            mysql_query("DELETE FROM `forum` WHERE `refid` = '" . $res_t['id'] . "'");
                            mysql_query("DELETE FROM `cms_forum_vote` WHERE `topic` = '" . $res_t['id'] . "'");
                            mysql_query("DELETE FROM `cms_forum_vote_users` WHERE `topic` = '" . $res_t['id'] . "'");
                            mysql_query("DELETE FROM `cms_forum_rdm` WHERE `topic_id` = '" . $res_t['id'] . "'");
                        }
                        // Удаляем темы
                        mysql_query("DELETE FROM `forum` WHERE `refid` = '$id'");
                        // Удаляем раздел
                        mysql_query("DELETE FROM `forum` WHERE `id` = '$id'");
                        // Оптимизируем таблицы
                        mysql_query("OPTIMIZE TABLE `cms_forum_files` , `cms_forum_rdm` , `forum` , `cms_forum_vote` , `cms_forum_vote_users`");
                        echo '<div class="rmenu"><p>' . $lng_forum['section_themes_deleted'] . '<br />' .
                            '<a href="index.php?act=forum&amp;mod=cat&amp;id=' . $res['refid'] . '">' . $lng_forum['to_category'] . '</a></p></div>';
                    } else {
                        echo '<form action="index.php?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST"><div class="rmenu">' .
                            '<p>' . $lng_forum['section_move_warning'] . '</p>' . '<p><h3>' . $lng_forum['select_section'] . '</h3>';
                        $cat = isset($_GET['cat']) ? abs(intval($_GET['cat'])) : 0;
                        $ref = $cat ? $cat : $res['refid'];
                        $req_r = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$ref' AND `id` != '$id' AND `type` = 'r' ORDER BY `realid` ASC");
                        while ($res_r = mysql_fetch_assoc($req_r)) {
                            echo '<input type="radio" name="subcat" value="' . $res_r['id'] . '" />&#160;' . $res_r['text'] . '<br />';
                        }
                        echo '</p><p><h3>' . $lng_forum['another_category'] . '</h3><ul>';
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' AND `id` != '$ref' ORDER BY `realid` ASC");
                        while ($res_c = mysql_fetch_assoc($req_c)) {
                            echo '<li><a href="index.php?act=forum&amp;mod=del&amp;id=' . $id . '&amp;cat=' . $res_c['id'] . '">' . $res_c['text'] . '</a></li>';
                        }
                        echo '</ul><small>' . $lng_forum['section_move_description'] . '</small></p>' .
                            '<p><input type="submit" name="submit" value="' . $lng['move'] . '" /></p></div>';
                        if ($rights == 9) {
                            // Для супервайзоров запрос на полное удаление
                            echo '<div class="rmenu"><p><h3>' . $lng_forum['delete_full'] . '</h3>' . $lng_forum['delete_full_warning'];
                            echo '</p><p><input type="submit" name="delete" value="' . $lng['delete'] . '" /></p></div>';
                        }
                        echo '</form>';
                    }
                }
            } else {
                ////////////////////////////////////////////////////////////
                // Удаление пустого раздела, или категории                //
                ////////////////////////////////////////////////////////////
                if (isset($_POST['submit'])) {
                    mysql_query("DELETE FROM `forum` WHERE `id` = '$id'");
                    echo '<div class="rmenu"><p>' . ($res['type'] == 'r' ? $lng_forum['section_deleted'] : $lng_forum['category_deleted']) . '</p></div>';
                } else {
                    echo '<div class="rmenu"><p>' . $lng['delete_confirmation'] . '</p>' .
                        '<p><form action="index.php?act=forum&amp;mod=del&amp;id=' . $id . '" method="POST">' .
                        '<input type="submit" name="submit" value="' . $lng['delete'] . '" />' .
                        '</form></p></div>';
                }
            }
            echo '<div class="phdr"><a href="index.php?act=forum&amp;mod=cat">' . $lng['back'] . '</a></div>';
        } else {
            header('Location: index.php?act=forum&mod=cat');
        }
        break;

    case 'add':
        /*
        -----------------------------------------------------------------
        Добавление категории
        -----------------------------------------------------------------
        */
        if ($id) {
            // Проверяем наличие категории
            $req = mysql_query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 'f'");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_array($req);
                $cat_name = $res['text'];
            } else {
                echo functions::display_error($lng['error_wrong_data'], '<a href="index.php?act=forum">' . $lng_forum['forum_management'] . '</a>');
                require('../incfiles/end.php');
                exit;
            }
        }
        if (isset($_POST['submit'])) {
            // Принимаем данные
            $name = isset($_POST['name']) ? functions::check($_POST['name']) : '';
            $desc = isset($_POST['desc']) ? functions::check($_POST['desc']) : '';
            $allow = isset($_POST['allow']) ? intval($_POST['allow']) : 0;
            // Проверяем на ошибки
            $error = array();
            if (!$name)
                $error[] = $lng['error_empty_title'];
            if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30))
                $error[] = $lng['title'] . ': ' . $lng['error_wrong_lenght'];
            if ($desc && mb_strlen($desc) < 2)
                $error[] = $lng['error_description_lenght'];
            if (!$error) {
                // Добавляем в базу категорию
                $req = mysql_query("SELECT `realid` FROM `forum` WHERE " . ($id ? "`refid` = '$id' AND `type` = 'r'" : "`type` = 'f'") . " ORDER BY `realid` DESC LIMIT 1");
                if (mysql_num_rows($req)) {
                    $res = mysql_fetch_assoc($req);
                    $sort = $res['realid'] + 1;
                } else {
                    $sort = 1;
                }
                if (mysql_query("INSERT INTO `forum` SET
                `refid` = '" . ($id ? $id : 0) . "',
                `type` = '" . ($id ? 'r' : 'f') . "',
                `text` = '$name',
                `soft` = '$desc',
                `edit` = '$allow',
                `curators` = '',
                `realid` = '$sort'")
                ) {
                    header('Location: index.php?act=forum&mod=cat' . ($id ? '&id=' . $id : ''));
                } else {
                    echo mysql_error();
                }
            } else {
                // Выводим сообщение об ошибках
                echo functions::display_error($error);
            }
        } else {
            // Форма ввода
            echo '<div class="phdr"><b>' . ($id ? $lng_forum['add_section'] : $lng_forum['add_category']) . '</b></div>';
            if ($id)
                echo '<div class="bmenu"><b>' . $lng_forum['to_category'] . ':</b> ' . $cat_name . '</div>';
            echo '<form action="index.php?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
                '<div class="gmenu">' .
                '<p><h3>' . $lng['title'] . '</h3>' .
                '<input type="text" name="name" />' .
                '<br /><small>' . $lng['minmax_2_30'] . '</small></p>' .
                '<p><h3>' . $lng['description'] . '</h3>' .
                '<textarea name="desc" rows="' . $set_user['field_h'] . '"></textarea>' .
                '<br /><small>' . $lng['not_mandatory_field'] . '<br />' . $lng['minmax_2_500'] . '</small></p>';
            if ($id) {
                echo '<p><input type="radio" name="allow" value="0" checked="checked"/>&#160;' . $lng['allow_plain'] . '<br/>' .
                    '<input type="radio" name="allow" value="4"/>&#160;' . $lng['allow_readonly'] . '<br/>' .
                    '<input type="radio" name="allow" value="2"/>&#160;' . $lng['allow_firstpost_edit'] . '<br/>' .
                    '<input type="radio" name="allow" value="1"/>&#160;' . $lng['allow_autocurators'] . '</p>';
            }
            echo '<p><input type="submit" value="' . $lng['add'] . '" name="submit" />' .
                '</p></div></form>' .
                '<div class="phdr"><a href="index.php?act=forum&amp;mod=cat' . ($id ? '&amp;id=' . $id : '') . '">' . $lng['back'] . '</a></div>';
        }
        break;

    case 'edit':
        /*
        -----------------------------------------------------------------
        Редактирование выбранной категории, или раздела
        -----------------------------------------------------------------
        */
        if (!$id) {
            echo functions::display_error($lng['error_wrong_data'], '<a href="index.php?act=forum">' . $lng_forum['forum_management'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
        if (mysql_num_rows($req)) {
            $res = mysql_fetch_assoc($req);
            if ($res['type'] == 'f' || $res['type'] == 'r') {
                if (isset($_POST['submit'])) {
                    // Принимаем данные
                    $name = isset($_POST['name']) ? functions::check($_POST['name']) : '';
                    $desc = isset($_POST['desc']) ? functions::check($_POST['desc']) : '';
                    $category = isset($_POST['category']) ? intval($_POST['category']) : 0;
                    $allow = isset($_POST['allow']) ? intval($_POST['allow']) : 0;
                    // проверяем на ошибки
                    $error = array();
                    if ($res['type'] == 'r' && !$category)
                        $error[] = $lng_forum['error_category_select'];
                    elseif ($res['type'] == 'r' && !mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `id` = '$category' AND `type` = 'f'"), 0))
                        $error[] = $lng_forum['error_category_select'];
                    if (!$name)
                        $error[] = $lng['error_empty_title'];
                    if ($name && (mb_strlen($name) < 2 || mb_strlen($name) > 30))
                        $error[] = $lng['title'] . ': ' . $lng['error_wrong_lenght'];
                    if ($desc && mb_strlen($desc) < 2)
                        $error[] = $lng['error_description_lenght'];
                    if (!$error) {
                        // Записываем в базу
                        mysql_query("UPDATE `forum` SET
                            `text` = '$name',
                            `soft` = '$desc',
                            `edit` = '$allow'
                            WHERE `id` = '$id'");
                        if ($res['type'] == 'r' && $category != $res['refid']) {
                            // Вычисляем сортировку
                            $req_s = mysql_query("SELECT `realid` FROM `forum` WHERE `refid` = '$category' AND `type` = 'r' ORDER BY `realid` DESC LIMIT 1");
                            $res_s = mysql_fetch_assoc($req_s);
                            $sort = $res_s['realid'] + 1;
                            // Меняем категорию
                            mysql_query("UPDATE `forum` SET `refid` = '$category', `realid` = '$sort' WHERE `id` = '$id'");
                            // Меняем категорию для прикрепленных файлов
                            mysql_query("UPDATE `cms_forum_files` SET `cat` = '$category' WHERE `cat` = '" . $res['refid'] . "'");
                        }
                        header('Location: index.php?act=forum&mod=cat' . ($res['type'] == 'r' ? '&id=' . $res['refid'] : ''));
                    } else {
                        // Выводим сообщение об ошибках
                        echo functions::display_error($error);
                    }
                } else {
                    // Форма ввода
                    echo '<div class="phdr"><b>' . ($res['type'] == 'r' ? $lng_forum['section_edit'] : $lng_forum['category_edit']) . '</b></div>' .
                        '<form action="index.php?act=forum&amp;mod=edit&amp;id=' . $id . '" method="post">' .
                        '<div class="gmenu">' .
                        '<p><h3>' . $lng['title'] . '</h3>' .
                        '<input type="text" name="name" value="' . $res['text'] . '"/>' .
                        '<br /><small>' . $lng['minmax_2_30'] . '</small></p>' .
                        '<p><h3>' . $lng['description'] . '</h3>' .
                        '<textarea name="desc" rows="' . $set_user['field_h'] . '">' . str_replace('<br />', "\r\n", $res['soft']) . '</textarea>' .
                        '<br /><small>' . $lng['not_mandatory_field'] . '<br />' . $lng['minmax_2_500'] . '</small></p>';
                    if ($res['type'] == 'r') {
                        $allow = !empty($res['edit']) ? intval($res['edit']) : 0;
                        echo '<p><input type="radio" name="allow" value="0" ' . (!$allow ? 'checked="checked"' : '') . '/>&#160;' . $lng['allow_plain'] . '<br/>' .
                            '<input type="radio" name="allow" value="4" ' . ($allow == 4 ? 'checked="checked"' : '') . '/>&#160;' . $lng['allow_readonly'] . '<br/>' .
                            '<input type="radio" name="allow" value="2" ' . ($allow == 2 ? 'checked="checked"' : '') . '/>&#160;' . $lng['allow_firstpost_edit'] . '<br/>' .
                            '<input type="radio" name="allow" value="1" ' . ($allow == 1 ? 'checked="checked"' : '') . '/>&#160;' . $lng['allow_autocurators'] . '</p>';
                        echo '<p><h3>' . $lng_forum['category'] . '</h3><select name="category" size="1">';
                        $req_c = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");
                        while ($res_c = mysql_fetch_assoc($req_c)) {
                            echo '<option value="' . $res_c['id'] . '"' . ($res_c['id'] == $res['refid'] ? ' selected="selected"' : '') . '>' . $res_c['text'] . '</option>';
                        }
                        echo '</select></p>';
                    }
                    echo '<p><input type="submit" value="' . $lng['save'] . '" name="submit" />' .
                        '</p></div></form>' .
                        '<div class="phdr"><a href="index.php?act=forum&amp;mod=cat' . ($res['type'] == 'r' ? '&amp;id=' . $res['refid'] : '') . '">' . $lng['back'] . '</a></div>';
                }
            } else {
                header('Location: index.php?act=forum&mod=cat');
            }
        } else {
            header('Location: index.php?act=forum&mod=cat');
        }
        break;

    case 'up':
        /*
        -----------------------------------------------------------------
        Перемещение на одну позицию вверх
        -----------------------------------------------------------------
        */
        if ($id) {
            $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
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
        header('Location: index.php?act=forum&mod=cat' . ($res1['type'] == 'r' ? '&id=' . $res1['refid'] : ''));
        break;

    case 'down':
        /*
        -----------------------------------------------------------------
        Перемещение на одну позицию вниз
        -----------------------------------------------------------------
        */
        if ($id) {
            $req = mysql_query("SELECT * FROM `forum` WHERE `id` = '$id'");
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
        header('Location: index.php?act=forum&mod=cat' . ($res1['type'] == 'r' ? '&id=' . $res1['refid'] : ''));
        break;

    case 'cat':
        /*
        -----------------------------------------------------------------
        Управление категориями и разделами
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=forum"><b>' . $lng_forum['forum_management'] . '</b></a> | ' . $lng_forum['forum_structure'] . '</div>';
        if ($id) {
            // Управление разделами
            $req = mysql_query("SELECT `text` FROM `forum` WHERE `id` = '$id' AND `type` = 'f'");
            $res = mysql_fetch_assoc($req);
            echo '<div class="bmenu"><a href="index.php?act=forum&amp;mod=cat"><b>' . $res['text'] . '</b></a> | ' . $lng_forum['section_list'] . '</div>';
            $req = mysql_query("SELECT * FROM `forum` WHERE `refid` = '$id' AND `type` = 'r' ORDER BY `realid` ASC");
            if (mysql_num_rows($req)) {
                $i = 0;
                while ($res = mysql_fetch_assoc($req)) {
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo '<b>' . $res['text'] . '</b>' .
                        '&#160;<a href="../forum/index.php?id=' . $res['id'] . '">&gt;&gt;</a>';
                    if (!empty($res['soft']))
                        echo '<br /><span class="gray"><small>' . $res['soft'] . '</small></span><br />';
                    echo '<div class="sub">' .
                        '<a href="index.php?act=forum&amp;mod=up&amp;id=' . $res['id'] . '">' . $lng['up'] . '</a> | ' .
                        '<a href="index.php?act=forum&amp;mod=down&amp;id=' . $res['id'] . '">' . $lng['down'] . '</a> | ' .
                        '<a href="index.php?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . $lng['edit'] . '</a> | ' .
                        '<a href="index.php?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a>' .
                        '</div></div>';
                    ++$i;
                }
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
        } else {
            // Управление категориями
            echo '<div class="bmenu">' . $lng_forum['category_list'] . '</div>';
            $req = mysql_query("SELECT * FROM `forum` WHERE `type` = 'f' ORDER BY `realid` ASC");
            $i = 0;
            while ($res = mysql_fetch_assoc($req)) {
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                echo '<a href="index.php?act=forum&amp;mod=cat&amp;id=' . $res['id'] . '"><b>' . $res['text'] . '</b></a> ' .
                    '(' . mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'r' AND `refid` = '" . $res['id'] . "'"), 0) . ')' .
                    '&#160;<a href="../forum/index.php?id=' . $res['id'] . '">&gt;&gt;</a>';
                if (!empty($res['soft']))
                    echo '<br /><span class="gray"><small>' . $res['soft'] . '</small></span><br />';
                echo '<div class="sub">' .
                    '<a href="index.php?act=forum&amp;mod=up&amp;id=' . $res['id'] . '">' . $lng['up'] . '</a> | ' .
                    '<a href="index.php?act=forum&amp;mod=down&amp;id=' . $res['id'] . '">' . $lng['down'] . '</a> | ' .
                    '<a href="index.php?act=forum&amp;mod=edit&amp;id=' . $res['id'] . '">' . $lng['edit'] . '</a> | ' .
                    '<a href="index.php?act=forum&amp;mod=del&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a>' .
                    '</div></div>';
                ++$i;
            }
        }
        echo '<div class="gmenu">' .
            '<form action="index.php?act=forum&amp;mod=add' . ($id ? '&amp;id=' . $id : '') . '" method="post">' .
            '<input type="submit" value="' . $lng['add'] . '" />' .
            '</form></div>' .
            '<div class="phdr">' . ($mod == 'cat' && $id ? '<a href="index.php?act=forum&amp;mod=cat">' . $lng_forum['category_list'] . '</a>' : '<a href="index.php?act=forum">' . $lng_forum['forum_management'] . '</a>') . '</div>';
        break;

    case 'htopics':
        /*
        -----------------------------------------------------------------
        Управление скрытыми темами форума
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=forum"><b>' . $lng_forum['forum_management'] . '</b></a> | ' . $lng_forum['hidden_topics'] . '</div>';
        $sort = '';
        $link = '';
        if (isset($_GET['usort'])) {
            $sort = " AND `forum`.`user_id` = '" . abs(intval($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs(intval($_GET['usort']));
            echo '<div class="bmenu">' . $lng_forum['filter_on_author'] . ' <a href="index.php?act=forum&amp;mod=htopics">[x]</a></div>';
        }
        if (isset($_GET['rsort'])) {
            $sort = " AND `forum`.`refid` = '" . abs(intval($_GET['rsort'])) . "'";
            $link = '&amp;rsort=' . abs(intval($_GET['rsort']));
            echo '<div class="bmenu">' . $lng_forum['filter_on_section'] . ' <a href="index.php?act=forum&amp;mod=htopics">[x]</a></div>';
        }
        if (isset($_POST['deltopic'])) {
            if ($rights != 9) {
                echo functions::display_error($lng['access_forbidden']);
                require('../incfiles/end.php');
                exit;
            }
            $req = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort");
            while ($res = mysql_fetch_assoc($req)) {
                $req_f = mysql_query("SELECT * FROM `cms_forum_files` WHERE `topic` = '" . $res['id'] . "'");
                if (mysql_num_rows($req_f)) {
                    // Удаляем файлы
                    while ($res_f = mysql_fetch_assoc($req_f)) {
                        unlink('../files/forum/attach/' . $res_f['filename']);
                    }
                    mysql_query("DELETE FROM `cms_forum_files` WHERE `topic` = '" . $res['id'] . "'");
                }
                // Удаляем посты
                mysql_query("DELETE FROM `forum` WHERE `type` = 'm' AND `refid` = '" . $res['id'] . "'");
            }
            // Удаляем темы
            $req = mysql_query("DELETE FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort");
            header('Location: index.php?act=forum&mod=htopics');
        } else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` = '1' $sort"), 0);
            if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?act=forum&amp;mod=htopics&amp;', $start, $total, $kmess) . '</div>';
            $req = mysql_query("SELECT `forum`.*, `forum`.`id` AS `fid`, `forum`.`user_id` AS `id`, `forum`.`from` AS `name`, `forum`.`soft` AS `browser`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`
            FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
            WHERE `forum`.`type` = 't' AND `forum`.`close` = '1' $sort ORDER BY `forum`.`id` DESC LIMIT $start, $kmess");
            if (mysql_num_rows($req)) {
                $i = 0;
                while ($res = mysql_fetch_assoc($req)) {
                    $subcat = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $res['refid'] . "'"));
                    $cat = mysql_fetch_assoc(mysql_query("SELECT * FROM `forum` WHERE `id` = '" . $subcat['refid'] . "'"));
                    $ttime = '<span class="gray">(' . functions::display_date($res['time']) . ')</span>';
                    $text = '<a href="../forum/index.php?id=' . $res['fid'] . '"><b>' . $res['text'] . '</b></a>';
                    $text .= '<br /><small><a href="../forum/index.php?id=' . $cat['id'] . '">' . $cat['text'] . '</a> / <a href="../forum/index.php?id=' . $subcat['id'] . '">' . $subcat['text'] . '</a></small>';
                    $subtext = '<span class="gray">' . $lng_forum['filter_to'] . ':</span> ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=htopics&amp;rsort=' . $res['refid'] . '">' . $lng_forum['by_section'] . '</a> | ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=htopics&amp;usort=' . $res['user_id'] . '">' . $lng_forum['by_author'] . '</a>';
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo functions::display_user($res, array(
                        'header' => $ttime,
                        'body'   => $text,
                        'sub'    => $subtext
                    ));
                    echo '</div>';
                    ++$i;
                }
                if ($rights == 9)
                    echo '<form action="index.php?act=forum&amp;mod=htopics' . $link . '" method="POST">' .
                        '<div class="rmenu">' .
                        '<input type="submit" name="deltopic" value="' . $lng['delete_all'] . '" />' .
                        '</div></form>';
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?act=forum&amp;mod=htopics&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php?act=forum&amp;mod=htopics" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
                    '</form></p>';
            }
        }
        break;

    case 'hposts':
        /*
        -----------------------------------------------------------------
        Управление скрытыми постави форума
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php?act=forum"><b>' . $lng_forum['forum_management'] . '</b></a> | ' . $lng_forum['hidden_posts'] . '</div>';
        $sort = '';
        $link = '';
        if (isset($_GET['tsort'])) {
            $sort = " AND `forum`.`refid` = '" . abs(intval($_GET['tsort'])) . "'";
            $link = '&amp;tsort=' . abs(intval($_GET['tsort']));
            echo '<div class="bmenu">' . $lng_forum['filter_on_theme'] . ' <a href="index.php?act=forum&amp;mod=hposts">[x]</a></div>';
        } elseif (isset($_GET['usort'])) {
            $sort = " AND `forum`.`user_id` = '" . abs(intval($_GET['usort'])) . "'";
            $link = '&amp;usort=' . abs(intval($_GET['usort']));
            echo '<div class="bmenu">' . $lng_forum['filter_on_author'] . ' <a href="index.php?act=forum&amp;mod=hposts">[x]</a></div>';
        }
        if (isset($_POST['delpost'])) {
            if ($rights != 9) {
                echo functions::display_error($lng['access_forbidden']);
                require('../incfiles/end.php');
                exit;
            }
            $req = mysql_query("SELECT `id` FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort");
            while ($res = mysql_fetch_assoc($req)) {
                $req_f = mysql_query("SELECT * FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "' LIMIT 1");
                if (mysql_num_rows($req_f)) {
                    $res_f = mysql_fetch_assoc($req_f);
                    // Удаляем файлы
                    unlink('../files/forum/attach/' . $res_f['filename']);
                    mysql_query("DELETE FROM `cms_forum_files` WHERE `post` = '" . $res['id'] . "' LIMIT 1");
                }
            }
            // Удаляем посты
            mysql_query("DELETE FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort");
            header('Location: index.php?act=forum&mod=hposts');
        } else {
            $total = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` = '1' $sort"), 0);
            if ($total > $kmess) echo '<div class="topmenu">' . functions::display_pagination('index.php?act=forum&amp;mod=hposts&amp;', $start, $total, $kmess) . '</div>';
            $req = mysql_query("SELECT `forum`.*, `forum`.`id` AS `fid`, `forum`.`user_id` AS `id`, `forum`.`from` AS `name`, `forum`.`soft` AS `browser`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`
            FROM `forum` LEFT JOIN `users` ON `forum`.`user_id` = `users`.`id`
            WHERE `forum`.`type` = 'm' AND `forum`.`close` = '1' $sort ORDER BY `forum`.`id` DESC LIMIT $start, $kmess");
            if (mysql_num_rows($req)) {
                $i = 0;
                while ($res = mysql_fetch_assoc($req)) {
                    $res['ip'] = ip2long($res['ip']);
                    $posttime = ' <span class="gray">(' . functions::display_date($res['time']) . ')</span>';
                    $page = ceil(mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '" . $res['fid'] . "'"), 0) / $kmess);
                    $text = mb_substr($res['text'], 0, 500);
                    $text = functions::checkout($text, 1, 0);
                    $text = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $text);
                    $theme = mysql_fetch_assoc(mysql_query("SELECT `id`, `text` FROM `forum` WHERE `id` = '" . $res['refid'] . "'"));
                    $text = '<b>' . $theme['text'] . '</b> <a href="../forum/index.php?id=' . $theme['id'] . '&amp;page=' . $page . '">&gt;&gt;</a><br />' . $text;
                    $subtext = '<span class="gray">' . $lng_forum['filter_to'] . ':</span> ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=hposts&amp;tsort=' . $theme['id'] . '">' . $lng_forum['by_theme'] . '</a> | ';
                    $subtext .= '<a href="index.php?act=forum&amp;mod=hposts&amp;usort=' . $res['user_id'] . '">' . $lng_forum['by_author'] . '</a>';
                    echo $i % 2 ? '<div class="list2">' : '<div class="list1">';
                    echo functions::display_user($res, array(
                        'header' => $posttime,
                        'body'   => $text,
                        'sub'    => $subtext
                    ));
                    echo '</div>';
                    ++$i;
                }
                if ($rights == 9)
                    echo '<form action="index.php?act=forum&amp;mod=hposts' . $link . '" method="POST"><div class="rmenu"><input type="submit" name="delpost" value="' . $lng['delete_all'] . '" /></div></form>';
            } else {
                echo '<div class="menu"><p>' . $lng['list_empty'] . '</p></div>';
            }
            echo '<div class="phdr">' . $lng['total'] . ': ' . $total . '</div>';
            if ($total > $kmess) {
                echo '<div class="topmenu">' . functions::display_pagination('index.php?act=forum&amp;mod=hposts&amp;', $start, $total, $kmess) . '</div>' .
                    '<p><form action="index.php?act=forum&amp;mod=hposts" method="post">' .
                    '<input type="text" name="page" size="2"/>' .
                    '<input type="submit" value="' . $lng['to_page'] . ' &gt;&gt;"/>' .
                    '</form></p>';
            }
        }
        break;

    default:
        /*
        -----------------------------------------------------------------
        Панель управления форумом
        -----------------------------------------------------------------
        */
        $total_cat = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'f'"), 0);
        $total_sub = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'r'"), 0);
        $total_thm = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't'"), 0);
        $total_thm_del = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `close` = '1'"), 0);
        $total_msg = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm'"), 0);
        $total_msg_del = mysql_result(mysql_query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `close` = '1'"), 0);
        $total_files = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_files`"), 0);
        $total_votes = mysql_result(mysql_query("SELECT COUNT(*) FROM `cms_forum_vote` WHERE `type` = '1'"), 0);
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng_forum['forum_management'] . '</div>' .
            '<div class="gmenu"><p><h3>' . functions::image('rate.gif') . $lng['statistics'] . '</h3><ul>' .
            '<li>' . $lng['categories'] . ':&#160;' . $total_cat . '</li>' .
            '<li>' . $lng['sections'] . ':&#160;' . $total_sub . '</li>' .
            '<li>' . $lng['themes'] . ':&#160;' . $total_thm . '&#160;/&#160;<span class="red">' . $total_thm_del . '</span></li>' .
            '<li>Посты:&#160;' . $total_msg . '&#160;/&#160;<span class="red">' . $total_msg_del . '</span></li>' .
            '<li>' . $lng['files'] . ':&#160;' . $total_files . '</li>' .
            '<li>' . $lng['votes'] . ':&#160;' . $total_votes . '</li>' .
            '</ul></p></div>' .
            '<div class="menu"><p><h3><img src="../images/settings.png" width="16" height="16" class="left" />&#160;' . $lng['settings'] . '</h3><ul>' .
            '<li><a href="index.php?act=forum&amp;mod=cat"><b>' . $lng_forum['forum_structure'] . '</b></a></li>' .
            '<li><a href="index.php?act=forum&amp;mod=hposts">' . $lng_forum['hidden_posts'] . '</a> (' . $total_msg_del . ')</li>' .
            '<li><a href="index.php?act=forum&amp;mod=htopics">' . $lng_forum['hidden_topics'] . '</a> (' . $total_thm_del . ')</li>' .
            '</ul></p></div>' .
            '<div class="phdr"><a href="../forum/index.php">' . $lng_forum['to_forum'] . '</a></div>';
}
echo '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';