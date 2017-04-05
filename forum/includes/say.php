<?php
/*
 * JohnCMS NEXT Mobile Content Management System (http://johncms.com)
 *
 * For copyright and license information, please see the LICENSE.md
 * Installing the system or redistributions of files must retain the above copyright notice.
 *
 * @link        http://johncms.com JohnCMS Project
 * @copyright   Copyright (C) JohnCMS Community
 * @license     GPL-3
 */

defined('_IN_JOHNCMS') or die('Error: restricted access');

/** @var Psr\Container\ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var Johncms\Api\UserInterface $systemUser */
$systemUser = $container->get(Johncms\Api\UserInterface::class);

/** @var Johncms\Api\ToolsInterface $tools */
$tools = $container->get(Johncms\Api\ToolsInterface::class);

/** @var Johncms\Api\ConfigInterface $config */
$config = $container->get(Johncms\Api\ConfigInterface::class);

// Закрываем доступ для определенных ситуаций
if (!$id
    || !$systemUser->isValid()
    || isset($systemUser->ban[1])
    || isset($systemUser->ban[11])
    || (!$systemUser->rights && $config['mod_forum'] == 3)
) {
    require('../system/head.php');
    echo $tools->displayError(_t('Access forbidden'));
    require('../system/end.php');
    exit;
}

