<?php

/**
 * @package     JohnCMS
 * @link        http://johncms.com
 * @copyright   Copyright (C) 2008-2011 JohnCMS Community
 * @license     LICENSE.txt (see attached file)
 * @version     VERSION.txt (see attached file)
 * @author      http://johncms.com/about
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

require('../incfiles/head.php');
if (!$user_id || !$id) {
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}
$stmt = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'm' " . ($rights >= 7 ? "" : " AND `close` != '1'"));
if ($stmt->rowCount()) {
    /*
    -----------------------------------------------------------------
    Предварительные проверки
    -----------------------------------------------------------------
    */
    $res = $stmt->fetch();

    $topic = $db->query("SELECT `refid`, `curators` FROM `forum` WHERE `id` = " . $res['refid'] . " LIMIT 1")->fetch();
    $curators = !empty($topic['curators']) ? unserialize($topic['curators']) : array();

    if (array_key_exists($user_id, $curators)) $rights = 3;
    $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">=" : "<=") . " '$id'" . ($rights < 7 ? " AND `close` != '1'" : ''))->fetchColumn() / $kmess);
    $posts = $db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `close` != '1'")->fetchColumn();
    $link = 'index.php?id=' . $res['refid'] . '&amp;page=' . $page;
    $error = FALSE;
    if ($rights == 3 || $rights >= 6) {
        // Проверка для Администрации
        if ($res['user_id'] != $user_id) {
            $stmt = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "' LIMIT 1");
            if ($stmt->rowCount()) {
                $res_u = $stmt->fetch();
                if ($res_u['rights'] > $datauser['rights']) {
                    $error = $lng['error_edit_rights'] . '<br /><a href="' . $link . '">' . $lng['back'] . '</a>';
                }
            }
        }
    } else {
        // Проверка для обычных юзеров
        if ($res['user_id'] != $user_id) {
            $error = $lng_forum['error_edit_another'] . '<br /><a href="' . $link . '">' . $lng['back'] . '</a>';
        }
        if (!$error) {
            $section = $db->query("SELECT * FROM `forum` WHERE `id` = " . $topic['refid'] . " LIMIT 1")->fetch();
            $allow = !empty($section['edit']) ? intval($section['edit']) : 0;

            $check = TRUE;
            if ($allow == 2) {
                $first = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['refid'] . "' ORDER BY `id` ASC LIMIT 1")->fetch();
                if ($first['user_id'] == $user_id && $first['id'] == $id) {
                    $check = FALSE;
                }
            }

            if ($check) {
                $res_m = $db->query("SELECT * FROM `forum` WHERE `refid` = '" . $res['refid'] . "' ORDER BY `id` DESC LIMIT 1")->fetch();
                if ($res_m['user_id'] != $user_id) {
                    $error = $lng_forum['error_edit_last'] . '<br /><a href="' . $link . '">' . $lng['back'] . '</a>';
                } elseif ($res['time'] < time() - 300) {
                    $error = $lng_forum['error_edit_timeout'] . '<br /><a href="' . $link . '">' . $lng['back'] . '</a>';
                }
            }
        }
    }
} else {
    $error = $lng_forum['error_post_deleted'] . '<br /><a href="index.php">' . $lng['forum'] . '</a>';
}
if (!$error) {
    switch ($do) {
        case 'restore':
            /*
            -----------------------------------------------------------------
            Восстановление удаленного поста
            -----------------------------------------------------------------
            */
            $stmt = $db->query("SELECT `postforum` FROM `users` WHERE `id` = '" . $res['user_id'] . "'");
            if ($stmt->rowCount()) {
                // Добавляем один балл к счетчику постов юзера
                $res_u = $stmt->fetch();
                $db->exec("UPDATE `users` SET `postforum` = '" . ($res_u['postforum'] + 1) . "' WHERE `id` = '" . $res['user_id'] . "'");
            }
            $db->exec("UPDATE `forum` SET `close` = '0', `close_who` = '$login' WHERE `id` = '$id'");
            $stmt = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");
            if ($stmt->fetchColumn()) {
                $db->exec("UPDATE `cms_forum_files` SET `del` = '0' WHERE `post` = '$id' LIMIT 1");
            }
            header('Location: ' . $link); exit;
            break;

        case 'delete':
            /*
            -----------------------------------------------------------------
            Удаление поста и прикрепленного файла
            -----------------------------------------------------------------
            */
            if ($res['close'] != 1) {
                $stmt = $db->query("SELECT `postforum` FROM `users` WHERE `id` = '" . $res['user_id'] . "' LIMIT 1");
                if ($stmt->rowCount()) {
                    // Вычитаем один балл из счетчика постов юзера
                    $res_u = $stmt->fetch();
                    $postforum = $res_u['postforum'] > 0 ? $res_u['postforum'] - 1 : 0;
                    $db->exec("UPDATE `users` SET `postforum` = '" . $postforum . "' WHERE `id` = '" . $res['user_id'] . "'");
                }
            }
            if ($rights == 9 && !isset($_GET['hide'])) {
                // Удаление поста (для Супервизоров)
                $stmt = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");
                if ($stmt->rowCount()) {
                    // Если есть прикрепленный файл, удаляем его
                    $res_f = $stmt->fetch();
                    unlink('../files/forum/attach/' . $res_f['filename']);
                    $db->exec("DELETE FROM `cms_forum_files` WHERE `post` = '$id' LIMIT 1");
                }
                // Формируем ссылку на нужную страницу темы
                $page = ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `refid` = '" . $res['refid'] . "' AND `id` " . ($set_forum['upfp'] ? ">" : "<") . " '$id'")->fetchColumn() / $kmess);
                $db->exec("DELETE FROM `forum` WHERE `id` = '$id'");
                if ($posts < 2) {
                    // Пересылка на удаление всей темы
                    header('Location: index.php?act=deltema&id=' . $res['refid']); exit;
                } else {
                    header('Location: index.php?id=' . $res['refid'] . '&page=' . $page); exit;
                }
            } else {
                // Скрытие поста
                $stmt = $db->query("SELECT COUNT(*) FROM `cms_forum_files` WHERE `post` = '$id'");
                if ($stmt->fetchColumn()) {
                    // Если есть прикрепленный файл, скрываем его
                    $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `post` = '$id' LIMIT 1");
                }
                if ($posts == 1) {
                    // Если это был последний пост темы, то скрываем саму тему
                    $res_l = $db->query("SELECT `refid` FROM `forum` WHERE `id` = '" . $res['refid'] . "' LIMIT 1")->fetch();
                    $db->exec("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '" . $res['refid'] . "' AND `type` = 't'");
                    header('Location: index.php?id=' . $res_l['refid']); exit;
                } else {
                    $db->exec("UPDATE `forum` SET `close` = '1', `close_who` = '$login' WHERE `id` = '$id'");
                    header('Location: index.php?id=' . $res['refid'] . '&page=' . $page); exit;
                }
            }
            break;

        case 'del':
            /*
            -----------------------------------------------------------------
            Удаление поста, предварительное напоминание
            -----------------------------------------------------------------
            */
            echo '<div class="phdr"><a href="' . $link . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['delete_post'] . '</div>' .
                '<div class="rmenu"><p>';
            if ($posts == 1) {
                echo $lng_forum['delete_last_post_warning'] . '<br />';
            }
            echo $lng['delete_confirmation'] . '</p>' .
                '<p><a href="' . $link . '">' . $lng['cancel'] . '</a> | <a href="index.php?act=editpost&amp;do=delete&amp;id=' . $id . '">' . $lng['delete'] . '</a>';
            if ($rights == 9) {
                echo ' | <a href="index.php?act=editpost&amp;do=delete&amp;hide&amp;id=' . $id . '">' . $lng['hide'] . '</a>';
            }
            echo '</p></div>';
            echo '<div class="phdr"><small>' . $lng_forum['delete_post_help'] . '</small></div>';
            break;

        default:
            /*
            -----------------------------------------------------------------
            Редактирование поста
            -----------------------------------------------------------------
            */
            $msg = isset($_POST['msg']) ? functions::checkin(trim($_POST['msg'])) : '';
            if (isset($_POST['msgtrans']))
                $msg = functions::trans($msg);
            if (isset($_POST['submit'])) {
                if (empty($_POST['msg'])) {
                    echo functions::display_error($lng['error_empty_message'], '<a href="index.php?act=editpost&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
                    require('../incfiles/end.php');
                    exit;
                }
                $stmt = $db->prepare("UPDATE `forum` SET
                    `tedit` = '" . time() . "',
                    `edit` = ?,
                    `kedit` = '" . ($res['kedit'] + 1) . "',
                    `text` = ?
                    WHERE `id` = '$id'
                ");
                $stmt->execute([
                    $login,
                    $msg
                ]);
                header('Location: index.php?id=' . $res['refid'] . '&page=' . $page); exit;
            } else {
                $msg_pre = functions::checkout($msg, 1, 1);
                if ($set_user['smileys']) {
                    $msg_pre = functions::smileys($msg_pre, $datauser['rights'] ? 1 : 0);
                }
                $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
                echo '<div class="phdr"><a href="' . $link . '"><b>' . $lng['forum'] . '</b></a> | ' . $lng_forum['edit_message'] . '</div>';
                if ($msg && !isset($_POST['submit'])) {
                    $user = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "' LIMIT 1")->fetch();
                    echo '<div class="list1">' . functions::display_user($user, array('iphide' => 1, 'header' => '<span class="gray">(' . functions::display_date($res['time']) . ')</span>', 'body' => $msg_pre)) . '</div>';
                }
                echo '<div class="rmenu"><form name="form" action="?act=editpost&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><p>';
                echo bbcode::auto_bb('form', 'msg');
                echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . (empty($_POST['msg']) ? _e($res['text']) : _e($_POST['msg'])) . '</textarea><br/>';
                if ($set_user['translit'])
                    echo '<input type="checkbox" name="msgtrans" value="1" ' . (isset($_POST['msgtrans']) ? 'checked="checked" ' : '') . '/> ' . $lng['translit'];
                echo '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '" style="width: 107px; cursor: pointer;"/> ' .
                    ($set_forum['preview'] ? '<input type="submit" value="' . $lng['preview'] . '" style="width: 107px; cursor: pointer;"/>' : '') .
                    '</p></form></div>' .
                    '<div class="phdr"><a href="../pages/faq.php?act=trans">' . $lng['translit'] . '</a> | <a href="../pages/faq.php?act=smileys">' . $lng['smileys'] . '</a></div>' .
                    '<p><a href="' . $link . '">' . $lng['back'] . '</a></p>';
            }
    }
} else {
    /*
    -----------------------------------------------------------------
    Выводим сообщения об ошибках
    -----------------------------------------------------------------
    */
    echo functions::display_error($error);
}