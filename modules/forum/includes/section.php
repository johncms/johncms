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
use Illuminate\Database\Eloquent\ModelNotFoundException;
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

$forum_settings = di('config')['forum']['settings'];

try {
    $current_section = (new ForumSection());
    if ($forum_settings['file_counters']) {
        $current_section = $current_section->withCount('categoryFiles');
    }
    $current_section = $current_section->findOrFail($id);
} catch (ModelNotFoundException $exception) {
    ForumUtils::notFound();
}

unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

// Build breadcrumbs
ForumUtils::buildBreadcrumbs($current_section->parent, $current_section->name);

// List of forum sections
$sections = (new ForumSection())
    ->withCount(['subsections', 'topics'])
    ->where('parent', '=', $id)
    ->orderBy('sort')
    ->get();

// Online users
$online = [
    'online_u' => (new User())->online()->where('place', 'like', '/forum%')->count(),
    'online_g' => (new GuestSession())->online()->where('place', 'like', '/forum%')->count(),
];

$view->addData(
    [
        'keywords'    => $current_section->calculated_meta_keywords,
        'description' => $current_section->calculated_meta_description,
    ]
);

echo $view->render(
    'forum::section',
    [
        'title'        => $current_section->name,
        'page_title'   => $current_section->name,
        'id'           => $current_section->id,
        'sections'     => $sections,
        'online'       => $online,
        'total'        => $sections->count(),
        'files_count'  => $forum_settings['file_counters'] ? $tools->formatNumber($current_section->category_files_count) : 0,
        'unread_count' => $tools->formatNumber($counters->forumUnreadCount()),
    ]
);
