<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\ForumVisitorsQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumVisitorsUseCase;
use Johncms\NavChain;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;
use Johncms\Users\User;

final readonly class ViewForumVisitorsController
{
    public function __construct(
        private NavChain $navChain,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumVisitorsUseCase $viewForumVisitorsUseCase,
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

        $showGuests = $request->queryParam('mode') === 'guests';
        $page = max(1, $request->queryInt('page', 1));
        $start = ($page - 1) * (int) $this->currentUser->config->kmess;
        $result = $this->viewForumVisitorsUseCase->execute(
            new ForumVisitorsQueryDTO(
                start: $start,
                guests: $showGuests,
            )
        );

        $caption = __('Who in Forum');
        $this->navChain->add(__('Forum'), '/forum/');
        $this->navChain->add($caption);

        $pagination = $this->paginationFactory->create($result->total, null, 'page', $page);

        return new ViewResponse(
            '@forum/public/visitors.twig',
            [
                'title'           => $caption,
                'page_title'      => $caption,
                'empty_message'   => __('The list is empty'),
                'items'           => $result->items,
                'pagination'      => $pagination->hasPages() ? $pagination->render() : null,
                'total'           => $result->total,
                'is_users'        => ! $showGuests,
                'users_list_url'  => '/forum/visitors/',
                'guests_list_url' => '/forum/visitors/?mode=guests',
            ]
        );
    }
}
