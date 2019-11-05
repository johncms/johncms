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

/**
 * @var Johncms\Api\ConfigInterface $config
 * @var PDO                         $db
 * @var Johncms\Api\ToolsInterface  $tools
 * @var Johncms\Api\UserInterface   $user
 */

// Закрываем доступ для определенных ситуаций
if (! $id
    || ! $user->isValid()
    || isset($user->ban[1])
    || isset($user->ban[11])
    || (! $user->rights && $config['mod_forum'] == 3)
) {
    echo $tools->displayError(_t('Access forbidden'));
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

// Вспомогательная Функция обработки ссылок форума
function forum_link($m)
{
    global $db, $config;

    if (! isset($m[3])) {
        return '[url=' . $m[1] . ']' . $m[2] . '[/url]';
    }
    $p = parse_url($m[3]);

    if ('http://' . $p['host'] . ($p['path'] ?? '') . '?id=' == $config->homeurl . '/forum/?id=') {
        $thid = abs((int) (preg_replace('/(.*?)id=/si', '', $m[3])));
        $req = $db->query("SELECT `name` FROM `forum_topic` WHERE `id`= '${thid}' AND (`deleted` != '1' OR deleted IS NULL)");

        if ($req->rowCount()) {
            $res = $req->fetch();
            $name = strtr($res['name'], [
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
        }

        return $m[3];
    }

    return $m[3];
}

// Проверка на флуд
$flood = $tools->antiflood();

if ($flood) {
    echo $tools->displayError(sprintf(_t('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
        '<a href="?type=topic&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a>');
    echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
    exit;
}

$headmod = 'forum,' . $id . ',message';

$post_type = $_REQUEST['type'] ?? 'post';

switch ($post_type) {
    case 'post':
        $type1 = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${id}'")->fetch();
        // Добавление простого сообщения
        if (($type1['edit'] == 1 || $type1['close'] == 1) && $user->rights < 7) {
            // Проверка, закрыта ли тема
            echo $tools->displayError(_t('You cannot write in a closed topic'),
                '<a href="?type=topic&amp;id=' . $id . '">' . _t('Back') . '</a>');
            echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
            exit;
        }

        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        //Обрабатываем ссылки
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            'forum_link', $msg);

        if (isset($_POST['submit'])
            && ! empty($_POST['msg'])
            && isset($_POST['token'], $_SESSION['token'])

            && $_POST['token'] == $_SESSION['token']
        ) {
            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                echo $tools->displayError(_t('Text is too short'),
                    '<a href="?type=topic&amp;id=' . $id . '">' . _t('Back') . '</a>');
                echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum_messages` WHERE `user_id` = '" . $user->id . "' ORDER BY `date` DESC");

            if ($req->rowCount()) {
                $res = $req->fetch();
                if ($msg == $res['text']) {
                    echo $tools->displayError(_t('Message already exists'),
                        '<a href="?type=topic&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a>');
                    echo $view->render('system::app/old_content',
                        ['title' => $textl ?? '', 'content' => ob_get_clean()]);
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            // Проверяем, было ли последнее сообщение от того же автора?
            $req = $db->query('SELECT *, CHAR_LENGTH(`text`) AS `strlen` FROM `forum_messages` 
            WHERE `topic_id` = ' . $id . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)") . ' 
            ORDER BY `date` DESC LIMIT 1');

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
                        $newpost = '[timestamp]' . date('d.m.Y H:i',
                                $res['date']) . '[/timestamp]' . PHP_EOL . $newpost;
                    }

                    $newpost .= PHP_EOL . PHP_EOL . '[timestamp]' . date('d.m.Y H:i',
                            time()) . '[/timestamp]' . PHP_EOL . $msg;

                    // Обновляем пост
                    $db->prepare('UPDATE `forum_messages` SET
                      `text` = ?,
                      `date` = ?
                      WHERE `id` = ' . $res['id']
                    )->execute([$newpost, time()]);
                } else {
                    $update = false;
                    /** @var Johncms\Api\EnvironmentInterface $env */
                    $env = App::getContainer()->get(Johncms\Api\EnvironmentInterface::class);

                    // Добавляем сообщение в базу
                    $db->prepare('
                      INSERT INTO `forum_messages` SET
                      `topic_id` = ?,
                      `date` = ?,
                      `user_id` = ?,
                      `user_name` = ?,
                      `ip` = ?,
                      `ip_via_proxy` = ?,
                      `user_agent` = ?,
                      `text` = ?
                    ')->execute([
                        $id,
                        time(),
                        $user->id,
                        $user->name,
                        $env->getIp(),
                        $env->getIpViaProxy(),
                        $env->getUserAgent(),
                        $msg,
                    ]);

                    $fadd = $db->lastInsertId();
                }
            }

            $cnt_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${id}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
            $cnt_all_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${id}'")->fetchColumn();

            // Пересчитываем топик
            $tools->recountForumTopic($id);

            // Обновляем статистику юзера
            $db->exec("UPDATE `users` SET
                `postforum`='" . ($user->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $user->id . "'
            ");

            // Вычисляем, на какую страницу попадает добавляемый пост
            if ($user->rights >= 7) {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_all_messages / $user->config->kmess);
            } else {
                $page = $set_forum['upfp'] ? 1 : ceil($cnt_messages / $user->config->kmess);
            }

            if (isset($_POST['addfiles'])) {
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
        echo '<div class="phdr"><b>' . _t('Topic') . ':</b> ' . $type1['name'] . '</div>';

        if ($msg && ! isset($_POST['submit'])) {
            echo '<div class="list1">' . $tools->displayUser($user, [
                    'iphide' => 1,
                    'header' => '<span class="gray">(' . $tools->displayDate(time()) . ')</span>',
                    'body'   => $msg_pre,
                ]) . '</div>';
        }

        echo '<form name="form" action="?act=say&amp;type=post&amp;id=' . $id . '&amp;start=' . $start . '" method="post"><div class="gmenu">' .
            '<p><h3>' . _t('Message') . '</h3>';
        echo '</p><p>' . $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form', 'msg');
        echo '<textarea rows="' . $user->config->fieldHeight . '" name="msg">' . (empty($_POST['msg']) ? '' : $tools->checkout($msg)) . '</textarea></p>' .
            '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . _t('Add File');

        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;
        echo '</p><p>' .
            '<input type="submit" name="submit" value="' . _t('Send') . '" style="width: 107px; cursor: pointer"/> ' .
            ($set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer"/>' : '') .
            '<input type="hidden" name="token" value="' . $token . '"/>' .
            '</p></div></form>';

        echo '<div class="phdr"><a href="../help/?act=smileys">' . _t('Smilies') . '</a></div>' .
            '<p><a href="?type=topic&amp;id=' . $id . '&amp;start=' . $start . '">' . _t('Back') . '</a></p>';
        break;

    case 'reply':
        // Добавление сообщения с цитированием поста
        $type1 = $db->query("SELECT * FROM `forum_messages` WHERE `id` = '${id}'" . ($user->rights >= 7 ? '' : " AND (`deleted` != '1' OR deleted IS NULL)"))->fetch();

        if (empty($type1)) {
            echo $tools->displayError(_t('Message not found'),
                '<a href="?type=topic&amp;id=' . $th1['id'] . '">' . _t('Back') . '</a>');
            echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
            exit;
        }

        $th = $type1['topic_id'];
        $th1 = $db->query("SELECT * FROM `forum_topic` WHERE `id` = '${th}'")->fetch();

        if (($th1['deleted'] == 1 || $th1['closed'] == 1) && $user->rights < 7) {
            echo $tools->displayError(_t('You cannot write in a closed topic'),
                '<a href="?type=topic&amp;id=' . $th1['id'] . '">' . _t('Back') . '</a>');
            echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
            exit;
        }

        if ($type1['user_id'] == $user->id) {
            echo $tools->displayError(_t('You can not reply to your own message'),
                '<a href="?type=topic&amp;id=' . $th1['id'] . '">' . _t('Back') . '</a>');
            echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
            exit;
        }

        $shift = ($config['timeshift'] + $user->config->timeshift) * 3600;
        $vr = date('d.m.Y / H:i', $type1['date'] + $shift);
        $msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
        $txt = isset($_POST['txt']) ? (int) ($_POST['txt']) : false;

        if (! empty($_POST['citata'])) {
            // Если была цитата, форматируем ее и обрабатываем
            $citata = isset($_POST['citata']) ? trim($_POST['citata']) : '';
            $citata = $container->get(Johncms\Api\BbcodeInterface::class)->notags($citata);
            $citata = preg_replace('#\[c\](.*?)\[/c\]#si', '', $citata);
            $citata = mb_substr($citata, 0, 200);
            $tp = date('d.m.Y H:i', $type1['date']);
            $msg = '[c][url=' . $config['homeurl'] . '/forum/?act=show_post&id=' . $type1['id'] . ']#[/url] ' . $type1['user_name'] . ' ([time]' . $tp . "[/time])\n" . $citata . '[/c]' . $msg;
        } elseif (isset($_POST['txt'])) {
            // Если был ответ, обрабатываем реплику
            switch ($txt) {
                case 2:
                    $repl = $type1['user_name'] . ', ' . _t('I am glad to answer you') . ', ';
                    break;

                case 3:
                    $repl = $type1['user_name'] . ', ' . _t('respond to Your message') . ' ([url=' . $config['homeurl'] . '/forum/?act=show_post&id=' . $type1['id'] . ']' . $vr . '[/url]): ';
                    break;

                default:
                    $repl = $type1['user_name'] . ', ';
            }
            $msg = $repl . ' ' . $msg;
        }

        //Обрабатываем ссылки
        $msg = preg_replace_callback('~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
            'forum_link', $msg);

        if (isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
            && $_POST['token'] == $_SESSION['token']
        ) {
            if (empty($_POST['msg'])) {
                echo $tools->displayError(_t('You have not entered the message'),
                    '<a href="?type=reply&amp;act=say&amp;id=' . $th . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '">' . _t('Repeat') . '</a>');
                echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
                exit;
            }

            // Проверяем на минимальную длину
            if (mb_strlen($msg) < 4) {
                echo $tools->displayError(_t('Text is too short'),
                    '<a href="?type=topic&amp;id=' . $id . '">' . _t('Back') . '</a>');
                echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
                exit;
            }

            // Проверяем, не повторяется ли сообщение?
            $req = $db->query("SELECT * FROM `forum_messages` WHERE `user_id` = '" . $user->id . "' ORDER BY `date` DESC LIMIT 1");

            if ($req->rowCount()) {
                $res = $req->fetch();

                if ($msg == $res['text']) {
                    echo $tools->displayError(_t('Message already exists'),
                        '<a href="?type=topic&amp;id=' . $th . '&amp;start=' . $start . '">' . _t('Back') . '</a>');
                    echo $view->render('system::app/old_content',
                        ['title' => $textl ?? '', 'content' => ob_get_clean()]);
                    exit;
                }
            }

            // Удаляем фильтр, если он был
            if (isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $th) {
                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);
            }

            unset($_SESSION['token']);

            /** @var Johncms\Api\EnvironmentInterface $env */
            $env = App::getContainer()->get(Johncms\Api\EnvironmentInterface::class);

            // Добавляем сообщение в базу
            $db->prepare('
              INSERT INTO `forum_messages` SET
              `topic_id` = ?,
              `date` = ?,
              `user_id` = ?,
              `user_name` = ?,
              `ip` = ?,
              `ip_via_proxy` = ?,
              `user_agent` = ?,
              `text` = ?
            ')->execute([
                $th,
                time(),
                $user->id,
                $user->name,
                $env->getIp(),
                $env->getIpViaProxy(),
                $env->getUserAgent(),
                $msg,
            ]);

            $fadd = $db->lastInsertId();

            $cnt_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${th}' AND (`deleted` != '1' OR `deleted` IS NULL)")->fetchColumn();
            $cnt_all_messages = $db->query("SELECT COUNT(*) FROM `forum_messages` WHERE `topic_id` = '${th}'")->fetchColumn();

            // Обновляем статистику юзера
            $db->exec("UPDATE `users` SET
                `postforum`='" . ($user->postforum + 1) . "',
                `lastpost` = '" . time() . "'
                WHERE `id` = '" . $user->id . "'
            ");

            $tools->recountForumTopic($th);

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
        $textl = _t('Forum');
        $qt = $type1['text'];
        $msg_pre = $tools->checkout($msg, 1, 1);
        $msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
        $msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);
        echo '<div class="phdr"><b>' . _t('Topic') . ':</b> ' . $th1['name'] . '</div>';
        $qt = str_replace('<br>', "\r\n", $qt);
        $qt = trim(preg_replace('#\[c\](.*?)\[/c\]#si', '', $qt));
        $qt = $tools->checkout($qt, 0, 2);

        if (! empty($_POST['msg']) && ! isset($_POST['submit'])) {
            echo '<div class="list1">' . $tools->displayUser($user, [
                    'iphide' => 1,
                    'header' => '<span class="gray">(' . $tools->displayDate(time()) . ')</span>',
                    'body'   => $msg_pre,
                ]) . '</div>';
        }

        echo '<form name="form" action="?act=say&amp;type=reply&amp;id=' . $id . '&amp;start=' . $start . (isset($_GET['cyt']) ? '&amp;cyt' : '') . '" method="post"><div class="gmenu">';

        if (isset($_GET['cyt'])) {
            // Форма с цитатой
            echo '<p><b>' . $type1['user_name'] . '</b> <span class="gray">(' . $vr . ')</span></p>' .
                '<p><h3>' . _t('Quote') . '</h3>' .
                '<textarea rows="' . $user->config->fieldHeight . '" name="citata">' . (empty($_POST['citata']) ? $qt : $tools->checkout($_POST['citata'])) . '</textarea>' .
                '<br /><small>' . _t('Only allowed 200 characters, other text will be cropped.') . '</small></p>';
        } else {
            // Форма с репликой
            echo '<p><h3>' . _t('Appeal') . '</h3>' .
                '<input type="radio" value="0" ' . (! $txt ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['user_name'] . '</b>,<br />' .
                '<input type="radio" value="2" ' . ($txt == 2 ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['user_name'] . '</b>, ' . _t('I am glad to answer you') . ',<br />' .
                '<input type="radio" value="3" ' . ($txt == 3 ? 'checked="checked"' : '') . ' name="txt" />&#160;<b>' . $type1['user_name'] . '</b>, ' . _t('respond to Your message') . ' (<a href="?act=show_post&amp;id=' . $type1['id'] . '">' . $vr . '</a>):</p>';
        }

        echo '<p><h3>' . _t('Message') . '</h3>';
        echo '</p><p>' . $container->get(Johncms\Api\BbcodeInterface::class)->buttons('form', 'msg');
        echo '<textarea rows="' . $user->config->fieldHeight . '" name="msg">' . (empty($_POST['msg']) ? '' : $tools->checkout($_POST['msg'])) . '</textarea></p>' .
            '<p><input type="checkbox" name="addfiles" value="1" ' . (isset($_POST['addfiles']) ? 'checked="checked" ' : '') . '/> ' . _t('Add File');

        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;
        echo '</p><p><input type="submit" name="submit" value="' . _t('Send') . '" style="width: 107px; cursor: pointer;"/> ' .
            ($set_forum['preview'] ? '<input type="submit" value="' . _t('Preview') . '" style="width: 107px; cursor: pointer;"/>' : '') .
            '<input type="hidden" name="token" value="' . $token . '"/>' .
            '</p></div></form>';

        echo '<div class="phdr"><a href="../help/?act=smileys">' . _t('Smilies') . '</a></div>' .
            '<p><a href="?type=topic&amp;id=' . $type1['topic_id'] . '&amp;start=' . $start . '">' . _t('Back') . '</a></p>';
        break;

    default:
        echo $tools->displayError(_t('Topic has been deleted or does not exists'),
            '<a href="./">' . _t('Forum') . '</a>');
        echo $view->render('system::app/old_content', ['title' => $textl ?? '', 'content' => ob_get_clean()]);
}
