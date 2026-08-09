<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Modules\Forum\Application\DTO\UnreadTopicsQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\UseCases\ViewUnreadTopicsUseCase;
use Johncms\Http\View\ViewResponse;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Users\User;

final readonly class UnreadTopicsController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewUnreadTopicsUseCase $viewUnreadTopicsUseCase,
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
        $page = max(1, $request->queryInt('page', 1));
        $start = ($page - 1) * (int) $this->currentUser->config->kmess;

        $result = $this->viewUnreadTopicsUseCase->execute(
            new UnreadTopicsQueryDTO(
                start: $start,
            )
        );

        $caption = __('Unread');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new ViewResponse(
            '@forum/public/topic-list.twig',
            [
                'pagination'          => $pagination->hasPages() ? $pagination->render() : null,
                'title'               => $caption,
                'page_title'          => $caption,
                'empty_message'       => __('The list is empty'),
                'topics'              => $result->topics,
                'total'               => $result->total,
                'show_period'         => false,
                'period_action'       => '/forum/topics-period/',
                'mark_as_read_action' => $result->total > 0 ? '/forum/unread/mark-read/' : '',
            ]
        );
    }
}
