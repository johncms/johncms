<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Auth\CurrentUser;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\TopicsPeriodQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewTopicsByPeriodUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;

final readonly class TopicsPeriodController
{
    public function __construct(
        private NavChain $navChain,
        private CurrentUser $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewTopicsByPeriodUseCase $viewTopicsByPeriodUseCase,
        private PaginationFactory $paginationFactory,
    ) {
    }

    public function __invoke(Request $request): ViewResponse
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse($exception);
        }

        $hours = $request->bodyInt('vr', $request->queryInt('vr', 24));
        if ($hours <= 0) {
            $hours = 24;
        }
        $page = max(1, $request->queryInt('page', 1));
        $start = ($page - 1) * (int) $this->currentUser->user()->config->kmess;

        $result = $this->viewTopicsByPeriodUseCase->execute(
            new TopicsPeriodQueryDTO(
                start: $start,
                hours: min(999, $hours),
            )
        );

        $caption = sprintf(__('All for period %d hours'), $result->hours);
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new ViewResponse(
            '@forum/public/topic-list.twig',
            [
                'pagination'          => $pagination->hasPages() ? $pagination->render() : null,
                'title'               => $caption,
                'page_title'          => $caption,
                'empty_message'       => __('There is nothing new in this forum for selected period'),
                'topics'              => $result->topics,
                'total'               => $result->total,
                'show_period'         => true,
                'period_action'       => '/forum/topics-period/',
                'mark_as_read_action' => '',
            ]
        );
    }
}
