<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\ForumUtils;
use Forum\Models\ForumTopic;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Forum\Models\ForumSection;
use Johncms\Counters;
use Johncms\NavChain;
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

try {
    $current_section = (new ForumSection());
    if ($forum_settings['file_counters']) {
        $current_section = $current_section->withCount('sectionFiles');
    }
    $current_section = $current_section->findOrFail($id);
} catch (ModelNotFoundException $exception) {
    ForumUtils::notFound();
}

// Build breadcrumbs
ForumUtils::buildBreadcrumbs($current_section->parent, $current_section->name);

// List of forum topics
$topics = (new ForumTopic())
    ->read()
    ->where('section_id', '=', $id)
    ->orderByDesc('pinned')
    ->orderByDesc('last_post_date')
    ->paginate($user->set_user->kmess);

// Check access to create topic
$create_access = false;
if (($user->is_valid && ! isset($user->ban['1']) && ! isset($user->ban['11']) && $config['mod_forum'] !== 4) || $user->rights > 0) {
    $create_access = true;
}

unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

// Считаем пользователей онлайн
$online = [
    'online_u' => (new User())->online()->where('place', 'like', '/forum%')->count(),
    'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum%')->count(),
];

// Setting the canonical URL
$page = $request->getQuery('page', 0, FILTER_VALIDATE_INT);
$canonical = $config['homeurl'] . $current_section->url;
if ($page > 1) {
    $canonical .= '&page=' . $page;
}
$view->addData(
    [
        'canonical'  => $canonical,
        'title'      => htmlspecialchars_decode($current_section->name),
        'page_title' => htmlspecialchars_decode($current_section->name),

        'keywords'    => $current_section->calculated_meta_keywords,
        'description' => $current_section->calculated_meta_description,
    ]
);

echo $view->render(
    'forum::topics',
    [
        'pagination'    => $topics->render(),
        'id'            => $id,
        'create_access' => $create_access,
        'topics'        => $topics->getItems(),
        'online'        => $online,
        'total'         => $topics->total(),
        'files_count'   => $forum_settings['file_counters'] ? $tools->formatNumber($current_section->section_files_count) : 0,
        'unread_count'  => $tools->formatNumber($counters->forumUnreadCount()),
    ]
);
