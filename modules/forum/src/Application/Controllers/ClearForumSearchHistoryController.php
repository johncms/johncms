<?php

declare(strict_types=1);

namespace Johncms\Modules\Forum\Application\Controllers;

use Johncms\Http\Controller\ControllerContext;
use Johncms\Modules\Forum\Application\Exceptions\ForumAccessDeniedException;
use Johncms\Modules\Forum\Application\Services\ForumErrorRenderer;
use Johncms\Modules\Forum\Application\UseCases\ClearForumSearchHistoryUseCase;
use Johncms\Modules\Forum\Application\UseCases\EnsureForumUserAccessUseCase;
use Johncms\Http\Request;
use Johncms\System\View\Render;

final readonly class ClearForumSearchHistoryController
{
    public function __construct(
        private ControllerContext $controllerContext,
        private Render $render,
        private Request $request,
        private EnsureForumUserAccessUseCase $forumUserAccessUseCase,
        private ForumErrorRenderer $forumErrorRenderer,
        private ClearForumSearchHistoryUseCase $clearForumSearchHistoryUseCase,
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

        if ($this->request->hasBody('submit')) {
            $this->clearForumSearchHistoryUseCase->execute();
            redirect('/forum/search/');
        }

        return $this->render->render(
            'forum::clear_search_history',
            [
                'title'      => __('Forum search'),
                'page_title' => __('Forum search'),
                'back_url'   => '/forum/search/',
            ]
        );
    }
}
