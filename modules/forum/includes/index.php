<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumFile;
use Forum\Models\ForumSection;
use Johncms\Counters;
use Johncms\NavChain;
use Johncms\System\Legacy\Tools;
use Johncms\Users\GuestSession;
use Johncms\Users\User;

/**
 * @var Tools $tools
 * @var Counters $counters
 * @var NavChain $nav_chain
 */

// Forum categories
$sections = (new ForumSection())
    ->withCount('subsections', 'topics')
    ->with('subsections')
    ->where('parent', '=', 0)
    ->orWhereNull('parent')
    ->orderBy('sort')
    ->get();

$forum_settings = di('config')['forum']['settings'];

// Считаем файлы
if ($forum_settings['file_counters']) {
    $files_count = (new ForumFile())->count();
}

// Считаем пользователей онлайн
$online = [
    'online_u' => (new User())->online()->where('place', 'like', '/forum%')->count(),
    'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum%')->count(),
];

unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

$view->addData(
    [
        'keywords'    => $forum_settings['forum_keywords'],
        'description' => $forum_settings['forum_description'],
    ]
);

echo $view->render(
    'forum::index',
    [
        'title'        => __('Forum'),
        'page_title'   => __('Forum'),
        'sections'     => $sections,
        'online'       => $online,
        'files_count'  => $forum_settings['file_counters'] ? $tools->formatNumber($files_count) : 0,
        'unread_count' => $tools->formatNumber($counters->forumUnreadCount()),
    ]
);
