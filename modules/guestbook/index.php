<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Johncms\Api\BbcodeInterface;
use Johncms\System\Config\Config;
use Johncms\Api\EnvironmentInterface;
use Johncms\Api\NavChainInterface;
use Johncms\Api\ToolsInterface;
use Johncms\System\Users\User;
use Johncms\View\Render;
use Zend\I18n\Translator\Translator;

defined('_IN_JOHNCMS') || die('Error: restricted access');
ob_start(); // Перехват вывода скриптов без шаблона

/**
 * @var BbcodeInterface $bbcode
 * @var Config $config
 * @var PDO $db
 * @var EnvironmentInterface $env
 * @var ToolsInterface $tools
 * @var User $user
 * @var Render $view
 * @var NavChainInterface $nav_chain
 */

$db = di(PDO::class);
$user = di(User::class);
$tools = di(ToolsInterface::class);
$env = di(EnvironmentInterface::class);
$bbcode = di(BbcodeInterface::class);
$config = di(Config::class);
$view = di(Render::class);
$nav_chain = di(NavChainInterface::class);
$route = di('route');

// Register Namespace for module templates
$view->addFolder('guestbook', __DIR__ . '/templates/');

// Register the module languages folder
di(Translator::class)->addTranslationFilePattern('gettext', __DIR__ . '/locale', '/%s/default.mo');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = $route['action'] ?? '';

// Here you can (separated by commas) add the ID of those users who are not in the administration.
// But who are allowed to read and write in the admin club
$guestAccess = [];

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Check the access rights to the Admin Club
if (isset($_SESSION['ga']) && $user->rights < 1 && ! in_array($user->id, $guestAccess)) {
    unset($_SESSION['ga']);
}

// Set page headers
$textl = isset($_SESSION['ga']) ? _t('Admin Club') : _t('Guestbook');

$nav_chain->add($textl);

// If the guest is closed, display a message and close access (except for Admins)
if (! $config->mod_guest && $user->rights < 7) {
    echo $view->render(
        'guestbook::result',
        [
            'title'    => $textl,
            'message'  => _t('Guestbook is closed'),
            'type'     => 'error',
            'back_url' => '/',
        ]
    );
    exit;
}

