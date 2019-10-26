<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

defined('_IN_JOHNCMS') || die('Error: restricted access');

require 'system/head.php';

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

if (! $systemUser->isValid() || ! $id) {
    echo $tools->displayError(_t('Wrong data'));
    require 'system/end.php';
    exit;
}

$req = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '${id}' " . ($systemUser->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)"));

if ($req->rowCount()) {
    // Предварительные проверки
    $res = $req->fetch();

    $topic = $db->query('SELECT `section_id`, `curators` FROM `forum_topic` WHERE `id` = ' . $res['topic_id'])->fetch();
    $curators = ! empty($topic['curators']) ? unserialize($topic['curators']) : [];

    if (array_key_exists($systemUser->id, $curators)) {
        $systemUser->rights = 3;
    }

    $page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '${id}'" . ($systemUser->rights < 7 ? " AND (`deleted` != '1' OR deleted IS NULL)" : ''))->fetchColumn() / $kmess);
    $posts = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND (`deleted` != '1' OR deleted IS NULL)")->fetchColumn();
    $link = 'index.php?type=topic&id=' . $res['topic_id'] . '&page=' . $page;
    $error = false;

    if ($systemUser->rights == 3 || $systemUser->rights >= 6) {
        // Проверка для Администрации
        if ($res['user_id'] != $systemUser->id) {
            $req_u = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

            if ($req_u->rowCount()) {
                $res_u = $req_u->fetch();

                if ($res_u['rights'] > $systemUser->rights) {
                    $error = _t('You cannot edit posts of higher administration') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
                }
            }
        }
    } else {
        // Проверка для обычных юзеров
        if ($res['user_id'] != $systemUser->id) {
            $error = _t('You are trying to change another\'s post') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
        }

        if (! $error) {
            $section = $db->query('SELECT * FROM `forum_sections` WHERE `id` = ' . $topic['section_id'])->fetch();
            $allow = ! empty($section['access']) ? (int) ($section['access']) : 0;
            $check = true;

            if ($allow == 2) {
                $first = $db->query("SELECT * FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' ORDER BY `id` ASC LIMIT 1")->fetch();

                if ($first['user_id'] == $systemUser->id && $first['id'] == $id) {
                    $check = false;
                }
            }

            if ($check) {
                $res_m = $db->query("SELECT * FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND (`deleted` != 1 OR deleted IS NULL) ORDER BY `id` DESC LIMIT 1")->fetch();

                if ($res_m['user_id'] != $systemUser->id) {
                    $error = _t('Your message not already latest, you cannot change it') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
                } elseif ($res['date'] < time() - 300
                    && $res_m['user_id'] != $systemUser->id && $res_m['date'] + 3600 > strtotime('+ 1 hour')
                ) {
                    $error = _t('You cannot edit your posts after 5 minutes') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>';
                }
            }
        }
    }
} else {
    $error = _t('Message does not exists or has been deleted') . '<br /><a href="index.php">' . _t('Forum') . '</a>';
}

$fid = isset($_GET['fid']) && $_GET['fid'] > 0 ? abs((int) ($_GET['fid'])) : false;

