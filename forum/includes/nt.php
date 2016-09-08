<?php

defined('_IN_JOHNCMS') or die('Error: restricted access');

// Закрываем доступ для определенных ситуаций
if (!$id || !$user_id || isset($ban['1']) || isset($ban['11']) || (!core::$user_rights && $set['mod_forum'] == 3)) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['access_forbidden']);
    require('../incfiles/end.php');
    exit;
}

/** @var PDO $db */
$db = App::getContainer()->get(PDO::class);

// Вспомогательная Функция обработки ссылок форума
function forum_link($m)
{
    global $set, $db;

    if (!isset($m[3])) {
        return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
    } else {
        $p = parse_url($m[3]);

        if ('http://' . $p['host'] . (isset($p['path']) ? $p['path'] : '') . '?id=' == $set['homeurl'] . '/forum/index.php?id=') {
            $thid = abs(intval(preg_replace('/(.*?)id=/si', '', $m[3])));
            $req = $db->query("SELECT `text` FROM `forum` WHERE `id`= '$thid' AND `type` = 't' AND `close` != '1'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $name = strtr($res['text'], [
                    '&quot;' => '',
                    '&amp;'  => '',
                    '&lt;'   => '',
                    '&gt;'   => '',
                    '&#039;' => '',
                    '['      => '',
                    ']'      => '',
                ]);

                if (mb_strlen($name) > 40) {
                    $name = mb_substr($name, 0, 40) . '...';
                }

                return '[url=' . $m[3] . ']' . $name . '[/url]';
            } else {
                return $m[3];
            }
        } else {
            return $m[3];
        }
    }
}

// Проверка на флуд
$flood = functions::antiflood();

if ($flood) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['error_flood'] . ' ' . $flood . $lng['sec'] . ', <a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . $lng['back'] . '</a>');
    require('../incfiles/end.php');
    exit;
}

$req_r = $db->query("SELECT * FROM `forum` WHERE `id` = '$id' AND `type` = 'r' LIMIT 1");

if (!$req_r->rowCount()) {
    require('../incfiles/head.php');
    echo functions::display_error($lng['error_wrong_data']);
    require('../incfiles/end.php');
    exit;
}

$res_r = $req_r->fetch();
$th = isset($_POST['th']) ? mb_substr(trim($_POST['th']), 0, 100) : '';
$msg = isset($_POST['msg']) ? functions::checkin(trim($_POST['msg'])) : '';
$msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);