switch ($act) {
    case 'delpost':
        // Delete a single post
        if ($user->rights >= 6 && $id) {
            if (isset($_GET['yes'])) {
                $db->exec('DELETE FROM `guest` WHERE `id` = ' . $id);
                header('Location: ./');
            } else {
                echo $view->render('guestbook::confirm_delete', ['id' => $id]);
            }
        }
        break;

    case 'say':
        // Add a new post
        $admset = isset($_SESSION['ga']) ? 1 : 0; // Задаем куда вставляем, в Админ клуб (1), или в Гастивуху (0)
        // Receive and process data
        $name = isset($_POST['name']) ? mb_substr(trim($_POST['name']), 0, 20) : '';
        $msg = isset($_POST['msg']) ? mb_substr(trim($_POST['msg']), 0, 5000) : '';
        $trans = isset($_POST['msgtrans']) ? 1 : 0;
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        $from = $user->isValid() ? $user->name : $name;
        // Check for errors
        $error = [];
        $flood = false;

        if (! isset($_POST['token']) || ! isset($_SESSION['token']) || $_POST['token'] != $_SESSION['token']) {
            $error[] = _t('Wrong data');
        }

        if (! $user->isValid() && empty($name)) {
            $error[] = _t('You have not entered a name');
        }

        if (empty($msg)) {
            $error[] = _t('You have not entered the message');
        }

        if (! empty($user->ban['1']) || ! empty($user->ban['13'])) {
            $error[] = _t('Access forbidden');
        }

        // CAPTCHA for guests
        if (! $user->isValid() && (empty($code) || mb_strlen($code) < 3 || strtolower($code) != strtolower($_SESSION['code']))) {
            $error[] = _t('The security code is not correct');
        }

        unset($_SESSION['code']);

        if ($user->isValid()) {
            // Anti-flood for registered users
            $flood = $tools->antiflood();
        } else {
            // Anti-flood for guests
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
            // Check for duplicate messages
            $req = $db->query("SELECT * FROM `guest` WHERE `user_id` = '" . $user->id . "' ORDER BY `time` DESC");
            $res = $req->fetch();

            if ($res['text'] == $msg) {
                header('location: ./');
                exit;
            }
        }

        if (! $error) {
            // Insert the message into the database
            $db->prepare(
                "INSERT INTO `guest` SET
                `adm` = ?,
                `time` = ?,
                `user_id` = ?,
                `name` = ?,
                `text` = ?,
                `ip` = ?,
                `browser` = ?,
                `otvet` = ''
            "
            )->execute(
                [
                    $admset,
                    time(),
                    $user->id,
                    $from,
                    $msg,
                    $env->getIp(),
                    $env->getUserAgent(),
                ]
            );

            // Fix the time of the last post (antispam)
            if ($user->isValid()) {
                $postguest = $user->postguest + 1;
                $db->exec("UPDATE `users` SET `postguest` = '${postguest}', `lastpost` = '" . time() . "' WHERE `id` = " . $user->id);
            }

            header('location: ./');
        } else {
            echo $view->render(
                'guestbook::result',
                [
                    'title'    => _t('Add message'),
                    'message'  => $error,
                    'type'     => 'error',
                    'back_url' => '/guestbook/',
                ]
            );
        }
        break;

    case 'otvet':
        // Add "admin response"
        if ($user->rights >= 6 && $id) {
            if (isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $reply = isset($_POST['otv']) ? mb_substr(trim($_POST['otv']), 0, 5000) : '';
                $db->exec(
                    "UPDATE `guest` SET
                    `admin` = '" . $user->name . "',
                    `otvet` = " . $db->quote($reply) . ",
                    `otime` = '" . time() . "'
                    WHERE `id` = '${id}'
                "
                );
                header('location: ./');
            } else {
                $req = $db->query("SELECT * FROM `guest` WHERE `id` = '${id}'");
                $res = $req->fetch();
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
                echo $view->render(
                    'guestbook::reply',
                    [
                        'id'         => $id,
                        'token'      => $token,
                        'message'    => $res,
                        'reply_text' => $tools->checkout($res['otvet'], 0, 0),
                        'text'       => $tools->checkout($res['text'], 1, 1),
                        'bbcode'     => $bbcode->buttons('form', 'otv'),
                    ]
                );
            }
        }
        break;

    case 'edit':
        // Edit post
        if ($user->rights >= 6 && $id) {
            if (isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
                && $_POST['token'] == $_SESSION['token']
            ) {
                $res = $db->query("SELECT `edit_count` FROM `guest` WHERE `id`='${id}'")->fetch();
                $edit_count = $res['edit_count'] + 1;
                $msg = isset($_POST['msg']) ? mb_substr(trim($_POST['msg']), 0, 5000) : '';

                $db->prepare(
                    '
                  UPDATE `guest` SET
                  `text` = ?,
                  `edit_who` = ?,
                  `edit_time` = ?,
                  `edit_count` = ?
                  WHERE `id` = ?
                '
                )->execute(
                    [
                        $msg,
                        $user->name,
                        time(),
                        $edit_count,
                        $id,
                    ]
                );

                header('location: ./');
            } else {
                $token = mt_rand(1000, 100000);
                $_SESSION['token'] = $token;
                $res = $db->query("SELECT * FROM `guest` WHERE `id` = '${id}'")->fetch();
                $text = htmlentities($res['text'], ENT_QUOTES, 'UTF-8');

                echo $view->render(
                    'guestbook::edit',
                    [
                        'id'      => $id,
                        'token'   => $token,
                        'message' => $res,
                        'text'    => $text,
                        'bbcode'  => $bbcode->buttons('form', 'msg'),
                    ]
                );
            }
        }
        break;

    case 'clean':
        // Cleaning Guest
        if ($user->rights >= 7) {
            if (! empty($_POST)) {
                // We clean the Guest, according to the specified parameters
                $adm = isset($_SESSION['ga']) ? 1 : 0;
                $cl = isset($_POST['cl']) ? (int) ($_POST['cl']) : '';

                switch ($cl) {
                    case '1':
                        // Clean messages older than 1 day
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}' AND `time` < '" . (time() - 86400) . "'");
                        $message = _t('All messages older than 1 day were deleted');
                        break;

                    case '2':
                        // Perform a full cleanup
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}'");
                        $message = _t('Full clearing is finished');
                        break;
                    default:
                        // Clean messages older than 1 week""
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}' AND `time`<='" . (time() - 604800) . "';");
                        $message = _t('All messages older than 1 week were deleted');
                }

                $db->query('OPTIMIZE TABLE `guest`');
                echo $view->render(
                    'guestbook::result',
                    [
                        'title'    => _t('Clear guestbook'),
                        'message'  => $message,
                        'type'     => 'success',
                        'back_url' => '/guestbook/',
                    ]
                );
            } else {
                // Request cleaning options
                echo $view->render('guestbook::clear');
            }
        } else {
            header('Location: /');
            exit;
        }
        break;

    case 'ga':
        // Switching the mode of operation Guest / admin club
        if ($user->rights >= 1 || in_array($user->id, $guestAccess)) {
            if (isset($_GET['do']) && $_GET['do'] == 'set') {
                $_SESSION['ga'] = 1;
            } else {
                unset($_SESSION['ga']);
            }
        }
        header('Location: /guestbook/');
        exit;
        break;
    default:
        $data = [
            'access_to_buttons' => ($user->rights > 0 || in_array($user->id, $guestAccess)),
            'is_guestbook'      => ! isset($_SESSION['ga']),
            'access_to_form'    => ($user->isValid() || $config->mod_guest == 2) && ! isset($user->ban['1']) && ! isset($user->ban['13']),
            'bbcode'            => $bbcode->buttons('form', 'msg'),
            'pagination'        => '',
        ];

        if ($data['access_to_form']) {
            $token = mt_rand(1000, 100000);
            $_SESSION['token'] = $token;
            $data['token'] = $token;

            if (! $user->isValid()) {
                // CAPTCHA for guests
                $code = (new Mobicms\Captcha\Code())->generate();
                $_SESSION['code'] = $code;
                $data['captcha'] = (new Mobicms\Captcha\Image($code))->generate();
            }
        }

        $total = $db->query("SELECT COUNT(*) FROM `guest` WHERE `adm`='" . (isset($_SESSION['ga']) ? 1 : 0) . "'")->fetchColumn();
        $data['total'] = $total;
        if ($total > $user->config->kmess) {
            $data['pagination'] = $tools->displayPagination('?', $start, $total, $user->config->kmess);
        }

        if ($total) {
            if (isset($_SESSION['ga']) && ($user->rights >= 1 || in_array($user->id, $guestAccess))) {
                $req = $db->query(
                    "SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='1' ORDER BY `time` DESC LIMIT " . $start . ',' . $user->config->kmess
                );
            } else {
                // Request for regular
                $req = $db->query(
                    "SELECT `guest`.*, `guest`.`id` AS `gid`, `users`.`rights`, `users`.`lastdate`, `users`.`sex`, `users`.`status`, `users`.`datereg`, `users`.`id`
                FROM `guest` LEFT JOIN `users` ON `guest`.`user_id` = `users`.`id`
                WHERE `guest`.`adm`='0' ORDER BY `time` DESC LIMIT " . $start . ',' . $user->config->kmess
                );
            }

            $items = [];
            while ($res = $req->fetch()) {
                // Tidy up the array for the template
                $item = $res;
                if (! $res['id']) {
                    // Request for guests
                    $res_g = $db->query("SELECT `lastdate` FROM `cms_sessions` WHERE `session_id` = '" . md5($res['ip'] . $res['browser']) . "' LIMIT 1")->fetch();
                    $item['user_lastdate'] = $res_g['lastdate'];
                }

                $item['created'] = $tools->displayDate($res['time']);
                $item['ip'] = long2ip((int) $res['ip']);

                if ($res['user_id']) {
                    // For registered we show links and smiles
                    $post = $tools->checkout($res['text'], 1, 1);
                    $post = $tools->smilies($post, $res['rights'] >= 1 ? 1 : 0);
                } else {
                    // For guests, process the name and filter the links
                    $res['name'] = $tools->checkout($res['name']);
                    $post = $tools->checkout($res['text'], 0, 2);
                    $post = preg_replace(
                        '~\\[url=(https?://.+?)\\](.+?)\\[/url\\]|(https?://(www.)?[0-9a-z\.-]+\.[0-9a-z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
                        '###',
                        $post
                    );
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

                $item['post_text'] = $post;

                $item['reply_text'] = '';
                if (! empty($res['otvet'])) {
                    // Administration Response
                    $otvet = $tools->checkout($res['otvet'], 1, 1);
                    $otvet = $tools->smilies($otvet, 1);
                    $item['reply_text'] = $otvet;
                    $item['reply_time'] = $tools->displayDate($res['otime']);
                }

                if ($res['edit_count']) {
                    $item['edit_time'] = $tools->displayDate($res['edit_time']);
                }

                $item['user_avatar'] = '';
                $avatar = 'users/avatar/' . $item['user_id'] . '.png';
                if (file_exists(UPLOAD_PATH . $avatar)) {
                    $item['user_avatar'] = UPLOAD_PUBLIC_PATH . $avatar;
                }

                $item['message_id'] = $res['gid'];
                $items[] = $item;
            }

            $data['items'] = $items;
        }

        echo $view->render(
            'guestbook::index',
            [
                'title' => $textl,
                'data'  => $data,
            ]
        );
        break;
}
