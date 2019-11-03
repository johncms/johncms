<?php

declare(strict_types=1);

/*
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

use Johncms\Api\BbcodeInterface;
use Johncms\Api\ConfigInterface;
use Johncms\Api\EnvironmentInterface;
use Johncms\Api\ToolsInterface;
use Johncms\Api\UserInterface;
use League\Plates\Engine;
use Psr\Container\ContainerInterface;
use Zend\I18n\Translator\Translator;

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';

// Сюда можно (через запятую) добавить ID тех юзеров, кто не в администрации,
// но которым разрешено читать и писать в Админ клубе
$guestAccess = [];

/** @var ContainerInterface $container */
$container = App::getContainer();

/** @var PDO $db */
$db = $container->get(PDO::class);

/** @var UserInterface $systemUser */
$systemUser = $container->get(UserInterface::class);

/** @var ToolsInterface $tools */
$tools = $container->get(ToolsInterface::class);

/** @var EnvironmentInterface $env */
$env = $container->get(EnvironmentInterface::class);

/** @var BbcodeInterface $bbcode */
$bbcode = $container->get(BbcodeInterface::class);

/** @var ConfigInterface $config */
$config = $container->get(ConfigInterface::class);

/** @var Translator $translator */
$translator = $container->get(Translator::class);
$translator->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

/** @var Engine $view */
$view = $container->get(Engine::class);

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Проверяем права доступа в Админ-Клуб
if (isset($_SESSION['ga']) && $systemUser->rights < 1 && ! in_array($systemUser->id, $guestAccess)) {
    unset($_SESSION['ga']);
}

ob_start();

// Задаем заголовки страницы
$textl = isset($_SESSION['ga']) ? _t('Admin Club') : _t('Guestbook');

// Если гостевая закрыта, выводим сообщение и закрываем доступ (кроме Админов)
if (! $config->mod_guest && $systemUser->rights < 7) {
    echo $view->render('system::app/old_content', [
        'title'   => $textl,
        'content' => '<div class="rmenu"><p>' . _t('Guestbook is closed') . '</p></div>',
    ]);
    exit;
}

