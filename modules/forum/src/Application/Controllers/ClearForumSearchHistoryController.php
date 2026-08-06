<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ClearForumSearchHistoryUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Http\Request;
use Johncms\Http\View\ViewResponse;

final readonly class ClearForumSearchHistoryController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ClearForumSearchHistoryUseCase $clearForumSearchHistoryUseCase,
    ) {
        $this->controllerContext->initModule('forum');
    }

    public function __invoke(Request $request): ViewResponse
    {
        try {
            $this->forumUserAccessUseCase->execute();
        } catch (ForumAccessDeniedException $exception) {
            return $this->forumErrorRenderer->viewResponse($exception);
        }

        if ($request->hasBody('submit')) {
            $this->clearForumSearchHistoryUseCase->execute();
            redirect('/forum/search/');
        }

        return new ViewResponse(
            '@forum/public/clear-search-history.twig',
            [
                'title'      => __('Forum search'),
                'page_title' => __('Forum search'),
                'action_url' => '/forum/search/history/clear/',
                'back_url'   => '/forum/search/',
            ]
        );
    }
}
