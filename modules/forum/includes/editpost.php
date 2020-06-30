<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Users\User;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

if (! $user->isValid() || ! $id) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Edit Message'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

$req = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '${id}' " . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)"));

if ($req->rowCount()) {
    // Предварительные проверки
    $res = $req->fetch();

    $topic = $db->query('SELECT `section_id`, `curators` FROM `forum_topic` WHERE `id` = ' . $res['topic_id'])->fetch();
    $curators = ! empty($topic['curators']) ? unserialize($topic['curators'], ['allowed_classes' => false]) : [];

    if (array_key_exists($user->id, $curators)) {
        $user->rights = 3;
    }

    $page = ceil(
        $db->query(
            "SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>=' : '<=') . " '${id}'" . ($user->rights < 7 ? " AND (`deleted` != '1' OR deleted IS NULL)" : '')
        )->fetchColumn() / $user->config->kmess
    );
    $posts = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND (`deleted` != '1' OR deleted IS NULL)")->fetchColumn();
    $link = '?type=topic&id=' . $res['topic_id'] . '&page=' . $page;
    $error = false;

    if ($user->rights == 3 || $user->rights >= 6) {
        // Проверка для Администрации
        if ($res['user_id'] != $user->id) {
            $req_u = $db->query("SELECT * FROM `users` WHERE `id` = '" . $res['user_id'] . "'");

            if ($req_u->rowCount()) {
                $res_u = $req_u->fetch();

                if ($res_u['rights'] > $user->rights) {
                    $error = __('You cannot edit posts of higher administration') . '<br /><a href="' . $link . '">' . __('Back') . '</a>';
                }
            }
        }
    } else {
        // Проверка для обычных юзеров
        if ($res['user_id'] != $user->id) {
            $error = __('You are trying to change another\'s post') . '<br /><a href="' . $link . '">' . __('Back') . '</a>';
        }

        if (! $error) {
            $section = $db->query('SELECT * FROM `forum_sections` WHERE `id` = ' . $topic['section_id'])->fetch();
            $allow = ! empty($section['access']) ? (int) ($section['access']) : 0;
            $check = true;

            if ($allow == 2) {
                $first = $db->query("SELECT * FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' ORDER BY `id` ASC LIMIT 1")->fetch();

                if ($first['user_id'] == $user->id && $first['id'] == $id) {
                    $check = false;
                }
            }

            if ($check) {
                $res_m = $db->query("SELECT * FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND (`deleted` != 1 OR deleted IS NULL) ORDER BY `id` DESC LIMIT 1")->fetch();

                if ($res_m['user_id'] != $user->id) {
                    $error = __('Your message not already latest, you cannot change it') . '<br /><a href="' . $link . '">' . __('Back') . '</a>';
                } elseif (
                    $res['date'] < time() - 300
                    && $res_m['user_id'] != $user->id && $res_m['date'] + 3600 > strtotime('+ 1 hour')
                ) {
                    $error = __('You cannot edit your posts after 5 minutes') . '<br /><a href="' . $link . '">' . __('Back') . '</a>';
                }
            }
        }
    }
} else {
    $error = __('Message does not exists or has been deleted') . '<br><a href="./">' . __('Forum') . '</a>';
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

            $db->exec('UPDATE `forum_messages` SET `deleted` = NULL, `deleted_by` = ' . $db->quote($user->name) . " WHERE `id` = '${id}'");
            $req_f = $db->query("SELECT * FROM `cms_forum_files` WHERE `post` = '${id}'");

            if ($req_f->rowCount()) {
                $db->exec("UPDATE `cms_forum_files` SET `del` = '0' WHERE `post` = '${id}'");
            }
            $tools->recountForumTopic($res['topic_id']);
            header('Location: ' . $link);
            break;

        case 'delfile':
            echo $view->render(
                'forum::delete_file',
                [
                    'title'      => __('Delete file'),
                    'page_title' => __('Delete file'),
                    'id'         => $id,
                    'fid'        => $fid,
                    'back_url'   => $link,
                ]
            );
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
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'    => __('Edit Message'),
                            'type'     => 'alert-danger',
                            'message'  => __('You cannot edit your posts after 5 minutes'),
                            'back_url' => $link,
                        ]
                    );
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

            if ($user->rights == 9 && ! isset($_GET['hide'])) {
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
                $page = ceil($db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '" . $res['topic_id'] . "' AND `id` " . ($set_forum['upfp'] ? '>' : '<') . " '${id}'")->fetchColumn() / $user->config->kmess);
                $db->exec("DELETE FROM `forum_messages` WHERE `id` = '${id}'");

                if ($posts < 2) {
                    // Пересылка на удаление всей темы
                    header('Location: ?act=deltema&id=' . $res['topic_id']);
                } else {
                    header('Location: ?type=topic&id=' . $res['topic_id'] . '&page=' . $page);
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
                    $db->exec("UPDATE `forum_topic` SET `deleted` = '1', `deleted_by` = '" . $user->name . "' WHERE `id` = '" . $res['topic_id'] . "'");

                    header('Location: ?type=topics&id=' . $res_l['section_id']);
                } else {
                    $db->exec("UPDATE `forum_messages` SET `deleted` = '1', `deleted_by` = '" . $user->name . "' WHERE `id` = '${id}'");
                    // Пересчитываем топик
                    $tools->recountForumTopic($res['topic_id']);
                    header('Location: ?type=topic&id=' . $res['topic_id'] . '&page=' . $page);
                }
            }

            // Пересчитываем топик
            $tools->recountForumTopic($res['topic_id']);
            break;

        case 'del':
            echo $view->render(
                'forum::delete_post',
                [
                    'title'      => __('Delete Message'),
                    'page_title' => __('Delete Message'),
                    'id'         => $id,
                    'posts'      => $posts,
                    'back_url'   => $link,
                ]
            );
            break;

        default:
            // Редактирование поста
            $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';

            if (isset($_POST['submit'])) {
                if (empty($_POST['msg'])) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => __('Edit Message'),
                            'type'          => 'alert-danger',
                            'message'       => __('You have not entered the message'),
                            'back_url'      => '/forum/?act=editpost&amp;id=' . $id,
                            'back_url_name' => __('Repeat'),
                        ]
                    );
                    exit;
                }

                $db->prepare(
                    '
                  UPDATE `forum_messages` SET
                  `edit_time` = ?,
                  `editor_name` = ?,
                  `edit_count` = ?,
                  `text` = ?
                  WHERE `id` = ?
                '
                )->execute(
                    [
                        time(),
                        $user->name,
                        ($res['edit_count'] + 1),
                        $msg,
                        $id,
                    ]
                );

                header('Location: ?type=topic&id=' . $res['topic_id'] . '&page=' . $page);
                exit;
            }
            $msg_pre = $tools->checkout($msg, 1, 1);
            $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
            $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);

            if ($msg && ! isset($_POST['submit'])) {
                $foundUser = (new User())->find($res['user_id']);
            }

            $message = (empty($_POST['msg']) ? htmlentities($res['text'], ENT_QUOTES, 'UTF-8') : $tools->checkout($_POST['msg'], 0, 0));

            echo $view->render(
                'forum::edit_post',
                [
                    'title'             => __('Edit Message'),
                    'page_title'        => __('Edit Message'),
                    'id'                => $id,
                    'bbcode'            => di(Johncms\System\Legacy\Bbcode::class)->buttons('edit_post', 'msg'),
                    'msg'               => $message,
                    'start'             => $start,
                    'back_url'          => $link,
                    'settings_forum'    => $set_forum,
                    'show_post_preview' => $msg && ! isset($_POST['submit']),
                    'preview_message'   => $msg_pre,
                    'message_author'    => $foundUser ?? null,
                ]
            );
    }
    exit;
}
echo $view->render(
    'system::pages/result',
    [
        'title'   => __('Error'),
        'type'    => 'alert-danger',
        'message' => $error,
    ]
);
