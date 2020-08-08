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
use Forum\Models\ForumTopic;
use Forum\Models\ForumUnread;
use Forum\Models\ForumVote;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;
use Johncms\Counters;
use Johncms\NavChain;
use Johncms\Notifications\Notification;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

/**
 * @var Tools $tools
 * @var Counters $counters
 * @var NavChain $nav_chain
 * @var $config
 * @var $id
 */

/** @var User $user */
$user = di(User::class);

/** @var Request $request */
$request = di(Request::class);

$forum_settings = di('config')['forum']['settings'];

// Getting data for the current topic
try {
    $current_topic = (new ForumTopic());
    if ($forum_settings['file_counters']) {
        $current_topic = $current_topic->withCount('files');
    }
    $current_topic = $current_topic->findOrFail($id);
} catch (ModelNotFoundException $exception) {
    ForumUtils::notFound();
}

// Build breadcrumbs
ForumUtils::buildBreadcrumbs($current_topic->section_id, $current_topic->name);

$access = 0;
if ($user->is_valid) {
    // Mark the topic as read
    (new ForumUnread())->updateOrInsert(['topic_id' => $id, 'user_id' => $user->id], ['time' => time()]);

    $online = [
        'online_u' => (new User())->online()->where('place', 'like', '/forum?type=topic&id=' . $id . '%')->count(),
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
$order = $set_forum['upfp'] ? 'DESC' : 'ASC';

$filter_by_users = [];
$filter = isset($_SESSION['fsort_id']) && $_SESSION['fsort_id'] === $id ? 1 : 0;
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
    ->paginate($user->set_user->kmess);

// Счетчик постов темы
$total = $message->total();

$poll_data = [];
if ($current_topic->has_poll) {
    $clip_forum = isset($_GET['clip']) ? '&amp;clip' : '';
    $topic_vote = (new ForumVote())
        ->voteUser()
        ->where('type', '=', 1)
        ->where('topic', '=', $id)
        ->first();

    $poll_data['show_form'] = (! $current_topic->closed && ! isset($_GET['vote_result']) && $user->is_valid && $topic_vote->vote_user !== 1);
    $poll_data['results'] = [];

    $color_classes = di('config')['forum']['answer_colors'];
    foreach ($topic_vote->answers as $answer) {
        $vote = $answer->toArray();
        $count_vote = $topic_vote->count ? round(100 / $topic_vote->count * $vote['count']) : 0;
        $color = null;
        if ($count_vote > 0 && $count_vote <= 25) {
            $color = $color_classes['0_25'];
        } elseif ($count_vote > 25 && $count_vote <= 50) {
            $color = $color_classes['25_50'];
        } elseif ($count_vote > 50 && $count_vote <= 75) {
            $color = $color_classes['50_75'];
        } elseif ($count_vote > 75 && $count_vote <= 100) {
            $color = $color_classes['75_100'];
        }

        $vote['color_class'] = $color;
        $vote['vote_percent'] = $count_vote;
        $poll_data['results'][] = $vote;
    }

    $poll_data['clip'] = $clip_forum;
}

// Получаем данные о кураторах темы
$curator = false;
if ($user->rights < 6 && $user->rights !== 3 && array_key_exists($user->id, $current_topic->curators) && $user->is_valid) {
    $curator = true;
}

// Fixed first post
$first_message = null;
if (isset($_GET['clip']) || ($set_forum['postclip'] === 2 && ($set_forum['upfp'] ? $start < (ceil($total - $user->set_user->kmess)) : $start > 0))) {
    $first_message = (new ForumMessage())
        ->users()
        ->where('topic_id', '=', $id)
        ->orderBy('id')
        ->first();
}

$i = 1;
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

        if ($user->id !== $message->user_id && $user->is_valid) {
            $message->reply_url = '/forum/?act=say&type=reply&amp;id=' . $message->id . '&start=' . $start;
            $message->quote_url = '/forum/?act=say&type=reply&amp;id=' . $message->id . '&start=' . $start . '&cyt';
        }

        $i++;
        return $message;
    }
);

if ($user->is_valid) {
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
if (($user->is_valid && ! $current_topic->closed && $config['mod_forum'] !== 3 && $access !== 4) || ($user->rights >= 7)) {
    $write_access = true;
    if ($set_forum['farea']) {
        $token = mt_rand(1000, 100000);
        $_SESSION['token'] = $token;
    }
}

// Список кураторов
$curators_array = [];
if (! empty($current_topic->curators)) {
    foreach ($current_topic->curators as $key => $value) {
        $curators_array[] = '<a href="/profile/?user=' . $key . '">' . $value . '</a>';
    }
}

// Setting the canonical URL
$page = $request->getQuery('page', 0, FILTER_VALIDATE_INT);
$canonical = $config['homeurl'] . $current_topic->url;
if ($page > 1) {
    $canonical .= '&page=' . $page;
}
$view->addData(
    [
        'canonical'   => $canonical,
        'title'       => htmlspecialchars_decode($current_topic->name),
        'page_title'  => htmlspecialchars_decode($current_topic->name),
        'keywords'    => $current_topic->calculated_meta_keywords,
        'description' => $current_topic->calculated_meta_description,
    ]
);

echo $view->render(
    'forum::topic',
    [
        'first_post'       => $first_message,
        'topic'            => $current_topic,
        'topic_vote'       => $topic_vote ?? null,
        'curators_array'   => $curators_array,
        'view_count'       => $current_topic->view_count,
        'pagination'       => $message->render(),
        'start'            => $start,
        'id'               => $id,
        'token'            => $token ?? null,
        'bbcode'           => di(Johncms\System\Legacy\Bbcode::class)->buttons('new_message', 'msg'),
        'settings_forum'   => $set_forum,
        'write_access'     => $write_access,
        'messages'         => $messages ?? [],
        'online'           => $online ?? [],
        'total'            => $total,
        'files_count'      => $forum_settings['file_counters'] ? $tools->formatNumber($current_topic->files_count) : 0,
        'unread_count'     => $tools->formatNumber($counters->forumUnreadCount()),
        'filter_by_author' => $filter,
        'poll_data'        => $poll_data,
    ]
);
