<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Exceptions\ForumTopicPathNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\Modules\Forum\Application\Services\ForumAccessResponseBuilder;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumTopicUseCase;
use Johncms\NavChain;
use Johncms\Security\Csrf;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Bbcode;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ForumTopicController
{
    public function __construct(
        private Render $render,
        private Request $request,
        private Tools $tools,
        private User $currentUser,
        private NavChain $navChain,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumAccessResponseBuilder $forumAccessResponseBuilder,
        private ViewForumTopicUseCase $viewForumTopicUseCase,
        private Csrf $csrf,
        private Bbcode $bbcode,
    ) {
    }

    public function __invoke(string $path): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->render->render(
                'system::pages/result',
                $this->forumAccessResponseBuilder->forException($exception)
            );
        }

        $setForum = $this->getForumSettings();
        $page = max(1, (int) $this->request->getQuery('page', 1));
        $start = max(0, (int) $this->request->getQuery('start', 0));
        if ($page === 1 && $start > 0) {
            $page = (int) floor($start / max(1, (int) $this->currentUser->config->kmess)) + 1;
        }
        $start = ($page - 1) * (int) $this->currentUser->config->kmess;

        $filterByUsers = [];
        $filterTopicId = isset($_SESSION['fsort_id']) ? (int) $_SESSION['fsort_id'] : 0;
        $filterEnabled = $filterTopicId > 0 && ! empty($_SESSION['fsort_users']);
        if ($filterEnabled && is_array($_SESSION['fsort_users'])) {
            $filterByUsers = array_map('intval', $_SESSION['fsort_users']);
        }

        try {
            $result = $this->viewForumTopicUseCase->execute(
                path: $path,
                start: $start,
                page: $page,
                showClip: $this->request->getQuery('clip') !== null,
                showVoteResult: $this->request->getQuery('vote_result') !== null,
                incrementViewCount: $this->shouldIncrementViewCount($path),
                setForum: $setForum,
                filterEnabled: $filterEnabled,
                filterTopicId: $filterTopicId,
                filterByUsers: $filterByUsers,
            );
        } catch (ForumTopicPathNotFoundException) {
            ForumUtils::notFound();
        }

        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs($result->viewData['topic']->section_id, $result->title);

        $this->render->addData(
            [
                'canonical'   => $result->canonical,
                'title'       => $result->title,
                'page_title'  => $result->title,
                'keywords'    => $result->viewData['topic']->calculated_meta_keywords,
                'description' => $result->viewData['topic']->calculated_meta_description,
            ]
        );

        /** @var \Johncms\Counters $counters */
        $counters = di('counters');

        return $this->render->render(
            'forum::topic',
            array_merge(
                $result->viewData,
                [
                    'bbcode'       => $this->bbcode->buttons('new_message', 'msg'),
                    'unread_count' => $this->tools->formatNumber($counters->forumUnreadCount()),
                    'csrf_token'   => $this->csrf->getToken(),
                ]
            )
        );
    }

    private function shouldIncrementViewCount(string $path): bool
    {
        if (empty($_SESSION['viewed_topics']) || ! in_array($path, (array) $_SESSION['viewed_topics'], true)) {
            $_SESSION['viewed_topics'][] = $path;

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
            $settings = unserialize((string) $this->currentUser->set_forum, ['allowed_classes' => false]) ?: [];
        }

        return array_merge($default, (array) $settings);
    }
}