if (! $error) {
    switch ($do) {
        case 'restore':
            // Восстановление удаленного поста
            $req_u = $db->query("SELECT `postforum` FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

            if ($req_u->rowCount()) {
                // Добавляем один балл к счетчику постов юзера
                $res_u = $req_u->fetch();
                $db->exec("UPDATE `users` SET `postforum` = '" . ($res_u['postforum'] + 1) . "' WHERE `id` = '" . $res['user_id'] . "'");
            }

            $db->exec('UPDATE `forum_messages` SET `deleted` = NULL, `deleted_by` = ' . $db->quote($systemUser->name) . " WHERE `id` = '${id}'");
            $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '${id}'");

            if ($req_f->rowCount()) {
                $db->exec("UPDATE `cms_forum_files` SET `del` = '0' WHERE `post` = '${id}'");
            }
            $tools->recountForumTopic($res['topic_id']);
            header('Location: ' . $link);
            break;

        case 'delfile':
            // Удаление поста, предварительное напоминание
            echo '<div class="phdr"><a href="' . $link . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Delete file') . '</div>'
                . '<div class="rmenu"><p>'
                . _t('Do you really want to delete?') . '</p>'
                . '<form method="post" action="index.php?act=editpost&amp;do=deletefile&amp;fid=' . $fid . '&amp;id=' . $id . '"><input type="submit" name="delfile" value="' . _t('Delete') . '" /></form>'
                . '<p><a href="' . $link . '">' . _t('Cancel') . '</a></p>'
                . '</div>';
            break;

        case 'deletefile':
            if (isset($_POST['delfile'])) {
                $req_f = $db->query('SELECT * FROM `cms_forum_files` WHERE `id` = ' . $fid);
                $res_f = $req_f->fetch();

                if ($req_f->rowCount()) {
                    $db->exec('DELETE FROM `cms_forum_files` WHERE `id` = ' . $fid);
                    unlink(UPLOAD_PATH . 'forum/attach/' . $res_f['filename']); //TODO: Разобраться с путем
                    header('Location: ' . $link);
                } else {
                    echo $tools->displayError(_t('You cannot edit your posts after 5 minutes') . '<br /><a href="' . $link . '">' . _t('Back') . '</a>');
                    require 'system/end.php';
                    exit;
                }
            }
            break;

        case 'delete':
            // Удаление поста и прикрепленного файла
            if ($res['deleted'] != 1) {
                $req_u = $db->query("SELECT `postforum` FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

                if ($req_u->rowCount()) {
                    // Вычитаем один балл из счетчика постов юзера
                    $res_u = $req_u->fetch();
                    $postforum = $res_u['postforum'] > 0 ? $res_u['postforum'] - 1 : 0;
                    $db->exec("UPDATE `users` SET `postforum` = '" . $postforum . "' WHERE `id` = '" . $res['user_id'] . "'");
                }
            }

            if ($systemUser->rights == 9 && ! isset($_GET['hide'])) {
                // Удаление поста (для Супервизоров)
                $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '${id}'");

                if ($req_f->rowCount()) {
                    // Если есть прикрепленные файлы, удаляем их
                    while ($res_f = $req_f->fetch()) {
                        unlink(UPLOAD_PATH . 'forum/attach/' . $res_f['filename']);
                    }
                }
                $db->exec('DELETE FROM `cms_forum_files` WHERE `post` = ' . $id);

                // Формируем ссылку на нужную страницу темы
                $page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>' : '<') . " '${id}'")->fetchColumn() / $kmess);
                $db->exec("DELETE FROM `forum_messages` WHERE `id` = '${id}'");

                if ($posts < 2) {
                    // Пересылка на удаление всей темы
                    header('Location: index.php?act=deltema&id=' . $res['topic_id']);
                } else {
                    header('Location: index.php?type=topic&id=' . $res['topic_id'] . '&page=' . $page);
                }
            } else {
                // Скрытие поста
                $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '${id}'");

                if ($req_f->rowCount()) {
                    // Если есть прикрепленные файлы, скрываем их
                    $db->exec("UPDATE `cms_forum_files` SET `del` = '1' WHERE `post` = '${id}'");
                }

                if ($posts == 1) {
                    // Если это был последний пост темы, то скрываем саму тему
                    $res_l = $db->query("SELECT `section_id` FROM `forum_topic` WHERE `id` = '" . $res['topic_id'] . "'")->fetch();
                    $db->exec("UPDATE `forum_topic` SET `deleted` = '1', `deleted_by` = '" . $systemUser->name . "' WHERE `id` = '" . $res['topic_id'] . "'");

                    header('Location: index.php?type=topics&id=' . $res_l['section_id']);
                } else {
                    $db->exec("UPDATE `forum_messages` SET `deleted` = '1', `deleted_by` = '" . $systemUser->name . "' WHERE `id` = '${id}'");
                    // Пересчитываем топик
                    $tools->recountForumTopic($res['topic_id']);
                    header('Location: index.php?type=topic&id=' . $res['topic_id'] . '&page=' . $page);
                }
            }

            // Пересчитываем топик
            $tools->recountForumTopic($res['topic_id']);

            break;

        case 'del':
            // Удаление поста, предварительное напоминание
            echo '<div class="phdr"><a href="' . $link . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Delete Message') . '</div>' .
                '<div class="rmenu"><p>';

            if ($posts == 1) {
                echo _t('WARNING!<br>This is last post of topic. By deleting this post topic will be deleted (or hidden) too') . '<br>';
            }

            echo _t('Do you really want to delete?') . '</p>' .
                '<p><a href="' . $link . '">' . _t('Cancel') . '</a> | <a href="index.php?act=editpost&amp;do=delete&amp;id=' . $id . '">' . _t('Delete') . '</a>';

            if ($systemUser->rights == 9) {
                echo ' | <a href="index.php?act=editpost&amp;do=delete&amp;hide&amp;id=' . $id . '">' . _t('Hide') . '</a>';
            }

            echo '</p></div>';
            echo '<div class="phdr"><small>' . _t('After deleting, one point will be subtracted from the counter of forum posts') . '</small></div>';
            break;

        default:
            // Редактирование поста
            $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';

            if (isset($_POST['submit'])) {
                if (empty($_POST['msg'])) {
                    echo $tools->displayError(_t('You have not entered the message'), '<a href="index.php?act=editpost&amp;id=' . $id . '">' . _t('Repeat') . '</a>');
                    require 'system/end.php';
                    exit;
                }

                $db->prepare('
                  UPDATE `forum_messages` SET
                  `edit_time` = ?,
                  `editor_name` = ?,
                  `edit_count` = ?,
                  `text` = ?
                  WHERE `id` = ?
                ')->execute([
                    time(),
                    $systemUser->name,
                    ($res['edit_count'] + 1),
                    $msg,
                    $id,
                ]);

                header('Location: index.php?type=topic&id=' . $res['topic_id'] . '&page=' . $page);
            } else {
                $msg_pre = $tools->checkout($msg, 1, 1);
                $msg_pre = $tools->smilies($msg_pre, $systemUser->rights ? 1 : 0);
                $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
                echo '<div class="phdr"><a href="' . $link . '"><b>' . _t('Forum') . '</b></a> | ' . _t('Edit Message') . '</div>';

                if ($msg && ! isset($_POST['submit'])) {
                    $user = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "' LIMIT 1")->fetch();
                    echo '<div class="list1">' . $tools->displayUser($user, ['iphide' => 1, 'header' => '<span class="gray">(' . $tools->displayDate($res['time']) . ')</span>', 'body' => $msg_pre]) . '</div>';
                }

                echo '<div class="rmenu"><form name="form" action="?act=editpost&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><p>';
                echo App::getContainer()->get(Johncms\Api\BbcodeInterface::class)->buttons('form', 'msg');
                echo '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="msg">' . (empty($_POST['msg']) ? htmlentities($res['text'], ENT_QUOTES, 'UTF-8') : $tools->checkout($_POST['msg'])) . '</textarea><br>';

                echo '</p><p><input type="submit" name="submit" value="' . _t('Save') . '" style="width: 107px; cursor: pointer;"/> ' .
                    ($set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer;"/>' : '') .
                    '</p></form></div>' .
                    '<div class="phdr"><a href="../help/?act=smileys">' . _t('Smilies') . '</a></div>' .
                    '<p><a href="' . $link . '">' . _t('Back') . '</a></p>';
            }
    }
} else {
    // Выводим сообщения об ошибках
    echo $tools->displayError($error);
}
