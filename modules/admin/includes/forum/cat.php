<?php

/**
 * This file is part of JohnCMS Content Management System.
 *
 * @copyright JohnCMS Community
 * @license   https://opensource.org/licenses/GPL-3.0 GPL-3.0
 * @link      https://johncms.com JohnCMS Project
 */

declare(strict_types=1);

use Forum\Models\ForumSection;

defined('_IN_JOHNADM') || die('Error: restricted access');

/**
 * @var PDO $db
 * @var Johncms\System\Legacy\Tools $tools
 * @var Johncms\System\Users\User $user
 * @var Johncms\NavChain $nav_chain
 * @var Johncms\System\Http\Request $request
 */
$title = __('Forum structure');
$nav_chain->add($title, '?mod=cat');

module_lib_loader('forum');

// Управление категориями и разделами
if ($id) {
    // Управление разделами
    $current_section = (new ForumSection())->findOrFail($id);
    $nav_chain->add($current_section->name, '?mod=cat' . (! empty($current_section->parent) ? '&amp;id=' . $current_section->parent : ''));
    $title = __('List of sections');
    $subsections = $current_section->subsections()->orderBy('sort')->withCount('subsections')->get();
    if ($subsections->count() > 0) {
        $items = [];
        foreach ($subsections as $subsection) {
            /** @var ForumSection $subsection */
            $res = $subsection->toArray();
            $res['list_url'] = '?mod=cat&amp;id=' . $subsection->id;
            $res['public_url'] = $subsection->url;
            $res['description'] = htmlspecialchars($subsection->description);
            $res['counter'] = $subsection->subsections_count;
            $res['edit_url'] = '?mod=edit&amp;id=' . $subsection->id;
            $res['delete_url'] = '?mod=del&amp;id=' . $subsection->id;
            $items[] = $res;
        }
    }
} else {
    // Управление категориями
    $title = __('List of categories');
    $sections = (new ForumSection())
        ->where('parent', 0)
        ->orWhereNull('parent')
        ->orderBy('sort')
        ->withCount('subsections')
        ->get();
    $items = [];
    foreach ($sections as $section) {
        /** @var ForumSection $section */
        $res = $section->toArray();
        $res['list_url'] = '?mod=cat&amp;id=' . $section->id;
        $res['public_url'] = $section->url;
        $res['description'] = htmlspecialchars($section->description);
        $res['counter'] = $section->subsections_count;
        $res['edit_url'] = '?mod=edit&amp;id=' . $section->id;
        $res['delete_url'] = '?mod=del&amp;id=' . $section->id;
        $items[] = $res;
    }
}

$data['items'] = $items ?? [];
$data['back_url'] = $id ? '?mod=cat' : '/admin/forum/';
$data['add_form_url'] = '?mod=add' . ($id ? '&amp;id=' . $id : '');
echo $view->render(
    'admin::forum/structure',
    [
        'title'      => $title,
        'page_title' => $title,
        'data'       => $data,
    ]
);
