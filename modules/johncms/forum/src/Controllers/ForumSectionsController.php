<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Johncms\Forum\ForumCounters;
use Johncms\Forum\Models\ForumFile;
use Johncms\Forum\Models\ForumSection;
use Johncms\Utility\Numbers;

class ForumSectionsController extends BaseForumController
{
    public function index(ForumCounters $forumCounters): string
    {
        // Forum categories
        $sections = (new ForumSection())
            ->withCount('subsections', 'topics')
            ->with('subsections')
            ->where('parent', '=', 0)
            ->orWhereNull('parent')
            ->orderBy('sort')
            ->get();

        unset($_SESSION['fsort_id'], $_SESSION['fsort_users']);

        $forum_settings = di('config')['forum']['settings'];
        $this->metaTagManager->setKeywords($forum_settings['forum_keywords']);
        $this->metaTagManager->setDescription($forum_settings['forum_description']);

        return $this->render->render(
            'forum::index',
            [
                'sections'     => $sections,
                'online'       => [
                    'users' => $forumCounters->onlineUsers(),
                    'guests' => $forumCounters->onlineGuests(),
                ],
                'files_count'  => $forum_settings['file_counters'] ? Numbers::formatNumber((new ForumFile())->count()) : 0,
                'unread_count' => Numbers::formatNumber($forumCounters->unreadMessages()),
            ]
        );
    }
}
