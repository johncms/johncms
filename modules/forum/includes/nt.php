<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Johncms\Api\ConfigInterface $config
 * @var PDO $db
 * @var Johncms\Api\ToolsInterface $tools
 * @var Johncms\Api\UserInterface $user
 */

// Check access
if (! $id
    || ! $user->isValid()
    || isset($user->ban['1'])
    || isset($user->ban['11'])
    || (! $user->rights && $config['mod_forum'] == 3)
) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => _t('Access forbidden'),
            'back_url'      => '?type=topics&amp;id=' . $id,
            'back_url_name' => _t('Go to Section'),
        ]
    );
    exit;
}

// Вспомогательная Функция обработки ссылок форума
function forum_link($m)
{
    global $config, $db;

    if (! isset($m[3])) {
        return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
    }
    $p = parse_url($m[3]);

    if ('http://' . $p['host'] . ($p['path'] ?? '') . '?id=' == $config['homeurl'] . '/forum/?id=') {
        $thid = abs((int) (preg_replace('/(.*?)id=/si', '', $m[3])));
        $req = $db->query("SELECT `text` FROM `forum_topic` WHERE `id`= '${thid}' AND (`deleted` != '1' OR deleted IS NULL)");

        if ($req->rowCount()) {
            $res = $req->fetch();
            $name = strtr(
                $res['text'],
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

// Проверка на флуд
$flood = $tools->antiflood();

if ($flood) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('New Topic'),
            'type'          => 'alert-danger',
            'message'       => sprintf(_t('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
            'back_url'      => '?type=topics&amp;id=' . $id . '&amp;start=' . $start,
            'back_url_name' => _t('Back'),
        ]
    );
    exit;
}

$req_r = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '${id}' AND `section_type` = 1 LIMIT 1");

if (! $req_r->rowCount()) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => _t('New Topic'),
            'type'          => 'alert-danger',
            'message'       => _t('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => _t('Back'),
        ]
    );
    exit;
}

$res_r = $req_r->fetch();

$th = filter_has_var(INPUT_POST, 'th')
    ? mb_substr(filter_var($_POST['th'], FILTER_SANITIZE_SPECIAL_CHARS, ['flag' => FILTER_FLAG_ENCODE_HIGH]), 0, 100)
    : '';

$msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
$msg = preg_replace_callback(
    '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
    'forum_link',
    $msg
);

if (isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
    && $_POST['token'] == $_SESSION['token']
) {
    $error = [];

    if (empty($th)) {
        $error[] = _t('You have not entered topic name');
    }

    if (mb_strlen($th) < 2) {
        $error[] = _t('Topic name too short');
    }

    if (empty($msg)) {
        $error[] = _t('You have not entered the message');
    }

    if (mb_strlen($msg) < 4) {
        $error[] = _t('Text is too short');
    }

    if (! $error) {
        $msg = preg_replace_callback(
            '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            'forum_link',
            $msg
        );

        $sql = 'SELECT (
SELECT COUNT(*) FROM `forum_topic` WHERE `section_id` = ? AND `name` = ?) AS topic, (
SELECT COUNT(*) FROM `forum_messages` WHERE `user_id` = ? AND `text`= ?) AS msg';
        $sth = $db->prepare($sql);
        $sth->execute([$id, $th, $user->id, $msg]);
        $row = $sth->fetch();
        // Прверяем, есть ли уже такая тема в текущем разделе?
        if ($row['topic']) {
            $error[] = _t('Topic with same name already exists in this section');
        }
        // Проверяем, не повторяется ли сообщение?
        if ($row['msg']) {
            $error[] = _t('Message already exists');
        }
    }

    if (! $error) {
        unset($_SESSION['token']);

        // Если задано в настройках, то назначаем топикстартера куратором
        $curator = $res_r['access'] == 1 ? serialize([$user->id => $user->name]) : '';

        $date = new DateTime();
        $date = $date->format('Y-m-d H:i:s');

        // Добавляем тему
        $db->prepare(
            '
          INSERT INTO `forum_topic` SET
          `section_id` = ?,
           `created_at` = ?,
           `user_id` = ?,
           `user_name` = ?,
           `name` = ?,
           `last_post_date` = ?,
           `post_count` = 0,
           `curators` = ?
        '
        )->execute(
            [
                $id,
                $date,
                $user->id,
                $user->name,
                $th,
                time(),
                $curator,
            ]
        );

        /** @var Johncms\Api\EnvironmentInterface $env */
        $env = di(Johncms\Api\EnvironmentInterface::class);
        $rid = $db->lastInsertId();

        // Добавляем текст поста
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
                $rid,
                time(),
                $user->id,
                $user->name,
                $env->getIp(),
                $env->getIpViaProxy(),
                $env->getUserAgent(),
                $msg,
            ]
        );

        $postid = $db->lastInsertId();

        // Пересчитаем топик
        $tools->recountForumTopic($rid);

        // Записываем счетчик постов юзера
        $fpst = $user->postforum + 1;
        $db->exec(
            "UPDATE `users` SET
            `postforum` = '${fpst}',
            `lastpost` = '" . time() . "'
            WHERE `id` = '" . $user->id . "'
        "
        );

        // Ставим метку о прочтении
        $db->exec(
            "INSERT INTO `cms_forum_rdm` SET
            `topic_id`='${rid}',
            `user_id`='" . $user->id . "',
            `time`='" . time() . "'
        "
        );

        if ($_POST['addfiles'] == 1) {
            header("Location: ?id=${postid}&act=addfile");
        } else {
            header("Location: ?type=topic&id=${rid}");
        }
    } else {
        echo $view->render(
            'system::pages/result',
            [
                'title'         => _t('New Topic'),
                'type'          => 'alert-danger',
                'message'       => $error,
                'back_url'      => '/forum/?act=nt&amp;id=' . $id,
                'back_url_name' => _t('Repeat'),
            ]
        );
    }
} else {
    $res_c = $db->query("SELECT * FROM `forum_sections` WHERE `id` = '" . $res_r['parent'] . "'")->fetch();
    $msg_pre = $tools->checkout($msg, 1, 1);
    $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
    $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);

    $nav_chain->add($res_c['name'], '/forum/?' . ($res_c['section_type'] == 1 ? 'type=topics&amp;' : '') . 'id=' . $res_c['id']);
    $nav_chain->add($res_r['name'], '/forum/?' . ($res_r['section_type'] == 1 ? 'type=topics&amp;' : '') . 'id=' . $res_r['id']);
    $nav_chain->add(_t('New Topic'));

    $token = mt_rand(1000, 100000);
    $_SESSION['token'] = $token;

    echo $view->render(
        'forum::new_topic',
        [
            'title'             => _t('New Topic'),
            'page_title'        => _t('New Topic'),
            'settings_forum'    => $set_forum,
            'id'                => $id,
            'token'             => $token,
            'th'                => $th,
            'add_files'         => isset($_POST['addfiles']),
            'msg'               => isset($_POST['msg']) ? $tools->checkout($_POST['msg'], 0, 0) : '',
            'bbcode'            => di(Johncms\Api\BbcodeInterface::class)->buttons('new_topic', 'msg'),
            'back_url'          => '/forum/?' . ($res_r['section_type'] == 1 ? 'type=topics&amp;' : '') . 'id=' . $id,
            'show_post_preview' => $msg && $th && ! isset($_POST['submit']),
            'preview_message'   => $msg_pre,
            'user_avatar'       => $user->getAvatar(),
        ]
    );
}
