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
use Carbon\Carbon;
use Forum\Models\ForumFile;
use Forum\Models\ForumMessage;
use Forum\Models\ForumSection;
use Forum\Models\ForumTopic;
use Forum\Models\ForumUnread;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Johncms\Notifications\Notification;
use Johncms\System\Legacy\Tools;
use Johncms\System\Users\User;
use Johncms\Counters;
use Johncms\System\View\Extension\Assets;
use Johncms\System\View\Render;
use Johncms\NavChain;
use Johncms\System\i18n\Translator;
use Johncms\Users\GuestSession;

defined('_IN_JOHNCMS') || die('Error: restricted access');

/**
 * @var Assets $assets
 * @var Counters $counters
 * @var PDO $db
 * @var Tools $tools
 * @var User $user
 * @var Render $view
 * @var NavChain $nav_chain
 */
$assets = di(Assets::class);
$config = di('config')['johncms'];
$counters = di('counters');
$db = di(PDO::class);
$user = di(User::class);
$tools = di(Tools::class);
$view = di(Render::class);
$nav_chain = di(NavChain::class);

// Register the module languages domain and folder
di(Translator::class)->addTranslationDomain('forum', __DIR__ . '/locale');

// Регистрируем Namespace для шаблонов модуля
$view->addFolder('forum', __DIR__ . '/templates/');

// Регистрируем автозагрузчик
$loader = new Loader();
$loader->register();
$loader->addPrefix('Forum', __DIR__ . '/lib');

// Добавляем раздел в навигационную цепочку
$nav_chain->add(__('Forum'), '/forum/');

$id = isset($_REQUEST['id']) ? abs((int) ($_REQUEST['id'])) : 0;
$act = isset($_GET['act']) ? trim($_GET['act']) : '';
$mod = isset($_GET['mod']) ? trim($_GET['mod']) : '';
$do = isset($_REQUEST['do']) ? trim($_REQUEST['do']) : false;

if (isset($_SESSION['ref'])) {
    unset($_SESSION['ref']);
}

// Настройки форума
$set_forum_default = [
    'farea'    => 0,
    'upfp'     => 0,
    'preview'  => 1,
    'postclip' => 1,
    'postcut'  => 2,
];
$set_forum = [];
if ($user->isValid() && ! empty($user->set_forum)) {
    $set_forum = unserialize($user->set_forum, ['allowed_classes' => false]);
}
$set_forum = array_merge($set_forum_default, (array) $set_forum);

$user_rights_names = [
    3 => __('Forum moderator'),
    4 => __('Download moderator'),
    5 => __('Library moderator'),
    6 => __('Super moderator'),
    7 => __('Administrator'),
    9 => __('Supervisor'),
];

// Ограничиваем доступ к Форуму
$error = '';

if (! $config['mod_forum'] && $user->rights < 7) {
    $error = __('Forum is closed');
} elseif ($config['mod_forum'] === 1 && ! $user->isValid()) {
    $error = __('For registered users only');
}

if ($error) {
    echo $view->render(
        'system::pages/result',
        [
            'title'   => __('Forum'),
            'type'    => 'alert-danger',
            'message' => $error,
        ]
    );
    exit;
}
$show_type = $_REQUEST['type'] ?? 'section';

// Переключаем режимы работы
$mods = [
    'addfile',
    'addvote',
    'close',
    'deltema',
    'delvote',
    'editpost',
    'editvote',
    'file',
    'files',
    'filter',
    'loadtem',
    'massdel',
    'new',
    'nt',
    'per',
    'show_post',
    'ren',
    'restore',
    'say',
    'search',
    'tema',
    'users',
    'vip',
    'vote',
    'who',
    'curators',
];

