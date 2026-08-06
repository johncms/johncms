<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\UseCases\ViewForumTopicUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Http\Session;
use Johncms\Users\User;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ForumTopicController
{
    public function __construct(
        private Session $session,
        private User $currentUser,
        private NavChain $navChain,
        private ViewForumTopicUseCase $viewForumTopicUseCase,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(Request $request, string $path): ViewResponse
    {
        $setForum = $this->getForumSettings();
        $perPage = (int) $this->currentUser->config->kmess;
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
                showClip: $request->query->has('clip'),
                showVoteResult: $request->query->has('vote_result'),
                incrementViewCount: $this->shouldIncrementViewCount($path),
                setForum: $setForum,
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

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($result->viewData['topic']->section_id, $result->title);

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');

        $pagination = $this->paginationFactory->create(
            (int) $result->viewData['total'],
            $perPage,
            'page',
            $page
        );

        $filterSuffix = $page > 1 ? '?page=' . $page : '';
        $canModerate = $this->currentUser->rights === 3 || $this->currentUser->rights >= 6;

        return new ViewResponse(
            '@forum/public/topic.twig',
            array_merge(
                $result->viewData,
                [
                    'canonical'        => $result->canonical,
                    'title'            => $this->buildTopicDocumentTitle($result->title, $page),
                    'page_title'       => $result->title,
                    'keywords'         => $result->viewData['topic']->calculated_meta_keywords,
                    'description'      => $this->buildTopicDescription(
                        (string) $result->viewData['topic']->calculated_meta_description,
                        $page
                    ),
                    'pagination'       => $pagination->hasPages() ? $pagination->render() : null,
                    'unread_count'     => ShortNumberFormatter::format($counters->forumUnreadCount()),
                    'reply_url'        => '/forum/new-message/' . $result->viewData['id'] . '/' . $filterSuffix,
                    'reply_above'      => ! empty($result->viewData['settings_forum']['upfp']),
                    'quick_reply'      => ! empty($result->viewData['settings_forum']['farea']),
                    'filter_url'       => '/forum/filter/' . $result->viewData['id'] . '/'
                        . ($result->viewData['filter_by_author'] ? 'clear/' : '') . $filterSuffix,
                    'can_moderate'     => $canModerate,
                    'can_set_curators' => $this->currentUser->rights >= 7,
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

    /**
     * @return array<string, int>
     */
    private function getForumSettings(): array
    {
        $default = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $settings = [];
        if ($this->currentUser->isValid() && ! empty($this->currentUser->set_forum)) {
            $settings = (array) $this->currentUser->set_forum;
        }

        return array_merge($default, $settings);
    }

    private function buildTopicDocumentTitle(string $topicTitle, int $page): string
    {
        if ($page <= 1) {
            return $topicTitle;
        }

        return $topicTitle . ' — ' . d__('system', 'Page') . ' ' . $page;
    }

    private function buildTopicDescription(string $description, int $page): string
    {
        if ($page <= 1 || $description === '') {
            return $description;
        }

        return $description . ' — ' . d__('system', 'Page') . ' ' . $page;
    }
}
