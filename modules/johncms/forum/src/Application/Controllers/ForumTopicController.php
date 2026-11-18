<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Services\UnreadTopicsCounter;
use Johncms\Auth\Authorization\AccessCheckerInterface;
use Johncms\Auth\CurrentUser;
use Johncms\Modules\Forum\Application\Services\ForumPermissions;
use Johncms\Modules\Forum\Application\Services\TopicMoveTargets;
use Johncms\Http\PageMeta;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\UseCases\ViewForumTopicUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ForumTopicController
{
    /** The result of an action on the topic (a vote, a poll removed), shown as a toast after the redirect. */
    public const string MESSAGE = 'forum_topic_message';

    public function __construct(
        private Session $session,
        private CurrentUser $currentUser,
        private NavChain $navChain,
        private ViewForumTopicUseCase $viewForumTopicUseCase,
        private PaginationFactory $paginationFactory,
        private AccessCheckerInterface $accessChecker,
        private UnreadTopicsCounter $unreadTopics,
        private TopicMoveTargets $moveTargets,
    ) {
    }

    public function __invoke(Request $request, string $path): ViewResponse
    {
        $perPage = (int) $this->currentUser->user()->config->kmess;
        $page = max(1, $request->queryInt('page', 1));
        if ($page === 1) {
            $start = max(0, $request->queryInt('start', 0));
            if ($start > 0) {
                $page = (int) floor($start / max(1, $perPage)) + 1;
            }
        }

        $filterByUsers = [];
        $filterTopicId = (int) $this->session->get('fsort_id', 0);
        $filterEnabled = $filterTopicId > 0 && ! empty($this->session->get('fsort_users'));
        if ($filterEnabled && is_array($this->session->get('fsort_users'))) {
            $filterByUsers = array_map('intval', $this->session->get('fsort_users'));
        }

        try {
            $result = $this->viewForumTopicUseCase->execute(
                path: $path,
                page: $page,
                showVoteResult: $request->query->has('vote_result'),
                incrementViewCount: $this->shouldIncrementViewCount($path),
                filterEnabled: $filterEnabled,
                filterTopicId: $filterTopicId,
                filterByUsers: $filterByUsers,
            );
        } catch (ForumNotFoundException) {
            // ForumNotFoundException always maps to FORUM_NOT_FOUND (404), the same status
            // pageNotFound() answers with.
            pageNotFound(
                title: __('Forum'),
                message: __('Topic has been deleted or does not exists'),
            );
        }

        $topic = $result->viewData['topic'];
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($topic->section_id, $result->title);


        $pagination = $this->paginationFactory->create(
            (int) $result->viewData['total'],
            $perPage,
            'page',
            $page
        );

        $meta = new PageMeta($result->title, $page, $topic->calculated_meta_description);
        $filterSuffix = $page > 1 ? '?page=' . $page : '';
        $canModerate = $this->accessChecker->allows(ForumPermissions::TOPIC_MODERATE);

        return new ViewResponse(
            '@forum/public/topic.twig',
            array_merge(
                $result->viewData,
                [
                    'canonical'        => $result->canonical,
                    // What the heading says of the topic; the columns may be empty in the table.
                    'state'            => [
                        'closed'     => (bool) $topic->closed,
                        'pinned'     => (bool) $topic->pinned,
                        'deleted'    => (bool) $topic->deleted,
                        'deleted_by' => (string) $topic->deleted_by,
                    ],
                    'views'            => (int) $topic->view_count,
                    'message'          => (string) $this->session->getFlash(self::MESSAGE),
                    // The count is empty in topics that were never recounted.
                    'one_post_in_sight' => (int) $topic->post_count <= 1,
                    'title'            => $meta->title,
                    'page_title'       => $result->title,
                    'keywords'         => $topic->calculated_meta_keywords,
                    'description'      => $meta->description,
                    'pagination'       => $pagination->hasPages() ? $pagination->render(withRange: true) : null,
                    'unread_count'     => ShortNumberFormatter::format($this->unreadTopics->count()),
                    'reply_url'        => '/forum/new-message/' . $result->viewData['id'] . '/' . $filterSuffix,
                    'reply_above'      => $result->viewData['newest_first'],
                    'filter_url'       => '/forum/filter/' . $result->viewData['id'] . '/'
                        . ($result->viewData['filter_by_author'] ? 'clear/' : '') . $filterSuffix,
                    'can_moderate'     => $canModerate,
                    // The dialog of moving the topic needs the sections only when it can be opened.
                    'move_targets'     => $canModerate ? $this->moveTargets->forSection($topic->section_id) : [],
                    // What the dialog of changing the topic starts from; the meta tags may be empty in the table.
                    'change_form'      => [
                        'name'             => $topic->name,
                        'meta_keywords'    => $topic->meta_keywords ?? '',
                        'meta_description' => $topic->meta_description ?? '',
                    ],
                    'can_set_curators' => $this->accessChecker->allows(ForumPermissions::CURATORS_MANAGE),
                    'can_manage_poll'  => $this->accessChecker->allows(ForumPermissions::POLL_MANAGE),
                ]
            )
        );
    }

    private function shouldIncrementViewCount(string $path): bool
    {
        $viewedTopics = (array) $this->session->get('viewed_topics', []);
        if (! in_array($path, $viewedTopics, true)) {
            $viewedTopics[] = $path;
            $this->session->set('viewed_topics', $viewedTopics);

            return true;
        }

        return false;
    }
}