if (isset($_POST['submit'])
    && isset($_POST['token'])
    && isset($_SESSION['token'])
    && $_POST['token'] == $_SESSION['token']
) {
    $error = [];

    if (empty($th)) {
        $error[] = $lng_forum['error_topic_name'];
    }

    if (mb_strlen($th) < 2) {
        $error[] = $lng_forum['error_topic_name_lenght'];
    }

    if (empty($msg)) {
        $error[] = $lng['error_empty_message'];
    }

    if (mb_strlen($msg) < 4) {
        $error[] = $lng['error_message_short'];
    }

    if (!$error) {
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);

        // Прверяем, есть ли уже такая тема в текущем разделе?
        if ($db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 't' AND `refid` = '$id' AND `text` = '$th'")->fetchColumn() > 0) {
            $error[] = $lng_forum['error_topic_exists'];
        }

        // Проверяем, не повторяется ли сообщение?
        $req = $db->query("SELECT * FROM `forum` WHERE `user_id` = '$user_id' AND `type` = 'm' ORDER BY `time` DESC");

        if ($req->rowCount()) {
            $res = $req->fetch();

            if ($msg == $res['text']) {
                $error[] = $lng['error_message_exists'];
            }
        }
    }

    if (!$error) {
        unset($_SESSION['token']);

        // Если задано в настройках, то назначаем топикстартера куратором
        $curator = $res_r['edit'] == 1 ? serialize([$user_id => $login]) : '';

        // Добавляем тему
        $db->prepare('
          INSERT INTO `forum` SET
          `refid` = ?,
          `type` = \'t\',
           `time` = ?,
           `user_id` = ?,
           `from` = ?,
           `text` = ?,
           `soft` = \'\',
           `edit` = \'\',
           `curators` = ?
        ')->execute([
            $id,
            time(),
            $user_id,
            $login,
            $th,
            $curator,
        ]);

        $rid = $db->lastInsertId();

        // Добавляем текст поста
        $db->prepare('
          INSERT INTO `forum` SET
          `refid` = ?,
          `type` = \'m\',
          `time` = ?,
          `user_id` = ?,
          `from` = ?,
          `ip` = ?,
          `ip_via_proxy` = ?,
          `soft` = ?,
          `text` = ?,
          `edit` = \'\',
          `curators` = \'\'
        ')->execute([
            $rid,
            time(),
            $user_id,
            $login,
            core::$ip,
            core::$ip_via_proxy,
            $agn,
            $msg,
        ]);

        $postid = $db->lastInsertId();

        // Записываем счетчик постов юзера
        $fpst = $datauser['postforum'] + 1;
        $db->exec("UPDATE `users` SET
            `postforum` = '$fpst',
            `lastpost` = '" . time() . "'
            WHERE `id` = '$user_id'
        ");

        // Ставим метку о прочтении
        $db->exec("INSERT INTO `cms_forum_rdm` SET
            `topic_id`='$rid',
            `user_id`='$user_id',
            `time`='" . time() . "'
        ");

        if ($_POST['addfiles'] == 1) {
            header("Location: index.php?id=$postid&act=addfile");
        } else {
            header("Location: index.php?id=$rid");
        }
    } else {
        // Выводим сообщение об ошибке
        require('../incfiles/head.php');
        echo functions::display_error($error, '<a href="index.php?act=nt&amp;id=' . $id . '">' . $lng['repeat'] . '</a>');
        require('../incfiles/end.php');
        exit;
    }
} else {
    $res_c = $db->query("SELECT * FROM `forum` WHERE `id` = '" . $res_r['refid'] . "'")->fetch();
    require('../incfiles/head.php');

    if ($datauser['postforum'] == 0) {
        if (!isset($_GET['yes'])) {
            $lng_faq = core::load_lng('faq');
            echo '<p>' . $lng_faq['forum_rules_text'] . '</p>';
            echo '<p><a href="index.php?act=nt&amp;id=' . $id . '&amp;yes">' . $lng_forum['agree'] . '</a> | <a href="index.php?id=' . $id . '">' . $lng_forum['not_agree'] . '</a></p>';
            require('../incfiles/end.php');
            exit;
        }
    }

    $msg_pre = functions::checkout($msg, 1, 1);

    if ($set_user['smileys']) {
        $msg_pre = functions::smileys($msg_pre, $datauser['rights'] ? 1 : 0);
    }

    $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
    echo '<div class="phdr"><a href="index.php?id=' . $id . '"><b>' . _t('Forum') . '</b></a> | ' . $lng_forum['new_topic'] . '</div>';

    if ($msg && $th && !isset($_POST['submit'])) {
        echo '<div class="list1">' . functions::image('op.gif') . '<span style="font-weight: bold">' . $th . '</span></div>' .
            '<div class="list2">' . functions::display_user($datauser, ['iphide' => 1, 'header' => '<span class="gray">(' . functions::display_date(time()) . ')</span>', 'body' => $msg_pre]) . '</div>';
    }

    echo '<form name="form" action="index.php?act=nt&amp;id=' . $id . '" method="post">' .
        '<div class="gmenu">' .
        '<p><h3>' . $lng['section'] . '</h3>' .
        '<a href="index.php?id=' . $res_c['id'] . '">' . $res_c['text'] . '</a> | <a href="index.php?id=' . $res_r['id'] . '">' . $res_r['text'] . '</a></p>' .
        '<p><h3>' . $lng_forum['new_topic_name'] . '</h3>' .
        '<input type="text" size="20" maxlength="100" name="th" value="' . $th . '"/></p>' .
        '<p><h3>' . $lng_forum['post'] . '</h3>';
    echo '</p><p>' . bbcode::auto_bb('form', 'msg');
    echo '<textarea rows="' . $set_user['field_h'] . '" name="msg">' . (isset($_POST['msg']) ? functions::checkout($_POST['msg']) : '') . '</textarea></p>' .
        '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . $lng_forum['add_file'];

    if ($set_user['translit']) {
        echo '<br /><input type="checkbox" name="msgtrans" value="1" ' . (isset($_POST['msgtrans']) ? 'checked="checked" ' : '') . '/> ' . $lng['translit'];
    }

    $token = mt_rand(1000, 100000);
    $_SESSION['token'] = $token;
    echo '</p><p><input type="submit" name="submit" value="' . $lng['save'] . '" style="width: 107px; cursor: pointer;"/> ' .
        ($set_forum['preview'] ? '<input type="submit" value="' . $lng['preview'] . '" style="width: 107px; cursor: pointer;"/>' : '') .
        '<input type="hidden" name="token" value="' . $token . '"/>' .
        '</p></div></form>' .
        '<div class="phdr"><a href="../help/?act=smileys">' . $lng['smileys'] . '</a></div>' .
        '<p><a href="index.php?id=' . $id . '">' . $lng['back'] . '</a></p>';
}
