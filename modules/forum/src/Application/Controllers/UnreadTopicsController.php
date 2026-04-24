<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\UnreadTopicsQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewUnreadTopicsUseCase;
use Johncms\NavChain;
use Johncms\Security\Csrf;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class UnreadTopicsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Csrf $csrf,
        private Tools $tools,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewUnreadTopicsUseCase $viewUnreadTopicsUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(): string
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->render($this->render, $exception);
        }
        $start = (max(1, (int) $this->request->getQuery('page', 1)) - 1) * (int) $this->currentUser->config->kmess;

        $result = $this->viewUnreadTopicsUseCase->execute(
            new UnreadTopicsQueryDTO(
                start: $start,
            )
        );

        $caption = __('Unread');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        return $this->render->render(
            'forum::new_topics',
            [
                'pagination'           => $this->buildPagination($start, $result->total),
                'title'                => $caption,
                'page_title'           => $caption,
                'empty_message'        => __('The list is empty'),
                'topics'               => $result->topics,
                'total'                => $result->total,
                'show_period'          => false,
                'mark_as_read_action'  => '/forum/unread/mark-read/',
                'mark_as_read_enabled' => $result->total > 0,
                'csrf_token'           => $this->csrf->getToken(),
            ]
        );
    }

    private function buildPagination(int $start, int $total): string
    {
        return $this->tools->displayPagination('/forum/unread/?', max(0, $start), $total, $this->currentUser->config->kmess);
    }
}
