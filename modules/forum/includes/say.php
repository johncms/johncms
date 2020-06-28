<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Notifications\Notification;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 */

// Закрываем доступ для определенных ситуаций
if (
    ! $id
    || ! $user->isValid()
    || isset($user->ban[1])
    || isset($user->ban[11])
    || (! $user->rights && $config['mod_forum'] == 3)
) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('New message'),
            'type'          => 'alert-danger',
            'message'       => __('Access forbidden'),
            'back_url'      => '/forum/?type=topic&amp;id=' . $id,
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

// Проверка на флуд
$flood = $tools->antiflood();

// Вспомогательная Функция обработки ссылок форума
function forum_link($m)
{
    global $db, $config;

    if (! isset($m[3])) {
        return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
    }
    $p = parse_url($m[3]);

    if ('http://' . $p['host'] . ($p['path'] ?? '') . '?id=' == $config['homeurl'] . '/forum/?id=') {
        $thid = abs((int) (preg_replace('/(.*?)id=/si', '', $m[3])));
        $req = $db->query("SELECT `name` FROM `forum_topic` WHERE `id`= '${thid}' AND (`deleted` != '1' OR deleted IS NULL)");

        if ($req->rowCount()) {
            $res = $req->fetch();
            $name = strtr(
                $res['name'],
                [
                    '&quot;' => '',
                    '&amp;'  => '',
                    '&lt;'   => '',
                    '&gt;'   => '',
                    '&#039;' => '',
                    '['      => '',
                    ']'      => '',
                ]
            );

            if (mb_strlen($name) > 40) {
                $name = mb_substr($name, 0, 40) . '...';
            }

            return '[url=' . $m[3] . ']' . $name . '[/url]';
        }

        return $m[3];
    }

    return $m[3];
}

$post_type = $_REQUEST['type'] ?? 'post';