if ($act && ($key = array_search($act, $mods)) !== false && file_exists(__DIR__ . '/includes/' . $mods[$key] . '.php')) {
    require __DIR__ . '/includes/' . $mods[$key] . '.php';
} else {
    if (! $user->isValid()) {
        if (isset($_GET['newup'])) {
            $_SESSION['uppost'] = 1;
        }

        if (isset($_GET['newdown'])) {
            $_SESSION['uppost'] = 0;
        }
    }

    if ($id) {
        // Определяем тип запроса (каталог, или тема)
        if ($show_type === 'topic') {
            $type = $db->query("SELECT * FROM `forum_topic` WHERE `id`= '${id}'");
        } else {
            $type = $db->query("SELECT * FROM `forum_sections` WHERE `id`= '${id}'");
        }

        if (! $type->rowCount()) {
            // Если темы не существует, показываем ошибку
            echo $view->render(
                'system::pages/result',
                [
                    'title'    => __('Forum'),
                    'type'     => 'alert-danger',
                    'message'  => __('Topic has been deleted or does not exists'),
                    'back_url' => '/forum/',
                ]
            );
            exit;
        }

        $type1 = $type->fetch();

        // Nav chain
        if ($show_type === 'topic') {
            $parent = $type1['section_id'];
        } else {
            $parent = $type1['parent'];
        }
        $tree = [];
        $tools->getSections($tree, $parent);
        foreach ($tree as $item) {
            $nav_chain->add($item['name'], '/forum/?' . ($item['section_type'] == 1 ? 'type=topics&amp;' : '') . 'id=' . $item['id']);
        }

        $nav_chain->add($type1['name']);

        switch ($show_type) {
            case 'section':
                try {
                    $current_section = (new ForumSection())->withCount('categoryFiles')->findOrFail($id);
                } catch (ModelNotFoundException $exception) {
                    pageNotFound();
                }

                // List of forum sections
                $sections = (new ForumSection())
                    ->withCount(['subsections', 'topics'])
                    ->where('parent', '=', $id)
                    ->orderBy('sort')
                    ->get();

                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

                // Считаем пользователей онлайн
                $online = [
                    'online_u' => (new \Johncms\Users\User())->online()->where('place', 'like', '/forum%')->count(),
                    'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum%')->count(),
                ];

                echo $view->render(
                    'forum::section',
                    [
                        'title'        => $current_section->name,
                        'page_title'   => $current_section->name,
                        'id'           => $current_section->id,
                        'sections'     => $sections,
                        'online'       => $online,
                        'total'        => $sections->count(),
                        'files_count'  => $tools->formatNumber($current_section->category_files_count),
                        'unread_count' => $tools->formatNumber($counters->forumUnreadCount()),
                    ]
                );
                break;

            case 'topics':
                try {
                    $current_section = (new ForumSection())->withCount('sectionFiles')->findOrFail($id);
                } catch (ModelNotFoundException $exception) {
                    pageNotFound();
                }

                // List of forum topics
                $topics = (new ForumTopic())
                    ->read()
                    ->where('section_id', '=', $id)
                    ->orderByDesc('pinned')
                    ->orderByDesc('last_post_date')
                    ->paginate($user->config->kmess);

                // Check access to create topic
                $create_access = false;
                if (($user->isValid() && ! isset($user->ban['1']) && ! isset($user->ban['11']) && $config['mod_forum'] != 4) || $user->rights) {
                    $create_access = true;
                }

                unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

                // Считаем пользователей онлайн
                $online = [
                    'online_u' => (new \Johncms\Users\User())->online()->where('place', 'like', '/forum%')->count(),
                    'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum%')->count(),
                ];

                echo $view->render(
                    'forum::topics',
                    [
                        'pagination'    => $topics->render(),
                        'id'            => $id,
                        'create_access' => $create_access,
                        'title'         => $current_section->name,
                        'page_title'    => $current_section->name,
                        'topics'        => $topics->getItems(),
                        'online'        => $online,
                        'total'         => $topics->total(),
                        'files_count'   => $tools->formatNumber($current_section->section_files_count),
                        'unread_count'  => $tools->formatNumber($counters->forumUnreadCount()),
                    ]
                );
                break;

            case 'topic':
                // Getting data for the current topic
                try {
                    $current_topic = (new ForumTopic())->withCount('files')->findOrFail($id);
                } catch (ModelNotFoundException $exception) {
                    pageNotFound();
                }

                $access = 0;
                if ($user->isValid()) {
                    // Mark the topic as read
                    (new ForumUnread())->updateOrInsert(['topic_id' => $id, 'user_id' => $user->id], ['time' => time()]);

                    $online = [
                        'online_u' => (new \Johncms\Users\User())->online()->where('place', 'like', '/forum?type=topic&id=' . $id . '%')->count(),
                        'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum?type=topic&id=' . $id . '%')->count(),
                    ];

                    $current_section = $current_topic->section;
                    $access = $current_section->access;
                }

                // Increasing the number of views
                if (empty($_SESSION['viewed_topics']) || ! in_array($current_topic->id, $_SESSION['viewed_topics'])) {
                    $current_topic->update(['view_count' => $current_topic->view_count + 1]);
                    $_SESSION['viewed_topics'][] = $current_topic->id;
                }

                // Задаем правила сортировки (новые внизу / вверху)
                if ($user->isValid()) {
                    $order = $set_forum['upfp'] ? 'DESC' : 'ASC';
                } else {
                    $order = (empty($_SESSION['uppost']) || $_SESSION['uppost'] == 0) ? 'ASC' : 'DESC';
                }

                $filter_by_users = [];
                $filter = isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] == $id ? 1 : 0;
                if ($filter && ! empty($_SESSION['fsort_users'])) {
                    $filter_by_users = unserialize($_SESSION['fsort_users'], ['allowed_classes' => false]);
                }

                // List of messages
                $message = (new ForumMessage())
                    ->users()
                    ->with('files')
                    ->where('topic_id', '=', $id);

                // Filter by users
                if (! empty($filter_by_users)) {
                    $message->whereIn('user_id', $filter_by_users);
                }

                $message = $message->orderBy('id', $order)
                    ->paginate($user->config->kmess);

                // Счетчик постов темы
                $total = $message->total();

                $poll_data = [];
                if ($current_topic->has_poll) {
                    $clip_forum = isset($_GET['clip']) ? '&amp;clip' : '';
                    $topic_vote = $db->query(
                        "SELECT `fvt`.`name`, `fvt`.`time`, `fvt`.`count`, (
SELECT COUNT(*) FROM `cms_forum_vote_users` WHERE `user`='" . $user->id . "' AND `topic`='" . $id . "') as vote_user
FROM `cms_forum_vote` `fvt` WHERE `fvt`.`type`='1' AND `fvt`.`topic`='" . $id . "' LIMIT 1"
                    )->fetch();
                    $topic_vote['name'] = $tools->checkout($topic_vote['name'], 0, 0);
                    $poll_data['poll'] = $topic_vote;
                    $poll_data['show_form'] = (! $type1['closed'] && ! isset($_GET['vote_result']) && $user->isValid() && $topic_vote['vote_user'] == 0);
                    $poll_data['results'] = [];

                    $vote_result = $db->query("SELECT `id`, `name`, `count` FROM `cms_forum_vote` WHERE `type`='2' AND `topic`='" . $id . "' ORDER BY `id` ASC");
                    while ($vote = $vote_result->fetch()) {
                        $vote['name'] = $tools->checkout($vote['name'], 0, 1);
                        $count_vote = $topic_vote['count'] ? round(100 / $topic_vote['count'] * $vote['count']) : 0;

                        $color = null;
                        if ($count_vote > 0 && $count_vote <= 25) {
                            $color = 'bg-success';
                        } elseif ($count_vote > 25 && $count_vote <= 50) {
                            $color = 'bg-info';
                        } elseif ($count_vote > 50 && $count_vote <= 75) {
                            $color = 'bg-warning';
                        } elseif ($count_vote > 75 && $count_vote <= 100) {
                            $color = 'bg-danger';
                        }

                        $vote['color_class'] = $color;
                        $vote['vote_percent'] = $count_vote;
                        $poll_data['results'][] = $vote;
                    }

                    $poll_data['clip'] = $clip_forum;
                }

                // Получаем данные о кураторах темы
                $curators = ! empty($type1['curators']) ? unserialize($type1['curators'], ['allowed_classes' => false]) : [];
                $curator = false;

                if ($user->rights < 6 && $user->rights != 3 && $user->isValid() && array_key_exists($user->id, $curators)) {
                    $curator = true;
                }

                // Fixed first post
                $first_message = null;
                if (isset($_GET['clip']) || ($set_forum['postclip'] == 2 && ($set_forum['upfp'] ? $start < (ceil($total - $user->config->kmess)) : $start > 0))) {
                    $first_message = (new ForumMessage())
                        ->users()
                        ->where('topic_id', '=', $id)
                        ->orderBy('id')
                        ->first();
                }

                /** @var Collection $messages */
                $messages = $message->getItems()->map(
                    static function (ForumMessage $message) use ($user, $curator, $set_forum, $access, &$i, $start, $total) {
                        if (
                            (($user->rights === 3 || $user->rights >= 6 || $curator) && $user->rights >= $message->rights)
                            || ($i === 1 && $access === 2 && $message->user_id === $user->id)
                            || ($message->user_id === $user->id && ! $set_forum['upfp'] && ($start + $i) === $total && $message->date > time() - 300)
                            || ($message->user_id === $user->id && $set_forum['upfp'] && $start === 0 && $i === 1 && $message->date > time() - 300)
                        ) {
                            $message->can_edit = true;
                        }

                        if ($user->id !== $message->user_id && $user->isValid()) {
                            $message->reply_url = '/forum/?act=say&amp;type=reply&amp;id=' . $message->id . '&amp;start=' . $start;
                            $message->quote_url = '/forum/?act=say&amp;type=reply&amp;id=' . $message->id . '&amp;start=' . $start . '&amp;cyt';
                        }

                        $i++;
                        return $message;
                    }
                );

                if ($user->isValid()) {
                    // Помечаем уведомления прочитанными
                    $post_ids = $messages->pluck('id')->all();

                    $notifications = (new Notification())
                        ->where('module', '=', 'forum')
                        ->where('event_type', '=', 'new_message')
                        ->whereNull('read_at')
                        ->whereIn('entity_id', $post_ids)
                        ->update(['read_at' => Carbon::now()]);
                }

                // Нижнее поле "Написать"
                $write_access = false;
                if (($user->isValid() && ! $current_topic->closed && $config['mod_forum'] != 3 && $access != 4) || ($user->rights >= 7)) {
                    $write_access = true;
                    if ($set_forum['farea']) {
                        $token = mt_rand(1000, 100000);
                        $_SESSION['token'] = $token;
                    }
                }

                // Список кураторов
                $curators_array = [];
                if ($curators) {
                    foreach ($curators as $key => $value) {
                        $curators_array[] = '<a href="/profile/?user=' . $key . '">' . $value . '</a>';
                    }
                }

                echo $view->render(
                    'forum::topic',
                    [
                        'first_post'       => $first_message,
                        'topic'            => $current_topic,
                        'topic_vote'       => $topic_vote ?? null,
                        'curators_array'   => $curators_array,
                        'view_count'       => $current_topic->view_count,
                        'pagination'       => $message->render($user->config->kmess),
                        'start'            => $start,
                        'id'               => $id,
                        'token'            => $token ?? null,
                        'bbcode'           => di(Johncms\System\Legacy\Bbcode::class)->buttons('new_message', 'msg'),
                        'settings_forum'   => $set_forum,
                        'write_access'     => $write_access,
                        'title'            => $current_topic->name,
                        'page_title'       => $current_topic->name,
                        'messages'         => $messages ?? [],
                        'online'           => $online ?? [],
                        'total'            => $total,
                        'files_count'      => $tools->formatNumber($current_topic->files_count),
                        'unread_count'     => $tools->formatNumber($counters->forumUnreadCount()),
                        'filter_by_author' => $filter,
                        'poll_data'        => $poll_data,
                    ]
                );
                break;

            default:
                echo $view->render(
                    'system::pages/result',
                    [
                        'title'         => __('Wrong data'),
                        'type'          => 'alert-danger',
                        'message'       => __('Wrong data'),
                        'back_url'      => '/forum/',
                        'back_url_name' => __('Go to Forum'),
                    ]
                );
                break;
        }
    } else {
        // Forum categories
        $sections = (new ForumSection())
            ->withCount('subsections')
            ->with('subsections')
            ->where('parent', '=', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->get();

        // Считаем файлы
        $files_count = (new ForumFile())->count();

        // Считаем пользователей онлайн
        $online = [
            'online_u' => (new \Johncms\Users\User())->online()->where('place', 'like', '/forum%')->count(),
            'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum%')->count(),
        ];

        unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

        echo $view->render(
            'forum::index',
            [
                'title'        => __('Forum'),
                'page_title'   => __('Forum'),
                'sections'     => $sections,
                'online'       => $online,
                'files_count'  => $tools->formatNumber($files_count),
                'unread_count' => $tools->formatNumber($counters->forumUnreadCount()),
            ]
        );
    }
}
