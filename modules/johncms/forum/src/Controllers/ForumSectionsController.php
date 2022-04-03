<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Forum\ForumCounters;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Models\ForumFile;
use Johncms\Forum\Models\ForumSection;
use Johncms\Http\Session;
use Johncms\Utility\Numbers;

class ForumSectionsController extends BaseForumController
{
    public function index(Session $session, ForumCounters $forumCounters): string
    {
        // Forum categories
        $sections = (new ForumSection())
            ->withCount('subsections', 'topics')
            ->with('subsections')
            ->where('parent', '=', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->get();

        $session->remove(['fsort_id', 'fsort_users']);

        $forumSettings = config('forum.settings');
        $this->metaTagManager->setKeywords($forumSettings['forum_keywords']);
        $this->metaTagManager->setDescription($forumSettings['forum_description']);

        return $this->render->render(
            'forum::index',
            [
                'sections'     => $sections,
                'online'       => [
                    'users'  => $forumCounters->onlineUsers(),
                    'guests' => $forumCounters->onlineGuests(),
                ],
                'files_count'  => $forumSettings['file_counters'] ? Numbers::formatNumber((new ForumFile())->count()) : 0,
                'unread_count' => Numbers::formatNumber($forumCounters->unreadMessages()),
            ]
        );
    }

    public function section(int $id, Session $session, ForumCounters $forumCounters): string
    {
        $forumSettings = config('forum.settings');
        try {
            $currentSection = ForumSection::query()
                ->when($forumSettings['file_counters'], function (Builder $builder) {
                    return $builder->withCount('categoryFiles');
                })
                ->findOrFail($id);
        } catch (ModelNotFoundException) {
            ForumUtils::notFound();
        }

        $session->remove(['fsort_id', 'fsort_users']);

        // Build breadcrumbs
        ForumUtils::buildBreadcrumbs($currentSection->parent, $currentSection->name);

        $this->metaTagManager->setTitle($currentSection->name);
        $this->metaTagManager->setPageTitle($currentSection->name);
        $this->metaTagManager->setKeywords($currentSection->calculated_meta_keywords);
        $this->metaTagManager->setDescription($currentSection->calculated_meta_description);

        // List of forum sections
        $sections = (new ForumSection())
            ->withCount(['subsections', 'topics'])
            ->where('parent', '=', $id)
            ->orderBy('sort')
            ->get();

        return $this->render->render(
            'forum::section',
            [
                'id'           => $currentSection->id,
                'sections'     => $sections,
                'online'       => [
                    'users'  => $forumCounters->onlineUsers(),
                    'guests' => $forumCounters->onlineGuests(),
                ],
                'total'        => $sections->count(),
                'files_count'  => $forumSettings['file_counters'] ? Numbers::formatNumber($currentSection->category_files_count) : 0,
                'unread_count' => Numbers::formatNumber($forumCounters->unreadMessages()),
            ]
        );
    }
}
