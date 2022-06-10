<?php

declare(strict_types=1);

namespace Johncms\Forum\Controllers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Johncms\Forum\ForumCounters;
use Johncms\Forum\ForumUtils;
use Johncms\Forum\Models\ForumFile;
use Johncms\Forum\Models\ForumSection;
use Johncms\Forum\Resources\TopicResource;
use Johncms\Forum\Services\ForumTopicService;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Utility\Numbers;

class SectionsController extends BaseForumController
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

    public function show(
        int $id,
        Session $session,
        ForumCounters $forumCounters,
        ForumTopicService $topicRepository,
        ?User $user,
        ForumUtils $forumUtils,
    ): string {
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
        $forumUtils->buildBreadcrumbs($currentSection->parent, $currentSection->name);

        $this->metaTagManager->setTitle($currentSection->name);
        $this->metaTagManager->setPageTitle($currentSection->name);
        $this->metaTagManager->setKeywords($currentSection->calculated_meta_keywords);
        $this->metaTagManager->setDescription($currentSection->calculated_meta_description);

        $templateBaseData = [
            'id'           => $currentSection->id,
            'online'       => [
                'users'  => $forumCounters->onlineUsers(),
                'guests' => $forumCounters->onlineGuests(),
            ],
            'files_count'  => $forumSettings['file_counters'] ? Numbers::formatNumber($currentSection->category_files_count) : 0,
            'unread_count' => Numbers::formatNumber($forumCounters->unreadMessages()),
        ];

        // If the section contains topics, then show a list of topics
        if ($currentSection->section_type) {
            $topics = $topicRepository->getTopics($id)->paginate();
            $resource = TopicResource::createFromCollection($topics);

            // Access to create topics
            $createAccess = false;
            if (($user && ! $user->hasBan(['forum_read_only', 'forum_create_topics'])) || $user?->hasAnyRole()) {
                $createAccess = true;
            }

            return $this->render->render(
                'forum::topics',
                array_merge(
                    $templateBaseData,
                    [
                        'pagination'     => $topics->render(),
                        'create_access'  => $createAccess,
                        'createTopicUrl' => route('forum.newTopic', ['sectionId' => $id]),
                        'topics'         => $resource->getItems(),
                        'total'          => $topics->total(),
                    ]
                )
            );
        } else {
            // List of forum sections
            $sections = (new ForumSection())
                ->withCount(['subsections', 'topics'])
                ->where('parent', '=', $id)
                ->orderBy('sort')
                ->get();

            return $this->render->render(
                'forum::section',
                array_merge(
                    $templateBaseData,
                    [
                        'sections' => $sections,
                        'total'    => $sections->count(),
                    ]
                )
            );
        }
    }
}