// Вспомогательная Функция обработки ссылок форума
function forum_link($m)
{
    global $db, $config;

    if (!isset($m[3])) {
        return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
    } else {
        $p = parse_url($m[3]);

        if ('http://' . $p['host'] . (isset($p['path']) ? $p['path'] : '') . '?id=' == $config->homeurl . '/forum/index.php?id=') {
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
$flood = $tools->antiflood();

if ($flood) {
    require('../system/head.php');
    echo $tools->displayError(sprintf(_t('You cannot add the message so often<br>Please, wait %d sec.'), $flood), '<a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a>');
    require('../system/end.php');
    exit;
}

$headmod = 'forum,' . $id . ',1';
$type1 = $db->query("SELECT * FROM `forum` WHERE `id` = '$id'")->fetch();

switch ($type1['type']) {
    case 't':
        // Добавление простого сообщения
        if (($type1['edit'] == 1 || $type1['close'] == 1) && $systemUser->rights < 7) {
            // Проверка, закрыта ли тема
            require('../system/head.php');
            echo $tools->displayError(_t('You cannot write in a closed topic'), '<a href="index.php?id=' . $id . '">' . _t('Back') . '</a>');
            require('../system/end.php');
            exit;
        }

        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        //Обрабатываем ссылки
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);

        if (isset($_POST['submit'])
            && !empty($_POST['msg'])
            && isset($_POST['token'])
            && isset($_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                require('../system/head.php');
                echo $tools->displayError(_t('Text is too short'), '<a href="index.php?id=' . $id . '">' . _t('Back') . '</a>');
                require('../system/end.php');
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum` WHERE `user_id` = '" . $systemUser->id . "' AND `type` = 'm' ORDER BY `time` DESC");

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($msg == $res['text']) {
                    require('../system/head.php');
                    echo $tools->displayError(_t('Message already exists'), '<a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a>');
                    require('../system/end.php');
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id) {
                unset($_SESSION['fsort_id']);
                unset($_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            // Проверяем, было ли последнее сообщение от того же автора?
            $req = $db->query("SELECT *, CHAR_LENGTH(`text`) AS `strlen` FROM `forum` WHERE `type` = 'm' AND `refid` = " . $id . " AND `close` != 1 ORDER BY `time` DESC LIMIT 1");

            $update = false;

            if ($req->rowCount()) {
                $update = true;
                $res = $req->fetch();

                if (!isset($_POST['addfiles']) && $res['time'] + 3600 < strtotime('+ 1 hour') && $res['strlen'] + strlen($msg) < 65536 && $res['user_id'] == $systemUser->id) {
                    $newpost = $res['text'];

                    if (strpos($newpost, '[timestamp]') === false) {
                        $newpost = '[timestamp]' . date("d.m.Y H:i", $res['time']) . '[/timestamp]' . PHP_EOL . $newpost;
                    }

                    $newpost .= PHP_EOL . PHP_EOL . '[timestamp]' . date("d.m.Y H:i", time()) . '[/timestamp]' . PHP_EOL . $msg;

                    // Обновляем пост
                    $db->prepare('UPDATE `forum` SET
                      `text` = ?,
                      `time` = ?
                      WHERE `id` = ' . $res['id']
                    )->execute([$newpost, time()]);
                } else {
                    $update = false;

                    /** @var Johncms\Api\EnvironmentInterface $env */
                    $env = App::getContainer()->get(Johncms\Api\EnvironmentInterface::class);

                    // Добавляем сообщение в базу
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
                        $id,
                        time(),
                        $systemUser->id,
                        $systemUser->name,
                        $env->getIp(),
                        $env->getIpViaProxy(),
                        $env->getUserAgent(),
                        $msg,
                    ]);

                    $fadd = $db->lastInsertId();
                }
            }

            // Обновляем время топика
            $db->exec("UPDATE `forum` SET
                `time` = '" . time() . "'
                WHERE `id` = '$id'
            ");

            // Обновляем статистику юзера
            $db->exec("UPDATE `users` SET
                `postforum`='" . ($systemUser->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $systemUser->id . "'
            ");

            // Вычисляем, на какую страницу попадает добавляемый пост
            $page = $set_forum['upfp'] ? 1 : ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '$id'" . ($systemUser->rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn() / $kmess);

            if (isset($_POST['addfiles'])) {
                if ($update) {
                    header("Location: index.php?id=" . $res['id'] . "&act=addfile");
                } else {
                    header("Location: index.php?id=" . $fadd . "&act=addfile");
                }
            } else {
                header("Location: index.php?id=" . $id . "&page=" . $page);
            }
            exit;
        } else {
            require('../system/head.php');
            $msg_pre = $tools->checkout($msg, 1, 1);
            $msg_pre = $tools->smilies($msg_pre, $systemUser->rights ? 1 : 0);
            $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
            echo '<div class="phdr"><b>' . _t('Topic') . ':</b> ' . $type1['text'] . '</div>';

            if ($msg && !isset($_POST['submit'])) {
                echo '<div class="list1">' . $tools->displayUser($systemUser, ['iphide' => 1, 'header' => '<span class="gray">(' . $tools->displayDate(time()) . ')</span>', 'body' => $msg_pre]) . '</div>';
            }

            echo '<form name="form" action="index.php?act=say&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><div class="gmenu">' .
                '<p><h3>' . _t('Message') . '</h3>';
            echo '</p><p>' . $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form', 'msg');
            echo '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="msg">' . (empty($_POST['msg']) ? '' : $tools->checkout($msg)) . '</textarea></p>' .
                '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . _t('Add File');

            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '</p><p>' .
                '<input type="submit" name="submit" value="' . _t('Send') . '" style="width: 107px; cursor: pointer"/> ' .
                ($set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer"/>' : '') .
                '<input type="hidden" name="token" value="' . $token . '"/>' .
                '</p></div></form>';
        }

        echo '<div class="phdr"><a href="../help/?act=smileys">' . _t('Smilies') . '</a></div>' .
            '<p><a href="index.php?id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a></p>';
        break;

    case 'm':
        // Добавление сообщения с цитированием поста
        $th = $type1['refid'];
        $th1 = $db->query("SELECT * FROM `forum` WHERE `id` = '$th'")->fetch();

        if (($th1['edit'] == 1 || $th1['close'] == 1) && $systemUser->rights < 7) {
            require('../system/head.php');
            echo $tools->displayError(_t('You cannot write in a closed topic'), '<a href="index.php?id=' . $th1['id'] . '">' . _t('Back') . '</a>');
            require('../system/end.php');
            exit;
        }

        if ($type1['user_id'] == $systemUser->id) {
            require('../system/head.php');
            echo $tools->displayError(_t('You can not reply to your own message'), '<a href="index.php?id=' . $th1['id'] . '">' . _t('Back') . '</a>');
            require('../system/end.php');
            exit;
        }

        $shift = ($config['timeshift'] + $systemUser->getConfig()->timeshift) * 3600;
        $vr = date("d.m.Y / H:i", $type1['time'] + $shift);
        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        $txt = isset($_POST['txt']) ? intval($_POST['txt']) : false;

        if (!empty($_POST['citata'])) {
            // Если была цитата, форматируем ее и обрабатываем
            $citata = isset($_POST['citata']) ? trim($_POST['citata']) : '';
            $citata = $container->get(Johncms\Api\BbcodeInterface::class)->notags($citata);
            $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
            $citata = mb_substr($citata, 0, 200);
            $tp = date("d.m.Y H:i", $type1['time']);
            $msg = '[c][url=' . $config['homeurl'] . '/forum/index.php?act=post&id=' . $type1['id'] . ']#[/url] ' . $type1['from'] . ' ([time]' . $tp . "[/time])\n" . $citata . '[/c]' . $msg;
        } elseif (isset($_POST['txt'])) {
            // Если был ответ, обрабатываем реплику
            switch ($txt) {
                case 2:
                    $repl = $type1['from'] . ', ' . _t('I am glad to answer you') . ', ';
                    break;

                case 3:
                    $repl = $type1['from'] . ', ' . _t('respond to Your message') . ' ([url=' . $config['homeurl'] . '/forum/index.php?act=post&id=' . $type1['id'] . ']' . $vr . '[/url]): ';
                    break;

                default :
                    $repl = $type1['from'] . ', ';
            }
            $msg = $repl . ' ' . $msg;
        }

        //Обрабатываем ссылки
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~', 'forum_link', $msg);

        if (isset($_POST['submit'])
            && isset($_POST['token'])
            && isset($_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            if (empty($_POST['msg'])) {
                require('../system/head.php');
                echo $tools->displayError(_t('You have not entered the message'), '<a href="index.php?act=say&amp;id=' . $th . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '">' . _t('Repeat') . '</a>');
                require('../system/end.php');
                exit;
            }

            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                require('../system/head.php');
                echo $tools->displayError(_t('Text is too short'), '<a href="index.php?id=' . $id . '">' . _t('Back') . '</a>');
                require('../system/end.php');
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum` WHERE `user_id` = '" . $systemUser->id . "' AND `type` = 'm' ORDER BY `time` DESC LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();

                if ($msg == $res['text']) {
                    require('../system/head.php');
                    echo $tools->displayError(_t('Message already exists'), '<a href="index.php?id=' . $th . '&amp;start=' . $start . '">' . _t('Back') . '</a>');
                    require('../system/end.php');
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $th) {
                unset($_SESSION['fsort_id']);
                unset($_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            /** @var Johncms\Api\EnvironmentInterface $env */
            $env = App::getContainer()->get(Johncms\Api\EnvironmentInterface::class);

            // Добавляем сообщение в базу
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
                $th,
                time(),
                $systemUser->id,
                $systemUser->name,
                $env->getIp(),
                $env->getIpViaProxy(),
                $env->getUserAgent(),
                $msg,
            ]);

            $fadd = $db->lastInsertId();

            // Обновляем время топика
            $db->exec("UPDATE `forum`
                SET `time` = '" . time() . "'
                WHERE `id` = '$th'
            ");

            // Обновляем статистику юзера
            $db->exec("UPDATE `users` SET
                `postforum`='" . ($systemUser->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $systemUser->id . "'
            ");

            // Вычисляем, на какую страницу попадает добавляемый пост
            $page = $set_forum['upfp'] ? 1 : ceil($db->query("SELECT COUNT(*) FROM `forum` WHERE `type` = 'm' AND `refid` = '$th'" . ($systemUser->rights >= 7 ? '' : " AND `close` != '1'"))->fetchColumn() / $kmess);

            if (isset($_POST['addfiles'])) {
                header("Location: index.php?id=$fadd&act=addfile");
            } else {
                header("Location: index.php?id=$th&page=$page");
            }
            exit;
        } else {
            $textl = _t('Forum');
            require('../system/head.php');
            $qt = " $type1[text]";
            $msg_pre = $tools->checkout($msg, 1, 1);
            $msg_pre = $tools->smilies($msg_pre, $systemUser->rights ? 1 : 0);
            $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
            echo '<div class="phdr"><b>' . _t('Topic') . ':</b> ' . $th1['text'] . '</div>';
            $qt = str_replace("<br>", "\r\n", $qt);
            $qt = trim(preg_replace('#\[c\](.*?)\[/c\]#si', '', $qt));
            $qt = $tools->checkout($qt, 0, 2);

            if (!empty($_POST['msg']) && !isset($_POST['submit'])) {
                echo '<div class="list1">' . $tools->displayUser($systemUser, ['iphide' => 1, 'header' => '<span class="gray">(' . $tools->displayDate(time()) . ')</span>', 'body' => $msg_pre]) . '</div>';
            }

            echo '<form name="form" action="index.php?act=say&amp;id=' . $id . '&amp;start=' . $start . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '" method="post"><div class="gmenu">';

            if (isset($_GET['cyt'])) {
                // Форма с цитатой
                echo '<p><b>' . $type1['from'] . '</b> <span class="gray">(' . $vr . ')</span></p>' .
                    '<p><h3>' . _t('Quote') . '</h3>' .
                    '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="citata">' . (empty($_POST['citata']) ? $qt : $tools->checkout($_POST['citata'])) . '</textarea>' .
                    '<br /><small>' . _t('Only allowed 200 characters, other text will be cropped.') . '</small></p>';
            } else {
                // Форма с репликой
                echo '<p><h3>' . _t('Appeal') . '</h3>' .
                    '<input type="radio" value="0" ' . (!$txt ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>,<br />' .
                    '<input type="radio" value="2" ' . ($txt == 2 ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . _t('I am glad to answer you') . ',<br />' .
                    '<input type="radio" value="3" ' . ($txt == 3 ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['from'] . '</b>, ' . _t('respond to Your message') . ' (<a href="index.php?act=post&amp;id=' . $type1['id'] . '">' . $vr . '</a>):</p>';
            }

            echo '<p><h3>' . _t('Message') . '</h3>';
            echo '</p><p>' . $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form', 'msg');
            echo '<textarea rows="' . $systemUser->getConfig()->fieldHeight . '" name="msg">' . (empty($_POST['msg']) ? '' : $tools->checkout($_POST['msg'])) . '</textarea></p>' .
                '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . _t('Add File');

            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '</p><p><input type="submit" name="submit" value="' . _t('Send') . '" style="width: 107px; cursor: pointer;"/> ' .
                ($set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer;"/>' : '') .
                '<input type="hidden" name="token" value="' . $token . '"/>' .
                '</p></div></form>';
        }

        echo '<div class="phdr"><a href="../help/?act=smileys">' . _t('Smilies') . '</a></div>' .
            '<p><a href="index.php?id=' . $type1['refid'] . '&amp;start=' . $start . '">' . _t('Back') . '</a></p>';
        break;

    default:
        require('../system/head.php');
        echo $tools->displayError(_t('Topic has been deleted or does not exists'), '<a href="index.php">' . _t('Forum') . '</a>');
        require('../system/end.php');
}
