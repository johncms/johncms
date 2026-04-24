<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\DTO\TopicsPeriodQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewTopicsByPeriodUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\Legacy\Tools;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class TopicsPeriodController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private Tools $tools,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewTopicsByPeriodUseCase $viewTopicsByPeriodUseCase,
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

        $hours = (int) ($this->request->getPost('vr', $this->request->getQuery('vr', 24)) ?? 24);
        if ($hours <= 0) {
            $hours = 24;
        }
        $start = (max(1, (int) $this->request->getQuery('page', 1)) - 1) * (int) $this->currentUser->config->kmess;

        $result = $this->viewTopicsByPeriodUseCase->execute(
            new TopicsPeriodQueryDTO(
                start: $start,
                hours: min(999, $hours),
            )
        );

        $caption = sprintf(__('All for period %d hours'), $result->hours);
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        return $this->render->render(
            'forum::new_topics',
            [
                'pagination'    => $this->tools->displayPagination(
                    '/forum/topics-period/?vr=' . $result->hours . '&amp;',
                    $start,
                    $result->total,
                    $this->currentUser->config->kmess
                ),
                'title'         => $caption,
                'page_title'    => $caption,
                'empty_message' => __('There is nothing new in this forum for selected period'),
                'topics'        => $result->topics,
                'total'         => $result->total,
                'show_period'   => true,
                'period_action' => '/forum/topics-period/',
            ]
        );
    }
}
