<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Carbon\Carbon;
use Forum\ForumUtils;
use Forum\Models\ForumMessage;
use Forum\Models\ForumSection;
use Forum\Models\ForumTopic;
use Forum\Models\ForumUnread;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Users\User;
use Johncms\Validator\Validator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 */

/** @var User $user */
$user = di(User::class);

try {
    $current_section = (new ForumSection())->where('section_type', 1)->where('id', $id)->firstOrFail();
} catch (ModelNotFoundException $exception) {
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('New Topic'),
            'type'          => 'alert-danger',
            'message'       => __('Wrong data'),
            'back_url'      => '/forum/',
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

// Check access
if (
    ! $user->isValid()
    || isset($user->ban['1'])
    || isset($user->ban['11'])
    || (! $user->rights && $config['mod_forum'] === 3)
) {
    http_response_code(403);
    echo $view->render(
        'system::pages/result',
        [
            'title'         => __('Access forbidden'),
            'type'          => 'alert-danger',
            'message'       => __('Access forbidden'),
            'back_url'      => $current_section->url,
            'back_url_name' => __('Go to Section'),
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
            'title'         => __('New Topic'),
            'type'          => 'alert-danger',
            'message'       => sprintf(__('You cannot add the message so often<br>Please, wait %d sec.'), $flood),
            'back_url'      => $current_section->url . '&amp;start=' . $start,
            'back_url_name' => __('Back'),
        ]
    );
    exit;
}

$th = filter_has_var(INPUT_POST, 'th')
    ? filter_var($_POST['th'], FILTER_SANITIZE_SPECIAL_CHARS, ['flag' => FILTER_FLAG_ENCODE_HIGH])
    : '';

$msg = isset($_POST['msg']) ? trim($_POST['msg']) : '';
$msg = preg_replace_callback(
    '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
    'forum_link',
    $msg
);

$errors = [];

if (isset($_POST['submit'])) {
    $msg = preg_replace_callback(
        '~\\[url=(http://.+?)\\](.+?)\\[/url\\]|(http://(www.)?[0-9a-zA-Z\.-]+\.[0-9a-zA-Z]{2,6}[0-9a-zA-Z/\?\.\~&amp;_=/%-:#]*)~',
        'forum_link',
        $msg
    );

    $data = [
        'name'       => $th,
        'message'    => $msg,
        'csrf_token' => $_POST['csrf_token'],
    ];

    $messages = [
        'isEmpty'              => __('Value is required and can\'t be empty'),
        'stringLengthTooShort' => __('The input is less than %min% characters long'),
        'stringLengthTooLong'  => __('The input is more than %max% characters long'),
        'modelExists'          => __('A record matching the input was found'),
    ];

    $rules = [
        'name'       => [
            'NotEmpty',
            'StringLength'   => ['min' => 3, 'max' => 200],
            'ModelNotExists' => [
                'model'   => ForumTopic::class,
                'field'   => 'name',
                'exclude' => static function ($query) use ($id) {
                    $query->where('section_id', $id);
                },
            ],
        ],
        'message'    => [
            'NotEmpty',
            'StringLength'   => ['min' => 4],
            'ModelNotExists' => [
                'model'   => ForumMessage::class,
                'field'   => 'text',
                'exclude' => static function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ],
        ],
        'csrf_token' => ['Csrf'],
    ];

    $validator = new Validator($data, $rules, $messages);

    if ($validator->isValid()) {
        $topic = (new ForumTopic())->create(
            [
                'section_id'     => $current_section->id,
                'created_at'     => Carbon::now(),
                'user_id'        => $user->id,
                'user_name'      => $user->name,
                'name'           => $th,
                'last_post_date' => time(),
                'post_count'     => 0,
                'curators'       => $current_section->access === 1 ? [$user->id => $user->name] : [],
            ]
        );

        /** @var Johncms\System\Http\Environment $env */
        $env = di(Johncms\System\Http\Environment::class);

        $message = (new ForumMessage())->create(
            [
                'topic_id'     => $topic->id,
                'date'         => time(),
                'user_id'      => $user->id,
                'user_name'    => $user->name,
                'ip'           => $env->getIp(),
                'ip_via_proxy' => $env->getIpViaProxy(),
                'user_agent'   => $env->getUserAgent(),
                'text'         => $msg,
            ]
        );

        // Пересчитаем топик
        $tools->recountForumTopic($topic->id);

        // Записываем счетчик постов юзера
        $user->update(
            [
                'postforum' => ($user->postforum + 1),
                'lastpost'  => time(),
            ]
        );

        (new ForumUnread())->create(
            [
                'topic_id' => $topic->id,
                'user_id'  => $user->id,
                'time'     => time(),
            ]
        );

        if (isset($_POST['addfiles']) && $_POST['addfiles'] == 1) {
            header("Location: ?id=" . $message->id . "&act=addfile");
        } else {
            header("Location: " . htmlspecialchars_decode($topic->url));
        }
        exit;
    }

    $errors = $validator->getErrors();
}

$msg_pre = $tools->checkout($msg, 1, 1);
$msg_pre = $tools->smilies($msg_pre, $user->rights ? 1 : 0);
$msg_pre = preg_replace('#\[c\](.*?)\[/c\]#si', '<div class="quote">\1</div>', $msg_pre);

ForumUtils::buildBreadcrumbs($current_section->parent, $current_section->name, $current_section->url);
$nav_chain->add(__('New Topic'));

$view->addData(
    [
        'title'      => __('New Topic'),
        'page_title' => __('New Topic'),
    ]
);

echo $view->render(
    'forum::new_topic',
    [
        'settings_forum'    => $set_forum,
        'id'                => $id,
        'th'                => $th,
        'add_files'         => isset($_POST['addfiles']),
        'msg'               => isset($_POST['msg']) ? $tools->checkout($_POST['msg'], 0, 0) : '',
        'bbcode'            => di(Johncms\System\Legacy\Bbcode::class)->buttons('new_topic', 'msg'),
        'back_url'          => $current_section->url,
        'show_post_preview' => $msg && $th && ! isset($_POST['submit']),
        'preview_message'   => $msg_pre,
        'errors'            => $errors,
    ]
);
