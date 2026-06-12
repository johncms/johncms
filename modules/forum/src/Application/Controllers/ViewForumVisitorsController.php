<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Http\Pagination\PaginationFactory;
use Johncms\Modules\Forum\Application\DTO\ForumVisitorsQueryDTO;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Modules\Forum\Application\UseCases\ViewForumVisitorsUseCase;
use Johncms\NavChain;
use Johncms\System\Http\Request;
use Johncms\System\View\Render;
use Johncms\Users\User;

final readonly class ViewForumVisitorsController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private NavChain $navChain,
        private User $currentUser,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ViewForumVisitorsUseCase $viewForumVisitorsUseCase,
        private PaginationFactory $paginationFactory,
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

        $showGuests = $this->request->getQuery('mode') === 'guests';
        $page = max(1, (int) $this->request->getQuery('page', 1));
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

        return $this->render->render(
            'forum::who',
            [
                'title'           => $caption,
                'page_title'      => $caption,
                'empty_message'   => __('The list is empty'),
                'items'           => $result->items,
                'pagination'      => $pagination->render(),
                'total'           => $result->total,
                'is_users'        => ! $showGuests,
                'users_list_url'  => '/forum/visitors/',
                'guests_list_url' => '/forum/visitors/?mode=guests',
                'show_period'     => false,
            ]
        );
    }
}
