<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewPostUseCase;
use Johncms\Modules\Forum\Application\Exceptions\ForumNotFoundException;
use Johncms\Modules\Forum\Application\ForumUtils;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ShowPostController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private User $currentUser,
        private NavChain $navChain,
        private EnsureForumAccessUseCase $forumAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewPostUseCase $viewPostUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(int $id): string
    {
        try {
            $this->forumAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Show post'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $start = (int) $this->request->getQuery('start', 0);

        try {
            $result = $this->viewPostUseCase->execute(
                postId: $id,
                start: $start,
                forumSettings: $this->getForumSettings(),
                homeUrl: (string) config('johncms.homeurl')
            );
        } catch (ForumNotFoundException | ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render(
                $this->render,
                $exception,
                [
                    'title'         => __('Show post'),
                    'type'          => 'alert-danger',
                    'message'       => __('Wrong data'),
                    'back_url'      => '/forum/',
                    'back_url_name' => __('Forum'),
                ]
            );
        }

        $this->render->addData(
            [
                'canonical'  => $result['canonical'],
                'title'      => __('Show post'),
                'page_title' => __('Show post'),
            ]
        );
        $this->navChain->add(__('Forum'), '/forum/');
        ForumUtils::buildBreadcrumbs(
            (int) $result['topic']->section_id,
            (string) $result['topic']->name,
            (string) $result['topic']->url
        );

        return $this->render->render(
            'forum::show_post',
            [
                'post'  => $result['post'],
                'topic' => $result['topic'],
            ]
        );
    }

    private function getForumSettings(): array
    {
        $setForumDefault = [
            'farea'    => 0,
            'upfp'     => 0,
            'preview'  => 1,
            'postclip' => 1,
            'postcut'  => 2,
        ];

        $setForum = [];
        if ($this->currentUser->isValid() && ! empty($this->currentUser->set_forum)) {
            $setForum = (array) $this->currentUser->set_forum;
        }

        return array_merge($setForumDefault, $setForum);
    }
}
