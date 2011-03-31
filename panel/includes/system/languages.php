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
if ($rights < 9) {
    header('Location: http://johncms.com/?err');
    exit;
}
switch ($mod) {
    case 'edit':
        /*
        -----------------------------------------------------------------
        Редактируем пользовательские фразы
        -----------------------------------------------------------------
        */
        echo '<div class="rmenu">' .
            '<p>Функция редактирования фраз пока недоступна.<br />Будет в одной из следующих версий</p>' .
            '<p>Editing the phrase is not yet available.<br />Will be in one of the following versions.</p>' .
            '</div>';
        break;

    case 'delete':
        /*
        -----------------------------------------------------------------
        Удаляем язык
        -----------------------------------------------------------------
        */
        $error = array ();
        if (!$id)
            $error[] = $lng['error_wrong_data'];
        if ($id == $set['lng_id'])
            $error[] = $lng['language_delete_error'];
        if (!$error) {
            $req = mysql_query("SELECT * FROM `cms_lng_list` WHERE `id` = '$id'");
            if (!mysql_num_rows($req))
                $error[] = $lng['language_select_error'];
        }
        if (!$error) {
            if (isset($_POST['submit'])) {
                mysql_query("UPDATE `users` SET `set_language` = '" . $set['lng_id'] . "' WHERE `set_language` = '$id'");
                mysql_query("DELETE FROM `cms_lng_phrases` WHERE `language_id` = '$id'");
                mysql_query("DELETE FROM `cms_lng_list` WHERE `id` = '$id'");
                mysql_query("OPTIMIZE TABLE `cms_lng_list` , `cms_lng_phrases`");
                header('Location: index.php?act=languages');
            } else {
                $res = mysql_fetch_assoc($req);
                $attr = unserialize($res['attr']);
                echo '<div class="phdr"><a href="index.php?act=languages"><b>' . $lng['language'] . '</b></a> | ' . $lng['delete'] . '</div>' .
                    '<div class="rmenu"><form action="index.php?act=languages&amp;mod=delete&amp;id=' . $id . '" method="post">' .
                    '<p>' . $lng['language_delete_warning'] . ': <b>' . $attr['name'] . '</b>?</p>' .
                    '<p><input type="submit" name="submit" value="' . $lng['delete'] . '" /></p>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="index.php?act=languages">' . $lng['cancel'] . '</a></div>';
            }
        } else {
            echo functions::display_error($error, '<a href="index.php?act=languages">' . $lng['back'] . '</a>');
            require('../incfiles/end.php');
            exit;
        }
        break;

    case 'set':
        /*
        -----------------------------------------------------------------
        Устанавливаем системный язык
        -----------------------------------------------------------------
        */
        if ($id && $id != $set['lng_id']) {
            $req = mysql_query("SELECT * FROM `cms_lng_list` WHERE `id` = '$id'");
            if (mysql_num_rows($req)) {
                $res = mysql_fetch_assoc($req);
                mysql_query("UPDATE `cms_settings` SET `val` = '$id' WHERE `key` = 'lng_id'");
                mysql_query("UPDATE `cms_settings` SET `val` = '" . $res['iso'] . "' WHERE `key` = 'lng_iso'");
                $set['lng_id'] = $id;
                echo '<div class="gmenu">' . $lng['language_set'] . ': <b>' . $res['default'] . '</b></div>';
            }
        }
        header('Location: index.php?act=languages');
        break;

    default:
        /*
        -----------------------------------------------------------------
        Выводим список доступных языков
        -----------------------------------------------------------------
        */
        echo '<div class="phdr"><a href="index.php"><b>' . $lng['admin_panel'] . '</b></a> | ' . $lng['language_default'] . '</div>';
        echo '<div class="menu"><form action="index.php?act=languages&amp;mod=set" method="post"><p>';
        echo '<table><tr><td>&nbsp;</td><td style="padding-bottom:4px"><h3>' . $lng['language_system'] . '</h3></td></tr>';
        $req = mysql_query("SELECT * FROM `cms_lng_list`");
        while ($res = mysql_fetch_assoc($req)) {
            $attr = unserialize($res['attr']);
            $lng_menu = array (
                (!empty($attr['author']) ? '<span class="gray">' . $lng['author'] . ':</span> ' . $attr['author'] : ''),
                (!empty($attr['author_email']) ? '<span class="gray">E-mail:</span> ' . $attr['author_email'] : ''),
                (!empty($attr['author_url']) ? '<span class="gray">URL:</span> ' . $attr['author_url'] : ''),
                (!empty($attr['description']) ? '<span class="gray">' . $lng['description'] . ':</span> ' . $attr['description'] : '')
            );
            echo '<tr>' .
                '<td valign="top"><input type="radio" value="' . $res['id'] . '" name="id" ' . ($res['id'] == $set['lng_id'] ? 'checked="checked"' : '') . '/></td>' .
                '<td style="padding-bottom:6px"><b>' . $res['name'] . '</b>&#160;<span class="green">[' . $res['iso'] . ']</span> <small class="gray">build:' . $res['build'] . '</small>' .
                '<div class="sub"><a href="index.php?act=languages&amp;mod=edit&amp;id=' . $res['id'] . '">' . $lng['edit'] . '</a> | ' .
                '<a href="index.php?act=languages&amp;mod=delete&amp;id=' . $res['id'] . '">' . $lng['delete'] . '</a><br />' . functions::display_menu($lng_menu, '<br />') . '</div></td>' .
                '</tr>';
        }
        echo '<tr><td>&nbsp;</td><td><input type="submit" name="submit" value="' . $lng['save'] . '" /></td></tr>' .
            '</table></p>' .
            '</form></div><div class="phdr">' . $lng['total'] . ': ' . mysql_num_rows($req) . '</div>' .
            '<p><a href="index.php">' . $lng['admin_panel'] . '</a></p>';
}
?>