switch ($act) {
    case 'delpost':
        // Удаление отдельного поста
        if ($systemUser->rights >= 6 && $id) {
            if (isset($_GET['yes'])) {
                $db->exec('DELETE FROM `guest` WHERE `id` = ' . $id);
                header('Location: ./');
            } else {
                echo '<div class="phdr"><a href="./"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Delete message') . '</div>' .
                    '<div class="rmenu"><p>' . _t('Do you really want to delete?') . '?<br>' .
                    '<a href="?act=delpost&amp;id=' . $id . '&amp;yes">' . _t('Delete') . '</a> | ' .
                    '<a href="./">' . _t('Cancel') . '</a></p></div>';
            }
        }
        break;

    case 'say':
        // Добавление нового поста
        $admset = isset($_SESSION['ga']) ? 1 : 0; // Задаем куда вставляем, в Админ клуб (1), или в Гастивуху (0)
        // Принимаем и обрабатываем данные
        $name = isset($_POST['name']) ? mb_substr(trim($_POST['name']), 0, 20) : '';
        $msg = isset($_POST['msg']) ? mb_substr(trim($_POST['msg']), 0, 5000) : '';
        $trans = isset($_POST['msgtrans']) ? 1 : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $from = $systemUser->isValid() ? $systemUser->name : $name;
        // Проверяем на ошибки
        $error = [];
        $flood = false;

        if (! isset($_POST['token']) || ! isset($_SESSION['token']) || $_POST['token'] != $_SESSION['token']) {
            $error[] = _t('Wrong data');
        }

        if (! $systemUser->isValid() && empty($name)) {
            $error[] = _t('You have not entered a name');
        }

        if (empty($msg)) {
            $error[] = _t('You have not entered the message');
        }

        if ($systemUser->ban['1'] || $systemUser->ban['13']) {
            $error[] = _t('Access forbidden');
        }

        // CAPTCHA для гостей
        if (! $systemUser->isValid() && (empty($code) || mb_strlen($code) < 3 || strtolower($code) != strtolower($_SESSION['code']))) {
            $error[] = _t('The security code is not correct');
        }

        unset($_SESSION['code']);

        if ($systemUser->isValid()) {
            // Антифлуд для зарегистрированных пользователей
            $flood = $tools->antiflood();
        } else {
            // Антифлуд для гостей
            $req = $db->query("SELECT `time` FROM `guest` WHERE `ip` = '" . $env->getIp() . "' AND `browser` = " . $db->quote($env->getUserAgent()) . " AND `time` > '" . (time() - 60) . "'");

            if ($req->rowCount()) {
                $res = $req->fetch();
                $flood = 60 - (time() - $res['time']);
            }
        }

        if ($flood) {
            $error = sprintf(_t('You cannot add the message so often. Please, wait %d seconds.'), $flood);
        }

        if (! $error) {
            // Проверка на одинаковые сообщения
            $req = $db->query("SELECT * FROM `guest` WHERE `user_id` = '" . $systemUser->id . "' ORDER BY `time` DESC");
            $res = $req->fetch();

            if ($res['text'] == $msg) {
                header('location: ./');
                exit;
            }
        }

        if (! $error) {
            // Вставляем сообщение в базу
            $db->prepare("INSERT INTO `guest` SET
                `adm` = ?,
                `time` = ?,
                `user_id` = ?,
                `name` = ?,
                `text` = ?,
                `ip` = ?,
                `browser` = ?,
                `otvet` = ''
            ")->execute([
                $admset,
                time(),
                $systemUser->id,
                $from,
                $msg,
                $env->getIp(),
                $env->getUserAgent(),
            ]);

            // Фиксируем время последнего поста (антиспам)
            if ($systemUser->isValid()) {
                $postguest = $systemUser->postguest + 1;
                $db->exec("UPDATE `users` SET `postguest` = '${postguest}', `lastpost` = '" . time() . "' WHERE `id` = " . $systemUser->id);
            }

            header('location: ./');
        } else {
            echo $tools->displayError($error, '<a href="./">' . _t('Back') . '</a>');
        }
        break;

    case 'otvet':
        // Добавление "ответа Админа"
        if ($systemUser->rights >= 6 && $id) {
            if (isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $reply = isset($_POST['otv']) ? mb_substr(trim($_POST['otv']), 0, 5000) : '';
                $db->exec("UPDATE `guest` SET
                    `admin` = '" . $systemUser->name . "',
                    `otvet` = " . $db->quote($reply) . ",
                    `otime` = '" . time() . "'
                    WHERE `id` = '${id}'
                ");
                header('location: ./');
            } else {
                echo '<div class="phdr"><a href="./"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Reply') . '</div>';
                $req = $db->query("SELECT * FROM `guest` WHERE `id` = '${id}'");
                $res = $req->fetch();
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;

                echo '<div class="menu">' .
                    '<div class="quote"><b>' . $res['name'] . '</b>' .
                    '<br />' . $tools->checkout($res['text']) . '</div>' .
                    '<form name="form" action="?act=otvet&amp;id=' . $id . '" method="post">' .
                    '<p><h3>' . _t('Reply') . '</h3>' . $bbcode->buttons('form', 'otv') .
                    '<textarea rows="' . $systemUser->config->fieldHeight . '" name="otv">' . $tools->checkout($res['otvet']) . '</textarea></p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Reply') . '"/></p>' .
                    '<input type="hidden" name="token" value="' . $token . '"/>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="./">' . _t('Back') . '</a></div>';
            }
        }
        break;

    case
    'edit':
        // Редактирование поста
        if ($systemUser->rights >= 6 && $id) {
            if (isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $res = $db->query("SELECT `edit_count` FROM `guest` WHERE `id`='${id}'")->fetch();
                $edit_count = $res['edit_count'] + 1;
                $msg = isset($_POST['msg']) ? mb_substr(trim($_POST['msg']), 0, 5000) : '';

                $db->prepare('
                  UPDATE `guest` SET
                  `text` = ?,
                  `edit_who` = ?,
                  `edit_time` = ?,
                  `edit_count` = ?
                  WHERE `id` = ?
                ')->execute([
                    $msg,
                    $systemUser->name,
                    time(),
                    $edit_count,
                    $id,
                ]);

                header('location: ./');
            } else {
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
                $res = $db->query("SELECT * FROM `guest` WHERE `id` = '${id}'")->fetch();
                $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');
                echo '<div class="phdr"><a href="./"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Edit') . '</div>' .
                    '<div class="rmenu">' .
                    '<form name="form" action="?act=edit&amp;id=' . $id . '" method="post">' .
                    '<p><b>' . _t('Author') . ':</b> ' . $res['name'] . '</p><p>';
                echo $bbcode->buttons('form', 'msg');
                echo '<textarea rows="' . $systemUser->config->fieldHeight . '" name="msg">' . $text . '</textarea></p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Save') . '"/></p>' .
                    '<input type="hidden" name="token" value="' . $token . '"/>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="./">' . _t('Back') . '</a></div>';
            }
        }
        break;

    case 'clean':
        // Очистка Гостевой
        if ($systemUser->rights >= 7) {
            if (isset($_POST['submit'])) {
                // Проводим очистку Гостевой, согласно заданным параметрам
                $adm = isset($_SESSION['ga']) ? 1 : 0;
                $cl = isset($_POST['cl']) ? (int) ($_POST['cl']) : '';

                switch ($cl) {
                    case '1':
                        // Чистим сообщения, старше 1 дня
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}' AND `time` < '" . (time() - 86400) . "'");
                        echo '<p>' . _t('All messages older than 1 day were deleted') . '</p>';
                        break;

                    case '2':
                        // Проводим полную очистку
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}'");
                        echo '<p>' . _t('Full clearing is finished') . '</p>';
                        break;
                    default:
                        // Чистим сообщения, старше 1 недели
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}' AND `time`<='" . (time() - 604800) . "';");
                        echo '<p>' . _t('All messages older than 1 week were deleted') . '</p>';
                }

                $db->query('OPTIMIZE TABLE `guest`');
                echo '<p><a href="./">' . _t('Guestbook') . '</a></p>';
            } else {
                // Запрос параметров очистки
                echo '<div class="phdr"><a href="./"><b>' . _t('Guestbook') . '</b></a> | ' . _t('Clear') . '</div>' .
                    '<div class="menu">' .
                    '<form id="clean" method="post" action="?act=clean">' .
                    '<p><h3>' . _t('Clearing parameters') . '</h3>' .
                    '<input type="radio" name="cl" value="0" checked="checked" />' . _t('Older than 1 week') . '<br />' .
                    '<input type="radio" name="cl" value="1" />' . _t('Older than 1 day') . '<br />' .
                    '<input type="radio" name="cl" value="2" />' . _t('Clear all') . '</p>' .
                    '<p><input type="submit" name="submit" value="' . _t('Clear') . '" /></p>' .
                    '</form></div>' .
                    '<div class="phdr"><a href="./">' . _t('Cancel') . '</a></div>';
            }
        }
        break;

    case 'ga':
        // Переключение режима работы Гостевая / Админ-клуб
        if ($systemUser->rights >= 1 || in_array($systemUser->id, $guestAccess)) {
            if (isset($_GET['do']) && $_GET['do'] == 'set') {
                $_SESSION['ga'] = 1;
            } else {
                unset($_SESSION['ga']);
            }
        }

    default:
        // Отображаем Гостевую, или Админ клуб
        if (! $config->mod_guest) {
            echo '<div class="alarm">' . _t('The guestbook is closed') . '</div>';
        }

        echo '<div class="phdr"><b>' . _t('Guestbook') . '</b></div>';

        if ($systemUser->rights > 0 || in_array($systemUser->id, $guestAccess)) {
            $menu = [
                isset($_SESSION['ga']) ? '<a href="?act=ga">' . _t('Guestbook') . '</a>' : '<b>' . _t('Guestbook') . '</b>',
                isset($_SESSION['ga']) ? '<b>' . _t('Admin Club') . '</b>' : '<a href="?act=ga&amp;do=set">' . _t('Admin Club') . '</a>',
                $systemUser->rights >= 7 ? '<a href="?act=clean">' . _t('Clear') . '</a>' : '',
            ];
            echo '<div class="topmenu">' . implode(' | ', array_filter($menu)) . '</div>';
        }

        // Форма ввода нового сообщения
        if (($systemUser->isValid() || $config->mod_guest == 2) && ! isset($systemUser->ban['1']) && ! isset($systemUser->ban['13'])) {
            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            echo '<div class="gmenu"><form name="form" action="?act=say" method="post">';

            if (! $systemUser->isValid()) {
                echo _t('Name') . ' (max 25):<br><input type="text" name="name" maxlength="25"/><br>';
            }

            echo '<b>' . _t('Message') . '</b> <small>(max 5000)</small>:<br>';
            echo $bbcode->buttons('form', 'msg');
            echo '<textarea rows="' . $systemUser->config->fieldHeight . '" name="msg"></textarea><br>';

            if (! $systemUser->isValid()) {
                // CAPTCHA для гостей
                $captcha = new Batumibiz\Captcha\Captcha;
                $code = $captcha->generateCode();
                $_SESSION['code'] = $code;

                echo '<img alt="' . _t('Verification code') . '" width="' . $captcha->width . '" height="' . $captcha->height . '" src="' . $captcha->generateImage($code) . '"/><br />' .
                    '<input type="text" size="5" maxlength="5"  name="code"/>&#160;' . _t('Symbols on the picture') . '<br><br>';
            }
            echo '<input type="hidden" name="token" value="' . $token . '"/>' .
                '<input type="submit" name="submit" value="' . _t('Send') . '"/></form></div>';
        } else {
            echo '<div class="rmenu">' . _t('For registered users only') . '</div>';
        }

        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `adm`='" . (isset($_SESSION['ga']) ? 1 : 0) . "'")->fetchColumn();
        echo '<div class="phdr"><b>' . _t('Comments') . '</b></div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $kmess) . '</div>';
        }

        if ($total) {
            if (isset($_SESSION['ga']) && ($systemUser->rights >= 1 || in_array($systemUser->id, $guestAccess))) {
                // Запрос для Админ клуба
                echo '<div class="rmenu"><b>АДМИН-КЛУБ</b></div>';
                $req = $db->query("SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='1' ORDER BY `time` DESC LIMIT " . $start . ',' . $kmess);
            } else {
                // Запрос для обычной Гастивухи
                $req = $db->query("SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='0' ORDER BY `time` DESC LIMIT " . $start . ',' . $kmess);
            }

            for ($i = 0; $res = $req->fetch(); ++$i) {
                $text = '';
                echo $i % 2 ? '<div class="list2">' : '<div class="list1">';

                if (! $res['id']) {
                    // Запрос по гостям
                    $res_g = $db->query("SELECT `lastdate` FROM `cms_sessions` WHERE `session_id` = '" . md5($res['ip'] . $res['browser']) . "' LIMIT 1")->fetch();
                    $res['lastdate'] = $res_g['lastdate'];
                }

                // Время создания поста
                $text = ' <span class="gray">(' . $tools->displayDate($res['time']) . ')</span>';

                if ($res['user_id']) {
                    // Для зарегистрированных показываем ссылки и смайлы
                    $post = $tools->checkout($res['text'], 1, 1);
                    $post = $tools->smilies($post, $res['rights'] >= 1 ? 1 : 0);
                } else {
                    // Для гостей обрабатываем имя и фильтруем ссылки
                    $res['name'] = $tools->checkout($res['name']);
                    $post = $tools->checkout($res['text'], 0, 2);
                    $post = preg_replace('~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-z\.-]+\.[0-9a-z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
                        '###', $post);
                    $replace = [
                        '.ru'   => '***',
                        '.com'  => '***',
                        '.biz'  => '***',
                        '.cn'   => '***',
                        '.in'   => '***',
                        '.net'  => '***',
                        '.org'  => '***',
                        '.info' => '***',
                        '.mobi' => '***',
                        '.wen'  => '***',
                        '.kmx'  => '***',
                        '.h2m'  => '***',
                    ];

                    $post = strtr($post, $replace);
                }

                if ($res['edit_count']) {
                    // Если пост редактировался, показываем кем и когда
                    $post .= '<br /><span class="gray"><small>Изм. <b>' . $res['edit_who'] . '</b> (' . $tools->displayDate($res['edit_time']) . ') <b>[' . $res['edit_count'] . ']</b></small></span>';
                }

                if (! empty($res['otvet'])) {
                    // Ответ Администрации
                    $otvet = $tools->checkout($res['otvet'], 1, 1);
                    $otvet = $tools->smilies($otvet, 1);
                    $post .= '<div class="reply"><b>' . $res['admin'] . '</b>: (' . $tools->displayDate($res['otime']) . ')<br>' . $otvet . '</div>';
                }

                if ($systemUser->rights >= 6) {
                    $subtext = '<a href="?act=otvet&amp;id=' . $res['gid'] . '">' . _t('Reply') . '</a>' .
                        ($systemUser->rights >= $res['rights'] ? ' | <a href="?act=edit&amp;id=' . $res['gid'] . '">' . _t('Edit') . '</a> | <a href="?act=delpost&amp;id=' . $res['gid'] . '">' . _t('Delete') . '</a>' : '');
                } else {
                    $subtext = '';
                }

                $arg = [
                    'header' => $text,
                    'body'   => $post,
                    'sub'    => $subtext,
                ];

                echo $tools->displayUser($res, $arg);
                echo '</div>';
            }
        } else {
            echo '<div class="menu"><p>' . _t('The guestbook is empty.<br><strong>Be the first! :)</strong>') . '</p></div>';
        }

        echo '<div class="phdr">' . _t('Total') . ': ' . $total . '</div>';

        if ($total > $kmess) {
            echo '<div class="topmenu">' . $tools->displayPagination('?', $start, $total, $kmess) . '</div>' .
                '<p><form method="get"><input type="text" name="page" size="2"/>' .
                '<input type="submit" value="' . _t('To Page') . ' &gt;&gt;"/></form></p>';
        }

        break;
}

echo $view->render('system::app/old_content', [
    'title'   => $textl,
    'content' => ob_get_clean(),
]);