switch ($post_type) {
    case 'post':
        if ($flood) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $id . '&amp;start=' . $start,
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $type1 = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'")->fetch();
        // Добавление простого сообщения
        if (($type1['deleted'] == 1 || $type1['closed'] == 1) && $user->rights < 7) {
            // Проверка, закрыта ли тема
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $id,
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        //Обрабатываем ссылки
        $msg = preg_replace_callback(
            '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            'forum_link',
            $msg
        );

        if (
            isset($_POST['submit'])
            && ! empty($_POST['msg'])
            && isset($_POST['token'], $_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => '/forum/?type=topic&amp;id=' . $id,
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum_messages` WHERE `user_id` = '" . $user->id . "' ORDER BY `date` DESC");

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($msg == $res['text']) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Message already exists'),
                            'back_url'      => '/forum/?type=topic&amp;id=' . $id . '&amp;start=' . $start,
                            'back_url_name' => __('Back'),
                        ]
                    );
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            // Проверяем, было ли последнее сообщение от того же автора?
            $req = $db->query(
                'SELECT *, CHAR_LENGTH(`text`) AS `strlen` FROM `forum_messages`
            WHERE `topic_id` = ' . $id . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)") . '
            ORDER BY `date` DESC LIMIT 1'
            );

            $update = false;
            if ($req->rowCount()) {
                $update = true;

                $check_files = false;
                // Если пост текущего пользователя, то проверяем наличие у него файлов
                if ($res['user_id'] == $user->id) {
                    $check_files = $db->query('SELECT id FROM cms_forum_files WHERE post = ' . $res['id'])->rowCount();
                }

                $res = $req->fetch();
                if (
                    ! isset($_POST['addfiles']) &&
                    $res['date'] + 3600 < strtotime('+ 1 hour') &&
                    $res['strlen'] + strlen($msg) < 65536 &&
                    $res['user_id'] == $user->id &&
                    empty($check_files)
                ) {
                    $newpost = $res['text'];

                    if (strpos($newpost, '[timestamp]') === false) {
                        $newpost = '[timestamp]' . date('d.m.Y H:i', $res['date']) . '[/timestamp]' . PHP_EOL . $newpost;
                    }

                    $newpost .= PHP_EOL . PHP_EOL . '[timestamp]' . date('d.m.Y H:i', time()) . '[/timestamp]' . PHP_EOL . $msg;

                    // Обновляем пост
                    $db->prepare(
                        'UPDATE `forum_messages` SET
                      `text` = ?,
                      `date` = ?
                      WHERE `id` = ' . $res['id']
                    )->execute([$newpost, time()]);
                } else {
                    $update = false;
                    /** @var Johncms\System\Http\Environment $env */
                    $env = di(Johncms\System\Http\Environment::class);

                    // Добавляем сообщение в базу
                    $db->prepare(
                        '
                      INSERT INTO `forum_messages` SET
                      `topic_id` = ?,
                      `date` = ?,
                      `user_id` = ?,
                      `user_name` = ?,
                      `ip` = ?,
                      `ip_via_proxy` = ?,
                      `user_agent` = ?,
                      `text` = ?
                    '
                    )->execute(
                        [
                            $id,
                            time(),
                            $user->id,
                            $user->name,
                            $env->getIp(),
                            $env->getIpViaProxy(),
                            $env->getUserAgent(),
                            $msg,
                        ]
                    );

                    $fadd = $db->lastInsertId();
                }
            }

            $cnt_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
            $cnt_all_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${id}'")->fetchColumn();

            // Пересчитываем топик
            $tools->recountForumTopic($id);

            // Обновляем статистику юзера
            $db->exec(
                "UPDATE `users` SET
                `postforum`='" . ($user->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $user->id . "'
            "
            );

            // Вычисляем, на какую страницу попадает добавляемый пост
            if ($user->rights >= 7) {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_all_messages / $user->config->kmess);
            } else {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_messages / $user->config->kmess);
            }

            if (isset($_POST['addfiles'])) {
                $db->query(
                    "INSERT INTO `cms_forum_rdm` (topic_id,  user_id, `time`)
                VALUES ('${id}', '" . $user->id . "', '" . time() . "')
                ON DUPLICATE KEY UPDATE `time` = VALUES(`time`)"
                );
                if ($update) {
                    header('Location: ?type=topic&id=' . $res['id'] . '&act=addfile');
                } else {
                    header('Location: ?type=topic&id=' . $fadd . '&act=addfile');
                }
            } else {
                header('Location: ?type=topic&id=' . $id . '&page=' . $page);
            }
            exit;
        }
        $msg_pre = $tools->checkout($msg, 1, 1);
        $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
        $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);

        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;

        echo $view->render(
            'forum::reply_message',
            [
                'title'             => __('New message'),
                'page_title'        => __('New message'),
                'id'                => $id,
                'bbcode'            => di(Johncms\System\Legacy\Bbcode::class)->buttons('message_form', 'msg'),
                'token'             => $token,
                'topic'             => $type1,
                'form_action'       => '?act=say&amp;type=post&amp;id=' . $id . '&amp;start=' . $start,
                'add_file'          => isset($_POST['addfiles']),
                'msg'               => (empty($_POST['msg']) ? '' : $tools->checkout($msg, 0, 0)),
                'settings_forum'    => $set_forum,
                'show_post_preview' => ($msg && ! isset($_POST['submit'])),
                'back_url'          => '?type=topic&id=' . $id . '&amp;start=' . $start,
                'preview_message'   => $msg_pre,
                'is_new_message'    => true,
            ]
        );
        break;

    case 'reply':
        // Добавление сообщения с цитированием поста
        $type1 = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '${id}'" . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)"))->fetch();

        if (empty($type1)) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('Message not found'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $th1['id'],
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $th = $type1['topic_id'];

        if ($flood) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $th . '&amp;start=' . $start,
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $th1 = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${th}'")->fetch();

        if (($th1['deleted'] == 1 || $th1['closed'] == 1) && $user->rights < 7) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You cannot write in a closed topic'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $th1['id'],
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        if ($type1['user_id'] == $user->id) {
            echo $view->render(
                'system::pages/result',
                [
                    'title'         => __('New message'),
                    'type'          => 'alert-danger',
                    'message'       => __('You can not reply to your own message'),
                    'back_url'      => '/forum/?type=topic&amp;id=' . $th1['id'],
                    'back_url_name' => __('Back'),
                ]
            );
            exit;
        }

        $shift = ($config['timeshift'] + $user->config->timeshift) * 3600;
        $vr = date('d.m.Y / H:i', $type1['date'] + $shift);
        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        $txt = isset($_POST['txt']) ? (int) ($_POST['txt']) : false;

        if (! empty($_POST['citata'])) {
            // Если была цитата, форматируем ее и обрабатываем
            $citata = isset($_POST['citata']) ? trim($_POST['citata']) : '';
            $citata = di(Johncms\System\Legacy\Bbcode::class)->notags($citata);
            $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
            $citata = mb_substr($citata, 0, 200);
            $tp = date('d.m.Y H:i', $type1['date']);
            $msg = '[c][url=' . $config['homeurl'] . '/forum/?act=show_post&id=' .
                $type1['id'] . ']#[/url] [url=' . $config['homeurl'] . '/profile/?user=' . $type1['user_id'] . ']' . $type1['user_name'] . '[/url]'
                . ' ([time]' . $tp . "[/time])\n" . $citata . '[/c]' . $msg;
        } elseif (isset($_POST['txt'])) {
            // Если был ответ, обрабатываем реплику
            switch ($txt) {
                case 2:
                    $repl = $type1['user_name'] . ', ' . __('I am glad to answer you') . ', ';
                    break;

                case 3:
                    $repl = $type1['user_name'] . ', ' . __('respond to Your message') . ' ([url=' . $config['homeurl'] . '/forum/?act=show_post&id=' . $type1['id'] . ']' . $vr . '[/url]): ';
                    break;

                default:
                    $repl = $type1['user_name'] . ', ';
            }
            $msg = $repl . ' ' . $msg;
        }

        //Обрабатываем ссылки
        $msg = preg_replace_callback(
            '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            'forum_link',
            $msg
        );

        if (
            isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            if (empty($_POST['msg'])) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('You have not entered the message'),
                        'back_url'      => '/forum/?type=reply&amp;act=say&amp;id=' . $th . (isset($_GET['cyt']) ? '&amp;cyt' : ''),
                        'back_url_name' => __('Repeat'),
                    ]
                );
                exit;
            }

            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('New message'),
                        'type'          => 'alert-danger',
                        'message'       => __('Text is too short'),
                        'back_url'      => '/forum/?type=topic&amp;id=' . $id,
                        'back_url_name' => __('Back'),
                    ]
                );
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum_messages` WHERE `user_id` = '" . $user->id . "' ORDER BY `date` DESC LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();

                if ($msg == $res['text']) {
                    echo $view->render(
                        'system::pages/result',
                        [
                            'title'         => __('New message'),
                            'type'          => 'alert-danger',
                            'message'       => __('Message already exists'),
                            'back_url'      => '/forum/?type=topic&amp;id=' . $th . '&amp;start=' . $start,
                            'back_url_name' => __('Back'),
                        ]
                    );
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $th) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            /** @var Johncms\System\Http\Environment $env */
            $env = di(Johncms\System\Http\Environment::class);

            // Добавляем сообщение в базу
            $db->prepare(
                '
              INSERT INTO `forum_messages` SET
              `topic_id` = ?,
              `date` = ?,
              `user_id` = ?,
              `user_name` = ?,
              `ip` = ?,
              `ip_via_proxy` = ?,
              `user_agent` = ?,
              `text` = ?
            '
            )->execute(
                [
                    $th,
                    time(),
                    $user->id,
                    $user->name,
                    $env->getIp(),
                    $env->getIpViaProxy(),
                    $env->getUserAgent(),
                    $msg,
                ]
            );

            $fadd = $db->lastInsertId();

            $cnt_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${th}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
            $cnt_all_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${th}'")->fetchColumn();

            // Обновляем статистику юзера
            $db->exec(
                "UPDATE `users` SET
                `postforum`='" . ($user->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $user->id . "'"
            );

            $tools->recountForumTopic($th);

            // Добавляем уведомление об ответе
            $preview_message = strip_tags($tools->checkout(trim($_POST['msg']), 1, 1));
            $preview_message = strlen($preview_message) > 200 ? mb_substr($preview_message, 0, 200) . '...' : $preview_message;
            $preview_message = $tools->smilies($preview_message, ($user->rights > 0));
            (new Notification())->create(
                [
                    'module'     => 'forum',
                    'event_type' => 'new_message',
                    'user_id'    => $type1['user_id'],
                    'sender_id'  => $user->id,
                    'entity_id'  => $fadd,
                    'fields'     => [
                        'topic_name'       => htmlspecialchars($th1['name']),
                        'user_name'        => htmlspecialchars($user->name),
                        'topic_url'        => '/forum/?type=topic&amp;id=' . $th,
                        'reply_to_message' => '/forum/?act=show_post&id=' . $type1['id'],
                        'message'          => $preview_message,
                        'post_id'          => $fadd,
                        'topic_id'         => $th,
                    ],
                ]
            );

            // Вычисляем, на какую страницу попадает добавляемый пост
            if ($user->rights >= 7) {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_all_messages / $user->config->kmess);
            } else {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_messages / $user->config->kmess);
            }

            if (isset($_POST['addfiles'])) {
                header("Location: ?type=topic&id=${fadd}&act=addfile");
            } else {
                header("Location: ?type=topic&id=${th}&page=${page}");
            }
            exit;
        }
        $qt = $type1['text'];
        $msg_pre = $tools->checkout($msg, 1, 1);
        $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
        $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
        $qt = str_replace('<br>', "\r\n", $qt);
        $qt = trim(preg_replace('#\[c\](.*?)\[/c\]#si', '', $qt));
        $qt = $tools->checkout($qt, 0, 2);

        $type1['time_formatted'] = $vr;

        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;

        echo $view->render(
            'forum::reply_message',
            [
                'title'             => __('Reply to message'),
                'page_title'        => __('Reply to message'),
                'id'                => $id,
                'bbcode'            => di(Johncms\System\Legacy\Bbcode::class)->buttons('message_form', 'msg'),
                'token'             => $token,
                'topic'             => $th1,
                'form_action'       => '/forum/?act=say&amp;type=reply&amp;id=' . $id . '&amp;start=' . $start . (isset($_GET['cyt']) ? '&amp;cyt' : ''),
                'txt'               => $txt ?? null,
                'is_quote'          => isset($_GET['cyt']),
                'add_file'          => isset($_POST['addfiles']),
                'msg'               => (empty($_POST['msg']) ? '' : $tools->checkout($msg, 0, 0)),
                'quote_msg'         => empty($_POST['citata']) ? $qt : $tools->checkout($_POST['citata'], 0, 0),
                'message'           => $type1,
                'settings_forum'    => $set_forum,
                'show_post_preview' => (! empty($_POST['msg']) && ! isset($_POST['submit'])),
                'back_url'          => '?type=topic&id=' . $th1['id'] . '&amp;start=' . $start,
                'preview_message'   => $msg_pre,
                'is_new_message'    => false,
            ]
        );
        break;

    default:
        echo $view->render(
            'system::pages/result',
            [
                'title'         => __('New message'),
                'type'          => 'alert-danger',
                'message'       => __('Topic has been deleted or does not exists'),
                'back_url'      => '/forum/',
                'back_url_name' => __('Forum'),
            ]
        );
}
