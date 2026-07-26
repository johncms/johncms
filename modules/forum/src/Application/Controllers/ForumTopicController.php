<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\UseCases\ViewForumTopicUseCase;
use Johncms\NavChain;
use Johncms\Security\Csrf;
use Johncms\Http\Request;
use Johncms\Http\Session;
use Johncms\System\View\Render;
use Johncms\Users\User;
use Johncms\Utils\ShortNumberFormatter;

final readonly class ForumTopicController
{
    public function __construct(
        private Render $render,
        private Request $request,
        private Session $session,
        private User $currentUser,
        private NavChain $navChain,
        private ViewForumTopicUseCase $viewForumTopicUseCase,
        private Csrf $csrf,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(string $path): string
    {
        $setForum = $this->getForumSettings();
        $perPage = (int) $this->currentUser->config->kmess;
        $page = max(1, $this->request->queryInt('page', 1));
        if ($page === 1) {
            $start = max(0, $this->request->queryInt('start', 0));
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
                showClip: $this->request->query->has('clip'),
                showVoteResult: $this->request->query->has('vote_result'),
                incrementViewCount: $this->shouldIncrementViewCount($path),
                setForum: $setForum,
                filterEnabled: $filterEnabled,
                filterTopicId: $filterTopicId,
                filterByUsers: $filterByUsers,
            );
        } catch (ForumNotFoundException $exception) {
            // ForumNotFoundException always maps to FORUM_NOT_FOUND (404), the same status
            // pageNotFound() answers with, so no separate status assignment is needed here.
            $this->render->addData(['error_code' => $exception->getErrorCode()->value]);
            pageNotFound(
                title: __('Forum'),
                message: __('Topic has been deleted or does not exists'),
            );
        }

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($result->viewData['topic']->section_id, $result->title);

        $this->render->addData(
            [
                'canonical'   => $result->canonical,
                'title'       => $this->buildTopicDocumentTitle($result->title, $page),
                'page_title'  => $result->title,
                'keywords'    => $result->viewData['topic']->calculated_meta_keywords,
                'description' => $this->buildTopicDescription(
                    (string) $result->viewData['topic']->calculated_meta_description,
                    $page
                ),
            ]
        );

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');

        $pagination = $this->paginationFactory->create(
            (int) $result->viewData['total'],
            $perPage,
            'page',
            $page
        );

        return $this->render->render(
            'forum::topic',
            array_merge(
                $result->viewData,
                [
                    'pagination'   => $pagination->render(),
                    'unread_count' => ShortNumberFormatter::format($counters->forumUnreadCount()),
                    'csrf_token'   => $this->csrf->getToken(),
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
