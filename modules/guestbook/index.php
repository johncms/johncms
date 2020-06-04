<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Aura\Autoload\Loader;
use Guestbook\Models\Guestbook;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Http\Environment;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\Validator\Validator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/** @var PDO $db */
$db = di(PDO::class);

/** @var User $user */
$user = di(User::class);

/** @var Tools $tools */
$tools = di(Tools::class);

/** @var Environment $env */
$env = di(Environment::class);

/** @var Bbcode $bbcode */
$bbcode = di(Bbcode::class);

/** @var Render $view */
$view = di(Render::class);

/** @var NavChain $nav_chain */
$nav_chain = di(NavChain::class);

/** @var Request $request */
$request = di(Request::class);

$config = di('config')['johncms'];
$route = di('route');

// Register Namespace for module templates
$view->addFolder('guestbook', __DIR__ . '/templates/');

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('guestbook', __DIR__ . '/locale');

$loader = new Loader();
$loader->register();
$loader->addPrefix('Guestbook', __DIR__ . '/lib');

$id = $request->getQuery('id', 0, FILTER_VALIDATE_INT);
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
$title = isset($_SESSION['ga']) ? __('Admin Club') : __('Guestbook');

$nav_chain->add($title);

// If the guest is closed, display a message and close access (except for Admins)
if (! $config['mod_guest'] && $user->rights < 7) {
    echo $view->render(
        'guestbook::result',
        [
            'title'    => $title,
            'message'  => __('Guestbook is closed'),
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
                (new Guestbook())->where('id', $id)->delete();
                header('Location: ./');
            } else {
                echo $view->render('guestbook::confirm_delete', ['id' => $id]);
            }
        }
        break;

    case 'otvet':
        // Add "admin response"
        if ($user->rights >= 6 && $id) {
            if (
                isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
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
            if (
                isset($_POST['submit'], $_POST['token'], $_SESSION['token'])
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
                        $message = __('All messages older than 1 day were deleted');
                        break;

                    case '2':
                        // Perform a full cleanup
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}'");
                        $message = __('Full clearing is finished');
                        break;
                    default:
                        // Clean messages older than 1 week""
                        $db->exec("DELETE FROM `guest` WHERE `adm`='${adm}' AND `time`<='" . (time() - 604800) . "';");
                        $message = __('All messages older than 1 week were deleted');
                }

                $db->query('OPTIMIZE TABLE `guest`');
                echo $view->render(
                    'guestbook::result',
                    [
                        'title'    => __('Clear guestbook'),
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
            if (isset($_GET['do']) && $_GET['do'] === 'set') {
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
            'access_to_form'    => ($user->isValid() || $config['mod_guest'] === 2) && ! isset($user->ban['1']) && ! isset($user->ban['13']),
            'bbcode'            => $bbcode->buttons('form', 'message'),
            'errors'            => [],
        ];

        $form_data = [
            'name'       => $request->getPost('name', '', FILTER_SANITIZE_STRING),
            'message'    => $request->getPost('message', ''),
            'csrf_token' => $request->getPost('csrf_token', ''),
            'code'       => $request->getPost('code', ''),
        ];

        $form_data = array_map('trim', $form_data);
        $data['form_data'] = $form_data;

        if ($request->getMethod() === 'POST') {
            $messages = [
                'isEmpty'              => __('Value is required and can\'t be empty'),
                'stringLengthTooShort' => __('The input is less than %min% characters long'),
                'stringLengthTooLong'  => __('The input is more than %max% characters long'),
            ];

            $rules = [
                'message'    => [
                    'NotEmpty',
                    'StringLength'   => ['min' => 4],
                    'ModelNotExists' => [
                        'model'   => Guestbook::class,
                        'field'   => 'text',
                        'exclude' => static function ($query) use ($user) {
                            $query->where('user_id', $user->id)->where('time', '>', (time() - 600));
                        },
                    ],
                ],
                'csrf_token' => [
                    'Csrf',
                    'Flood',
                    'Ban' => [
                        'bans' => [1, 13],
                    ],
                ],
            ];

            if (! $user->isValid()) {
                $rules['name'] = [
                    'NotEmpty',
                    'StringLength' => ['min' => 3, 'max' => 25],
                ];
                $rules['code'] = [
                    'Captcha',
                ];
            }

            $validator = new Validator($form_data, $rules, $messages);

            if ($validator->isValid()) {
                $new_message = (new Guestbook())->create(
                    [
                        'adm'     => ! $data['is_guestbook'],
                        'time'    => time(),
                        'user_id' => $user->id,
                        'name'    => $user->isValid() ? $user->name : $form_data['name'],
                        'text'    => $form_data['message'],
                        'ip'      => $env->getIp(false),
                        'browser' => $env->getUserAgent(),
                        'otvet'   => '',
                    ]
                );
                if ($user->isValid()) {
                    $post_guest = $user->postguest + 1;
                    (new \Johncms\Users\User())
                        ->where('id', $user->id)
                        ->update(
                            [
                                'postguest' => $post_guest,
                                'lastpost'  => time(),
                            ]
                        );
                }
                $data['form_data']['message'] = '';
            } else {
                $data['errors'] = $validator->getErrors();
            }
            unset($_SESSION['code']);
        }

        if ($data['access_to_form'] && ! $user->isValid()) {
            // CAPTCHA for guests
            $code = (new Mobicms\Captcha\Code())->generate();
            $_SESSION['code'] = $code;
            $data['captcha'] = (new Mobicms\Captcha\Image($code))->generate();
        }

        $admin_club = (isset($_SESSION['ga']) && ($user->rights >= 1 || in_array($user->id, $guestAccess)));
        $messages = (new Guestbook())->with('user')->where('adm', $admin_club)->orderByDesc('time')->paginate($user->config->kmess);

        $data['items'] = $messages;
        $data['pagination'] = $messages->render();

        echo $view->render(
            'guestbook::index',
            [
                'title' => $title,
                'data'  => $data,
            ]
        );
        break;
}
