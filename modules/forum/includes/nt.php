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
use Johncms\System\Http\Request;
use Johncms\Users\User;
use Johncms\Validator\Validator;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var array $config
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\View\Render $view
 * @var Johncms\NavChain $nav_chain
 */

/** @var User $user */
$user = di(User::class);

/** @var Request $request */
$request = di(Request::class);

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
    ! $user->is_valid
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

$data = [
    'name'       => $request->getPost('th', '', FILTER_SANITIZE_SPECIAL_CHARS, ['flag' => FILTER_FLAG_ENCODE_HIGH]),
    'message'    => ForumUtils::topicLink($request->getPost('msg', '')),
    'csrf_token' => $request->getPost('csrf_token', ''),
    'add_files'  => (int) $request->getPost('addfiles', 0),
];

if ($user->rights > 0) {
    $data['meta_keywords'] = $request->getPost('meta_keywords', null, FILTER_SANITIZE_STRING);
    $data['meta_description'] = $request->getPost('meta_description', null, FILTER_SANITIZE_STRING);
}

$errors = [];
if ($request->getPost('submit', null)) {
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

    $validator = new Validator($data, $rules);

    if ($validator->isValid()) {
        $topic = (new ForumTopic())->create(
            [
                'section_id'       => $current_section->id,
                'created_at'       => Carbon::now(),
                'user_id'          => $user->id,
                'user_name'        => $user->name,
                'name'             => $data['name'],
                'meta_keywords'    => $data['meta_keywords'] ?? null,
                'meta_description' => $data['meta_description'] ?? null,
                'last_post_date'   => time(),
                'post_count'       => 0,
                'curators'         => $current_section->access === 1 ? [$user->id => $user->name] : [],
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
                'ip'           => $env->getIp(false),
                'ip_via_proxy' => $env->getIpViaProxy(false),
                'user_agent'   => $env->getUserAgent(),
                'text'         => $data['message'],
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

        if ($data['add_files'] === 1) {
            header("Location: ?id=" . $message->id . "&act=addfile");
        } else {
            header("Location: " . htmlspecialchars_decode($topic->url));
        }
        exit;
    }

    $errors = $validator->getErrors();
}

$msg_pre = $tools->checkout($data['message'], 1, 1);
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
        'th'                => $data['name'],
        'add_files'         => ($data['add_files'] === 1),
        'msg'               => $tools->checkout($data['message'], 0, 0),
        'bbcode'            => di(Johncms\System\Legacy\Bbcode::class)->buttons('new_topic', 'msg'),
        'back_url'          => $current_section->url,
        'show_post_preview' => ! empty($data['name']) && ! empty($data['message']) && ! $request->getPost('submit', null),
        'preview_message'   => $msg_pre,
        'errors'            => $errors,
        'data'              => $data,
    ]
);
